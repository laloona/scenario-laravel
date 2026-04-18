<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Runtime;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\ConnectionValue;
use Stateforge\Scenario\Laravel\Runtime\DatabaseRefreshExecutor;

#[CoversClass(DatabaseRefreshExecutor::class)]
#[Group('runtime')]
#[Small]
final class DatabaseRefreshExecutorTest extends TestCase
{
    protected function tearDown(): void
    {
        $configProperty = (new ReflectionClass(Application::class))->getProperty('configuration');
        $configProperty->setValue(null, null);
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testExecuteCallsMigrateFreshWithConfiguredConnectionAndPath(): void
    {
        $configuration = new LoadedConfiguration(new DefaultConfiguration());
        $configuration->setConnections([
            'main' => new ConnectionValue('main', 'database/main.php'),
        ]);
        $this->setConfiguration($configuration);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', [
            '--force' => true,
            '--database' => 'main',
            '--path' => 'database/main.php',
        ]);

        (new DatabaseRefreshExecutor())->execute(new RefreshDatabase('main'));

        self::addToAssertionCount(1);
    }

    public function testExecuteCallsMigrateFreshWithoutOptionalParametersWhenConnectionIsMissing(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', [
            '--force' => true,
        ]);

        (new DatabaseRefreshExecutor())->execute(new RefreshDatabase(null));

        self::addToAssertionCount(1);
    }

    private function setConfiguration(LoadedConfiguration $configuration): void
    {
        $configProperty = (new ReflectionClass(Application::class))->getProperty('configuration');
        $configProperty->setValue(null, $configuration);
    }
}
