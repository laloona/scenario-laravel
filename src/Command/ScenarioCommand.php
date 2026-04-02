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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use function is_array;
use function sprintf;
use const DIRECTORY_SEPARATOR;

abstract class ScenarioCommand extends Command
{
    final public function handle(): int
    {
        $allowedEnvs = Config::get('scenario.allowed_envs', ['local', 'testing']);

        if (is_array($allowedEnvs) === false) {
            $this->error('Configuration key "scenario.allowed_envs" must be an array.');
            return self::FAILURE;
        }

        if (App::environment($allowedEnvs) === false) {
            $this->error(sprintf(
                'Scenarios are not allowed in "%s" environment.',
                App::environment(),
            ));
            return self::FAILURE;
        }

        return $this->executeCommand();
    }

    final protected function getBlueprint(string $name): string
    {
        return App::basePath(
            'vendor' . DIRECTORY_SEPARATOR .
            'scenario' . DIRECTORY_SEPARATOR .
            'laravel' . DIRECTORY_SEPARATOR .
            'blueprint' . DIRECTORY_SEPARATOR .
            $name,
        );
    }

    final protected function getCliPath(): string
    {
        return App::basePath(
            'vendor' . DIRECTORY_SEPARATOR .
            'bin' . DIRECTORY_SEPARATOR .
            'scenario',
        );
    }

    abstract protected function executeCommand(): int;
}
