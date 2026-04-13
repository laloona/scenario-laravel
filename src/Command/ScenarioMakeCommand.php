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
use Stateforge\Scenario\Core\Console\Input\Validate\ClassNameValidation;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use function array_map;
use function dirname;
use function explode;
use function implode;
use function is_string;
use function str_replace;
use function ucfirst;

abstract class ScenarioMakeCommand extends ScenarioCommand
{
    final protected function executeCommand(): int
    {
        (new Application())->prepare();

        $config = Application::config();
        if ($config === null) {
            $this->error('Application configuration not found.');
            return self::FAILURE;
        }

        return $this->make($config);
    }

    final protected function askClassname(string $question): ?string
    {
        while (true) {
            $name = $this->ask($question);
            if ($name === null) {
                return null;
            }

            if (is_string($name) === true
                && ClassNameValidation::validate($name) === true) {
                return $name;
            }

            $this->error('Input was invalid, please try again.');
        }
    }

    final protected function generateFile(
        string $name,
        string $source,
        string $target,
        string $directory,
    ): bool {
        File::ensureDirectoryExists(dirname($target));
        File::put(
            $target,
            str_replace(
                ['%nameSpace%', '%className%'],
                [
                    implode('\\', array_map(
                        static fn (string $part): string => ucfirst($part),
                        explode('/', str_replace('\\', '/', $directory)),
                    )),
                    ucfirst($name),
                ],
                File::get($source),
            ),
        );

        return File::exists($target);
    }

    abstract protected function make(Configuration $config): int;
}
