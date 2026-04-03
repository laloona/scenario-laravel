<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Tests\Unit\Command;

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
use Scenario\Core\PHPUnit\Configuration\ConfiguredInterface;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Scenario\Laravel\Command\ScenarioCommand;
use Scenario\Laravel\Command\ScenarioInstallCommand;
use Scenario\Laravel\Tests\Unit\CommandMock;
use Scenario\Laravel\Tests\Unit\LaravelMock;
use Symfony\Component\Console\Tester\CommandTester;
use const PHP_BINARY;

#[CoversClass(ScenarioInstallCommand::class)]
#[UsesClass(Application::class)]
#[UsesClass(DefaultConfiguration::class)]
#[UsesClass(LoadedConfiguration::class)]
#[UsesClass(ScenarioCommand::class)]
#[Group('command')]
#[Medium]
final class ScenarioInstallCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

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
            configurePhpUnit: true,
        );

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
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
            configurePhpUnit: false,
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
        bool $configurePhpUnit,
    ): void {
        /** @var Filesystem&MockInterface $filesystem */
        $filesystem = Mockery::mock(Filesystem::class);
        $this->app->instance('files', $filesystem);

        $this->basePathMock('/app');
        $this->commandMocks();

        $bootstrapBlueprint = 'vendor/scenario/laravel/blueprint/bootstrap.blueprint';
        $configBlueprint = 'vendor/scenario/laravel/blueprint/config.blueprint';
        $bootstrapTarget = 'scenario/bootstrap.php';
        $configTarget = 'scenario.dist.xml';

        $bootstrapInstalled = false;
        $configInstalled = false;
        $scenarioXmlCalls = 0;
        $scenarioDistXmlCalls = 0;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->withArgs(static fn (string $path): bool => true)
            ->andReturnUsing(static function (string $path) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
                &$bootstrapInstalled,
                &$configInstalled,
                &$scenarioXmlCalls,
                &$scenarioDistXmlCalls,
                $scenarioXmlExistsAfterInstall,
                $scenarioDistExistsAfterInstall,
            ): bool {
                return match ($path) {
                    'scenario.xml' => $scenarioXmlExistsAfterInstall
                        ? match (++$scenarioXmlCalls) {
                            1, 2 => false,
                            default => true,
                        }
                    : false,

                    'scenario.dist.xml' => match (++$scenarioDistXmlCalls) {
                        1, 2 => false,
                        default => $scenarioDistExistsAfterInstall ? $configInstalled : false,
                    },

                    $bootstrapBlueprint, $configBlueprint => true,
                    $bootstrapTarget => $bootstrapInstalled,
                    $configTarget => $configInstalled,
                    default => false,
                };
            });

        /** @var Expectation $copy */
        $copy = $filesystem->shouldReceive('copy');
        $copy->twice()
            ->withArgs(static function (string $source, string $target) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
                &$bootstrapInstalled,
                &$configInstalled,
            ): bool {
                if ($source === $bootstrapBlueprint && $target === $bootstrapTarget) {
                    $bootstrapInstalled = true;
                    return true;
                }

                if ($source === $configBlueprint && $target === $configTarget) {
                    $configInstalled = true;
                    return true;
                }

                return false;
            });

        /** @var Expectation $ensureScenario */
        $ensureScenario = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureScenario->once()
            ->with('scenario');

        /** @var Expectation $ensureMain */
        $ensureMain = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureMain->once()
            ->with('scenario/main');
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
