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
use Illuminate\Support\Facades\File;
use Stateforge\Scenario\Core\Runtime\Application;
use function array_keys;
use function array_map;
use function count;
use function explode;
use function implode;
use function is_string;
use function preg_match;
use function str_replace;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

final class ScenarioMakeCommand extends ScenarioCommand
{
    protected $signature = 'scenario:make';

    protected $description = 'Make a scenario - should only be used for local/develop/testing';

    protected function executeCommand(): int
    {
        (new Application())->prepare();

        $file = $this->getBlueprint('scenario.blueprint');
        if (File::exists($file) === false) {
            $this->error('Scenario generation failed.');
            return self::FAILURE;
        }

        $config = Application::config();
        if ($config === null) {
            $this->error('Application configuration not found.');
            return self::FAILURE;
        }

        $suites = $config->getSuites();
        $options = array_keys($suites);

        if ($options === []) {
            $this->error('Application configuration not found.');
            return self::FAILURE;
        }

        $suite = $suites[$options[0]];

        if (count($suites) > 1) {
            $selected = $this->choice(
                'Please select the suite where you want to make a scenario.',
                $options,
            );

            if (is_string($selected) === false
                || isset($suites[$selected]) === false) {
                $this->error('Scenario generation failed.');
                return self::FAILURE;
            }

            $suite = $suites[$selected];
        }

        $name = $this->askScenarioName();
        $scenario = App::basePath($suite->directory . DIRECTORY_SEPARATOR . ucfirst($name) . '.php');

        if (File::exists($scenario) === true) {
            $this->error('Scenario already exists.');
            return self::FAILURE;
        }

        $content = File::get($file);

        File::put(
            $scenario,
            str_replace(
                ['%nameSpace%', '%className%'],
                [
                    implode('\\', array_map(
                        static fn (string $part): string => ucfirst($part),
                        explode('/', str_replace('\\', '/', $suite->directory)),
                    )),
                    ucfirst($name),
                ],
                $content,
            ),
        );

        if (File::exists($scenario) === false) {
            $this->error('Scenario generation failed.');
            return self::FAILURE;
        }

        $this->info('Scenario "' . $scenario . '" generated, please modify to your needs.');
        return self::SUCCESS;
    }

    private function askScenarioName(): string
    {
        while (true) {
            $name = $this->ask('Please insert a class name for the new scenario');

            if (is_string($name) === true
                && preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name) === 1) {
                return $name;
            }

            $this->error('Input was invalid, please try again.');
        }
    }
}
