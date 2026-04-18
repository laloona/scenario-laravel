<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Command;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Core\PHPUnit\Configuration\ConfiguredInterface;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Stateforge\Scenario\Laravel\Command\ScenarioCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioInstallCommand;
use Stateforge\Scenario\Laravel\Tests\Unit\CommandMock;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;
use Stateforge\Scenario\Laravel\Tests\Unit\PathHelper;
use Symfony\Component\Console\Tester\CommandTester;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

#[CoversClass(ScenarioInstallCommand::class)]
#[UsesClass(ScenarioCommand::class)]
#[Group('command')]
#[Medium]
final class ScenarioInstallCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;
    use PathHelper;

    protected function setUp(): void
    {
        $this->setScenarioConfiguration(new LoadedConfiguration(new DefaultConfiguration()));
        $this->setUpFacades();
    }

    protected function tearDown(): void
    {
        $this->tearDownFacades();
        $this->setScenarioConfiguration(null);
        $this->resetApplicationBootState();
    }

    public function testCommandIsConfigured(): void
    {
        $this->setUpInstalled(false, 4);
        $command = new ScenarioInstallCommand(self::createStub(ConfiguredInterface::class));

        self::assertSame('scenario:install', $command->getName());
        self::assertSame('Install the Scenario Package (local/develop/testing only)', $command->getDescription());
        self::assertFalse($command->isHidden());
    }

    public function testExecuteFailsWhenScenarioIsAlreadyInstalled(): void
    {
        $this->setUpInstalled(true, 4);
        $this->basePathMock('/app');
        $this->commandMocks();

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::never())
            ->method('isConfigured');

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertTrue($command->isHidden());
        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario is already installed.', $tester->getDisplay());
    }

    public function testExecuteAbortsWhenUserDeclinesInstallation(): void
    {
        $this->setUpInstalled(false, 4);
        $this->basePathMock('/app');
        $this->commandMocks();

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::never())
            ->method('isConfigured');

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['no']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario installation aborted.', $tester->getDisplay());
    }

    public function testExecuteInstallsBlueprintsAndConfiguresPhpUnit(): void
    {
        $this->setUpInstallScenario(
            scenarioXmlExistsAfterInstall: true,
            scenarioDistExistsAfterInstall: true,
        );

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
                    'install',
                    '--force',
                    '--quiet',
                ],
                '/app',
                null,
            )
            ->andReturn(true);

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::exactly(2))
            ->method('isConfigured')
            ->willReturnOnConsecutiveCalls(false, true);

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['yes', 'yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario was successfully installed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenConfigFileDoesNotExistAfterInstall(): void
    {
        $this->setUpInstallScenario(
            scenarioXmlExistsAfterInstall: false,
            scenarioDistExistsAfterInstall: false,
        );

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::never())
            ->method('isConfigured');

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario installation failed.', $tester->getDisplay());
    }

    public function testExecuteCommandReturnsFailureWhenScenarioIsInstalled(): void
    {
        $this->setUpInstalled(true, 3);
        $this->basePathMock('/app');
        $this->commandMocks();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioInstallCommand(self::createStub(ConfiguredInterface::class));
        $command->setLaravel($this->getLaravelMock());

        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
    }

    private function setUpInstallScenario(
        bool $scenarioXmlExistsAfterInstall,
        bool $scenarioDistExistsAfterInstall,
    ): void {
        /** @var Filesystem&MockInterface $filesystem */
        $filesystem = Mockery::mock(Filesystem::class);
        $this->app->instance('files', $filesystem);

        $this->basePathMock('/app');
        $this->commandMocks();

        $bootstrapInstalled = false;
        $configInstalled = false;
        $scenarioXmlCalls = 0;
        $scenarioDistXmlCalls = 0;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->withArgs(fn (string $path): bool => true)
            ->andReturnUsing(function (string $path) use (
                &$bootstrapInstalled,
                &$configInstalled,
                &$scenarioXmlCalls,
                &$scenarioDistXmlCalls,
                $scenarioXmlExistsAfterInstall,
                $scenarioDistExistsAfterInstall,
            ): bool {
                if ($this->pathEndsWith($path, 'scenario.xml')) {
                    return $scenarioXmlExistsAfterInstall
                        ? match (++$scenarioXmlCalls) {
                            1, 2 => false,
                            default => true,
                        }
                    : false;
                }

                if ($this->pathEndsWith($path, 'scenario.dist.xml')) {
                    return match (++$scenarioDistXmlCalls) {
                        1, 2 => false,
                        default => $scenarioDistExistsAfterInstall ? $configInstalled : false,
                    };
                }

                if ($this->pathEndsWith($path, 'vendor/stateforge/scenario-laravel/blueprint/bootstrap.blueprint')) {
                    return true;
                }

                if ($this->pathEndsWith($path, 'vendor/stateforge/scenario-laravel/blueprint/config.blueprint')) {
                    return true;
                }

                if ($this->pathEndsWith($path, 'scenario/bootstrap.php')) {
                    return $bootstrapInstalled;
                }

                return false;
            });

        /** @var Expectation $copy */
        $copy = $filesystem->shouldReceive('copy');
        $copy->twice()
            ->withArgs(function (string $source, string $target) use (
                &$bootstrapInstalled,
                &$configInstalled,
            ): bool {
                if (
                    $this->pathEndsWith($source, 'vendor/stateforge/scenario-laravel/blueprint/bootstrap.blueprint')
                    && $this->pathEndsWith($target, 'scenario/bootstrap.php')
                ) {
                    $bootstrapInstalled = true;
                    return true;
                }

                if (
                    $this->pathEndsWith($source, 'vendor/stateforge/scenario-laravel/blueprint/config.blueprint')
                    && $this->pathEndsWith($target, 'scenario.dist.xml')
                ) {
                    $configInstalled = true;
                    return true;
                }

                return false;
            });

        /** @var Expectation $ensureScenario */
        $ensureScenario = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureScenario->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, 'scenario'));

        /** @var Expectation $ensureMain */
        $ensureMain = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureMain->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, 'scenario/main'));
    }

    private function resetApplicationBootState(): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('isBooted');
        $property->setValue(null, false);
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }
}
