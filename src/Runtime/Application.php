<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Runtime;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application as IlluminateApplication;
use Illuminate\Support\Facades\App;
use Scenario\Core\Runtime\Application as CoreApplication;
use Scenario\Core\Runtime\Metadata\Handler\ApplyScenarioHandler;
use Scenario\Core\Runtime\Metadata\Handler\RefreshDatabaseHandler;
use Scenario\Core\Runtime\Metadata\HandlerRegistry;
use function define;
use function defined;
use const DIRECTORY_SEPARATOR;

final class Application
{
    public function bootstrap(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false) {
            define('SCENARIO_CLI_DISABLED', true);
        }

        // core kernel is not prepared, this file was loaded by file scan
        if (CoreApplication::config() === null) {
            return;
        }

        /** @var IlluminateApplication $app */
        $app = require CoreApplication::getRootDir() . DIRECTORY_SEPARATOR . 'bootstrap/app.php';

        /** @var Kernel $kernel */
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();

        /** @var DatabaseRefreshExecutor $refreshExecutor */
        $refreshExecutor = App::make(DatabaseRefreshExecutor::class);

        /** @var ScenarioBuilder $scenarioBuilder */
        $scenarioBuilder = App::make(ScenarioBuilder::class);

        HandlerRegistry::getInstance()->registerHandler(new RefreshDatabaseHandler($refreshExecutor));
        HandlerRegistry::getInstance()->registerHandler(new ApplyScenarioHandler($scenarioBuilder));
    }
}
