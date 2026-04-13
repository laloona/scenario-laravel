<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Runtime;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application as IlluminateApplication;
use Illuminate\Support\Facades\App;
use Stateforge\Scenario\Core\Runtime\Application as CoreApplication;
use Stateforge\Scenario\Core\Runtime\ApplicationExtension;
use Stateforge\Scenario\Core\Runtime\Metadata\Handler\ApplyScenarioHandler;
use Stateforge\Scenario\Core\Runtime\Metadata\Handler\RefreshDatabaseHandler;
use Stateforge\Scenario\Core\Runtime\Metadata\HandlerRegistry;
use function define;
use function defined;
use const DIRECTORY_SEPARATOR;

final class Application extends ApplicationExtension
{
    public function prepare(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false) {
            define('SCENARIO_CLI_DISABLED', true);
        }

        if (CoreApplication::config() === null) {
            return;
        }

        CoreApplication::config()->addParameterDirectory(
            'vendor' . DIRECTORY_SEPARATOR .
            'stateforge' . DIRECTORY_SEPARATOR .
            'scenario-laravel' . DIRECTORY_SEPARATOR .
            'src' . DIRECTORY_SEPARATOR . 'Parameter',
        );
    }

    public function boot(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false
            || CoreApplication::config() === null) {
            return;
        }

        /** @var IlluminateApplication $app */
        $app = require CoreApplication::getRootDir() . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

        /** @var Kernel $kernel */
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();

        /** @var DatabaseRefreshExecutor $refreshExecutor */
        $refreshExecutor = App::make(DatabaseRefreshExecutor::class);

        /** @var StateBuilder $scenarioBuilder */
        $scenarioBuilder = App::make(StateBuilder::class);

        HandlerRegistry::getInstance()->registerHandler(new RefreshDatabaseHandler($refreshExecutor));
        HandlerRegistry::getInstance()->registerHandler(new ApplyScenarioHandler($scenarioBuilder));
    }
}
