<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Command;

use Illuminate\Support\Facades\App;
use Stateforge\Scenario\Core\PHPUnit\Finder\ScenarioTestFinder;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\ScenarioDefinition;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Laravel\Facades\Shell;
use function array_keys;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function is_string;
use function sprintf;
use const PHP_BINARY;

final class ScenarioDebugCommand extends ScenarioCommand
{
    protected $signature = 'scenario:debug';

    protected $description = 'Debug a given scenario or unit test - should only be used for local/develop/testing';

    protected function executeCommand(): int
    {
        (new Application())->prepare();

        $scenarioDefinitions = ScenarioRegistry::getInstance()->all();
        $testClasses = (new ScenarioTestFinder())->all();

        $type = $this->getSelectedType($scenarioDefinitions, $testClasses);
        if ($type === false) {
            $this->error('No scenarios or unit tests were found, please create one.');
            return self::FAILURE;
        }

        return match ($type) {
            'Scenario' => $this->debugScenario($scenarioDefinitions),
            'Unit Test' => $this->debugTest($testClasses),
        };
    }

    /**
     * @param array<class-string|string, ScenarioDefinition> $scenarioDefinitions
     * @param array<class-string, list<non-empty-string>> $testClasses
     * @return 'Scenario'|'Unit Test'|false
     */
    private function getSelectedType(array $scenarioDefinitions, array $testClasses): string|false
    {
        if (count($scenarioDefinitions) === 0
            && count($testClasses) === 0) {
            return false;
        }

        if (count($scenarioDefinitions) === 0) {
            return 'Unit Test';
        }

        if (count($testClasses) === 0) {
            return 'Scenario';
        }

        /** @var 'Scenario'|'Unit Test' $selected */
        $selected = $this->choice(
            'Which kind of class would you like to debug?',
            ['Scenario', 'Unit Test'],
        );

        return $selected;
    }

    /**
     * @param array<class-string|string, ScenarioDefinition> $scenarioDefinitions
     */
    private function debugScenario(array $scenarioDefinitions): int
    {
        $scenarios = [];
        foreach ($scenarioDefinitions as $scenarioDefinition) {
            $scenarios[$scenarioDefinition->class . ' (' . $scenarioDefinition->suite . ')'] = $scenarioDefinition;
        }

        /** @var list<non-falsy-string> $options */
        $options = array_values(array_unique(array_keys($scenarios)));
        $chosen = $this->choice('Which scenario would you like to debug?', $options);

        if (is_string($chosen) === false
            || isset($scenarios[$chosen]) === false) {
            $this->error('Invalid scenario selection.');
            return self::FAILURE;
        }

        /** @var class-string $scenarioClass */
        $scenarioClass = $scenarios[$chosen]->class;
        $this->runDebugClass($scenarioClass, null);

        return self::SUCCESS;
    }

    /**
     * @param array<class-string, list<non-empty-string>> $classesMethods
     */
    private function debugTest(array $classesMethods): int
    {
        /** @var list<class-string> $testClasses */
        $testClasses = array_keys($classesMethods);

        if (count($testClasses) === 1) {
            $testClass = array_shift($testClasses);

            if (is_string($testClass) === false) {
                $this->error('Invalid test class selection.');
                return self::FAILURE;
            }
        } else {
            /** @var class-string $testClass */
            $testClass = $this->choice('Which class would you like to debug?', $testClasses);
        }

        /** @var list<non-empty-string> $methods */
        $methods = $classesMethods[$testClass];

        if (count($methods) === 0) {
            $this->runDebugClass($testClass, null);
            return self::SUCCESS;
        }

        if (count($methods) === 1) {
            $selectedMethod = array_shift($methods);
        } else {
            $selectedMethod = $this->choice(
                sprintf('Which method would you like to debug from %s?', $testClass),
                $methods,
            );
        }

        if (is_string($selectedMethod) === false) {
            $this->error('Invalid test method selection.');
            return self::FAILURE;
        }

        $method = $selectedMethod;

        $this->runDebugClass($testClass, $method);
        return self::SUCCESS;
    }

    /**
     * @param class-string $testClass
     */
    private function runDebugClass(string $testClass, ?string $method): bool
    {
        $arguments = [$testClass];

        if ($method !== null) {
            $arguments[] = $method;
        }

        return Shell::run(
            [
                PHP_BINARY,
                $this->getCliPath(),
                'debug',
                ...$arguments,
                '--force',
                '--quiet',
            ],
            App::basePath(),
            $this->output,
        );
    }
}
