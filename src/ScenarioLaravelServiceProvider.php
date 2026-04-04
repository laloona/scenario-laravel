<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Stateforge\Scenario\Core\PHPUnit\Configuration\Configured;
use Stateforge\Scenario\Core\PHPUnit\Configuration\ConfiguredInterface;
use Stateforge\Scenario\Laravel\Command\ScenarioApplyCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioDebugCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioInstallCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioListCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioMakeCommand;
use Stateforge\Scenario\Laravel\Runtime\Consumer;
use Stateforge\Scenario\Laravel\Runtime\ProcessInterface;
use Stateforge\Scenario\Laravel\Runtime\ProcessRunner;
use function config_path;
use const DIRECTORY_SEPARATOR;

final class ScenarioLaravelServiceProvider extends ServiceProvider
{
    private function getConfig(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR .
            '..' . DIRECTORY_SEPARATOR .
            'config'. DIRECTORY_SEPARATOR .
            'scenario.php';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->getConfig(),
            'scenario',
        );

        $this->app->singleton(ConfiguredInterface::class, Configured::class);
        $this->app->singleton(Consumer::class, Consumer::class);
        $this->app->singleton(ProcessInterface::class, ProcessRunner::class);
    }

    public function boot(): void
    {
        $this->publishes([
           $this->getConfig() => config_path('scenario.php'),
        ], 'scenario-config');

        if ($this->app->runningInConsole() === false) {
            return;
        }

        /** @var array<string>|string $allowedEnvs */
        $allowedEnvs = Config::get('scenario.allowed_envs', ['local', 'develop', 'testing']);
        if ($this->app->environment($allowedEnvs) === false) {
            return;
        }

        $this->commands([
            ScenarioInstallCommand::class,
            ScenarioApplyCommand::class,
            ScenarioDebugCommand::class,
            ScenarioListCommand::class,
            ScenarioMakeCommand::class,
        ]);
    }
}
