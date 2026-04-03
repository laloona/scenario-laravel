<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Command;

use Illuminate\Support\Facades\App;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Exception\RegistryException;
use Scenario\Core\Runtime\Metadata\ExecutionType;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Laravel\Facades\Shell;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function is_array;
use function is_string;
use function sprintf;
use const PHP_BINARY;

final class ScenarioApplyCommand extends ScenarioCommand
{
    protected $signature = '
        scenario:apply
        {scenario? : Scenario name}
        {--parameter=* : Scenario parameters in name=value format (repeatable)}
        {--up : Apply up method (default)}
        {--down : Apply down method}
        {--audit : Print audit output}
    ';

    protected $description = 'Apply a given scenario, use --up or --down to choose how the scenario should be applied - should only be used for local/develop/testing';

    protected function executeCommand(): int
    {
        if ($this->option('up') === true
            && $this->option('down') === true) {
            $this->error('You can just use either up or down scenarios.');
            return self::FAILURE;
        }

        (new Application())->prepare();
        $scenarioDefinitions = ScenarioRegistry::getInstance()->all();
        if (count($scenarioDefinitions) === 0) {
            $this->error('No scenarios were found, please create one.');
            return self::FAILURE;
        }

        $directExecution = false;
        $scenario = $this->argument('scenario');
        $executionType = $this->option('down') === true ? ExecutionType::Down : ExecutionType::Up;

        if (is_string($scenario) === true) {
            try {
                $scenario = ScenarioRegistry::getInstance()->resolve($scenario)->class;
                $directExecution = true;
            } catch (RegistryException $exception) {
                $this->error(sprintf('Given scenario [%s] is not registered.', $scenario));
                $scenario = null;
            }
        }

        if ($scenario === null) {
            $scenarios = [];
            foreach ($scenarioDefinitions as $scenarioDefinition) {
                $scenarios[$scenarioDefinition->class . ' (' . $scenarioDefinition->suite . ')'] = $scenarioDefinition;
            }

            $options = array_keys($scenarios);
            $selected = $this->choice('Which scenario would you like to apply?', $options);

            if (is_string($selected) === false
                || !isset($scenarios[$selected]) === true) {
                $this->error('Invalid scenario selection.');
                return self::FAILURE;
            }

            $scenario = $scenarios[$selected]->class;
        }

        /** @var list<string> $parameters */
        $parameters = [];

        if (is_string($scenario) === true) {
            if ($directExecution === true) {
                $optionParameters = $this->option('parameter');
                $parameters = is_array($optionParameters)
                    ? array_values(array_filter($optionParameters, static fn (mixed $value): bool => is_string($value)))
                    : [];
            } else {
                $definition = $scenarioDefinitions[$scenario];

                if (count($definition->parameters) > 0) {
                    foreach ($definition->parameters as $parameter) {
                        $ask = sprintf(
                            'Please insert value for %s parameter "%s"%s%s',
                            $parameter->type->value,
                            $parameter->name,
                            $parameter->description === null ? '' : ' (' . $parameter->description . ')',
                            $parameter->required === true ? ' (required)' : '',
                        );

                        while (true) {
                            $answer = $this->ask($ask, $parameter->asString($parameter->default));

                            if ($answer === null) {
                                if ($parameter->required === true) {
                                    $this->error('Input was invalid, please try again.');
                                    continue;
                                }

                                break;
                            }

                            if (!is_string($answer) || $parameter->type->valid($answer) === false) {
                                $this->error('Input was invalid, please try again.');
                                continue;
                            }

                            if ($parameter->repeatable === true) {
                                $parameters[] = $parameter->name . '=' . $answer;

                                while ($this->confirm('Do you want to continue?', false) === true) {
                                    $answer = $this->ask($ask, $parameter->asString($parameter->default));

                                    if (!is_string($answer) || $parameter->type->valid($answer) === false) {
                                        $this->error('Input was invalid, please try again.');
                                        continue;
                                    }

                                    $parameters[] = $parameter->name . '=' . $answer;
                                }
                            } else {
                                $parameters[] = $parameter->name . '=' . $answer;
                            }

                            break;
                        }
                    }
                }
            }

            $parameters = array_map(
                static fn (string $param): string => '--parameter=' . $param,
                $parameters,
            );
        }

        if ($this->option('audit') === true) {
            $parameters[] = '--audit';
        }

        /** @var class-string $scenario */
        $applied = $this->applyScenario($scenario, $executionType, $parameters);

        if ($applied === true) {
            $this->info('Scenario "' . $scenario . '::' . $executionType->value . '" was applied successfully.');
            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * @param class-string $className
     * @param list<string> $parameters
     */
    private function applyScenario(
        string $className,
        ExecutionType $executionType,
        array $parameters,
    ): bool {
        return Shell::run(
            [
                PHP_BINARY,
                $this->getCliPath(),
                'apply',
                $className,
                '--' . $executionType->value,
                ...$parameters,
                '--force',
                '--quiet',
            ],
            App::basePath(),
            $this->output,
        );
    }
}
