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
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use function array_keys;
use function count;
use function is_string;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

final class ScenarioMakeScenarioCommand extends ScenarioMakeCommand
{
    protected $signature = 'scenario:make:scenario';

    protected $description = 'Make a scenario - should only be used for local/develop/testing';

    protected function make(Configuration $config): int
    {
        $file = $this->getBlueprint('scenario.blueprint');
        if (File::exists($file) === false) {
            $this->error('Scenario generation failed.');
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

        $name = $this->askClassname('Please insert a class name for the new scenario');
        if ($name === null) {
            $this->error('Scenario generation failed.');
            return self::FAILURE;
        }

        $scenario = App::basePath($suite->directory . DIRECTORY_SEPARATOR . ucfirst($name) . '.php');
        if (File::exists($scenario) === true) {
            $this->error('Scenario already exists.');
            return self::FAILURE;
        }

        $generated = $this->generateFile(
            $name,
            $file,
            $scenario,
            $suite->directory,
        );
        if ($generated === false) {
            $this->error('Scenario generation failed.');
            return self::FAILURE;
        }

        $this->info('Scenario "' . $scenario . '" generated, please modify to your needs.');
        return self::SUCCESS;
    }
}
