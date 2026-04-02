<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Tests\Unit\Runtime;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Scenario\Core\Attribute\RefreshDatabase;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Scenario\Core\Runtime\Application\Configuration\Value\ConnectionValue;
use Scenario\Laravel\Runtime\DatabaseRefreshExecutor;

#[CoversClass(DatabaseRefreshExecutor::class)]
#[UsesClass(RefreshDatabase::class)]
#[UsesClass(Application::class)]
#[UsesClass(DefaultConfiguration::class)]
#[UsesClass(LoadedConfiguration::class)]
#[UsesClass(ConnectionValue::class)]
#[Group('runtime')]
#[Small]
final class DatabaseRefreshExecutorTest extends TestCase
{
    private LaravelApplication $app;

    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
        $this->app = new LaravelApplication('/app');
        Facade::setFacadeApplication($this->app);
    }

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

        $kernel = Mockery::mock(ConsoleKernelContract::class);
        /** @var ConsoleKernelContract&MockInterface $kernel */
        $kernel = $kernel;

        /** @var Expectation $expectation */
        $expectation = $kernel->shouldReceive('call');
        $expectation->once()->with('migrate:fresh', [
            '--force' => true,
            '--database' => 'main',
            '--path' => 'database/main.php',
        ]);

        $this->app->instance(ConsoleKernelContract::class, $kernel);

        (new DatabaseRefreshExecutor())->execute(new RefreshDatabase('main'));

        self::addToAssertionCount(1);
    }

    public function testExecuteCallsMigrateFreshWithoutOptionalParametersWhenConnectionIsMissing(): void
    {
        /** @var ConsoleKernelContract&MockInterface $kernel */
        $kernel = Mockery::mock(ConsoleKernelContract::class);

        /** @var Expectation $expectation */
        $expectation = $kernel->shouldReceive('call');
        $expectation->once()->with('migrate:fresh', [
            '--force' => true,
        ]);

        $this->app->instance(ConsoleKernelContract::class, $kernel);

        (new DatabaseRefreshExecutor())->execute(new RefreshDatabase(null));

        self::addToAssertionCount(1);
    }

    private function setConfiguration(LoadedConfiguration $configuration): void
    {
        $configProperty = (new ReflectionClass(Application::class))->getProperty('configuration');
        $configProperty->setValue(null, $configuration);
    }
}
