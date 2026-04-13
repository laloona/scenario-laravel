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

use Illuminate\Support\Facades\File;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use function base_path;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

final class ScenarioMakeParameterCommand extends ScenarioMakeCommand
{
    protected $signature = 'scenario:make:parameter';

    protected $description = 'Make a parameter type - should only be used for local/develop/testing';

    protected function make(Configuration $config): int
    {
        $file = $this->getBlueprint('parameter.blueprint');
        if (File::exists($file) === false) {
            $this->error('Parameter type generation failed.');
            return self::FAILURE;
        }

        $name = $this->askClassname('Please insert a class name for the new parameter type');
        if ($name === null) {
            $this->error('Parameter type generation failed.');
            return self::FAILURE;
        }

        $parameterType = base_path(
            $config->getParameterDirectory() . DIRECTORY_SEPARATOR . ucfirst($name) . '.php',
        );
        if (File::exists($parameterType) === true) {
            $this->error('Parameter type already exists.');
            return self::FAILURE;
        }

        $generated = $this->generateFile(
            $name,
            $file,
            $parameterType,
            $config->getParameterDirectory(),
        );
        if ($generated === false) {
            $this->error('Parameter type generation failed.');
            return self::FAILURE;
        }

        $this->info('Parameter type "' . $parameterType . '" generated, please modify to your needs.');
        return self::SUCCESS;
    }
}
