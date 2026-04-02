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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Mockery;
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
use Scenario\Laravel\Facades\Shell;
use Scenario\Laravel\Tests\Unit\CommandMock;
use Scenario\Laravel\Tests\Unit\LaravelMock;
use Symfony\Component\Console\Tester\CommandTester;
use const DIRECTORY_SEPARATOR;
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->setScenarioConfiguration(null);
        $this->resetApplicationBootState();
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioInstallCommand(self::createStub(ConfiguredInterface::class));

        self::assertSame('scenario:install', $command->getName());
        self::assertSame('Install the Scenario Package (dev/test only)', $command->getDescription());
    }

    public function testExecuteFailsWhenScenarioIsAlreadyInstalled(): void
    {
        $this->commandMocks();

        App::shouldReceive('basePath')
            ->once()
            ->with('scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php')
            ->andReturn('/app/scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::once())
            ->method('isConfigured')
            ->willReturn(true);

        File::shouldReceive('exists')
            ->once()
            ->with('/app/scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php')
            ->andReturn(true);

        Shell::shouldReceive('run')->never();

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario is already installed.', $tester->getDisplay());
    }

    public function testExecuteAbortsWhenUserDeclinesInstallation(): void
    {
        $this->commandMocks();

        App::shouldReceive('basePath')
            ->once()
            ->with('scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php')
            ->andReturn('/app/scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php');

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::atMost(1))
            ->method('isConfigured');

        File::shouldReceive('exists')
            ->once()
            ->with('/app/scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php')
            ->andReturn(false);

        Shell::shouldReceive('run')->never();

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['no']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Installation aborted.', $tester->getDisplay());
    }

    public function testExecuteInstallsBlueprintsAndConfiguresPhpUnit(): void
    {
        $this->commandMocks();

        App::shouldReceive('basePath')
            ->andReturnUsing(static function (?string $path = null): string {
                if ($path === null || $path === '') {
                    return '/app';
                }

                return '/app/' . $path;
            });

        $bootstrapBlueprint = '/app/vendor/scenario/laravel/blueprint/bootstrap.blueprint';
        $configBlueprint = '/app/vendor/scenario/laravel/blueprint/config.blueprint';
        $bootstrapTarget = '/app/scenario/bootstrap.php';
        $configTarget = '/app/scenario.dist.xml';
        $bootstrapInstalled = false;

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::exactly(3))
            ->method('isConfigured')
            ->willReturnOnConsecutiveCalls(false, true, true);

        File::shouldReceive('exists')
            ->times(6)
            ->andReturnUsing(static function (string $path) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
                &$bootstrapInstalled,
            ): bool {
                return match ($path) {
                    $bootstrapTarget => $bootstrapInstalled,
                    $bootstrapBlueprint, $configBlueprint => true,
                    $configTarget => false,
                    default => false,
                };
            });
        File::shouldReceive('copy')
            ->twice()
            ->withArgs(static function (string $source, string $target) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
                &$bootstrapInstalled,
            ): bool {
                if ($source === $bootstrapBlueprint && $target === $bootstrapTarget) {
                    $bootstrapInstalled = true;
                    return true;
                }

                return $source === $configBlueprint && $target === $configTarget;
            });
        File::shouldReceive('delete')->never();
        File::shouldReceive('ensureDirectoryExists')
            ->once()
            ->with('/app/scenario/main');

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    '/app/vendor/bin/scenario',
                    'install',
                    '--force',
                    '--quiet',
                ],
                '/app',
                null,
            )
            ->andReturn(true);

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['yes', 'yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario was successfully installed.', $tester->getDisplay());
    }

    public function testExecuteDeletesExistingConfigTargetBeforeCopyingBlueprint(): void
    {
        $this->commandMocks();

        App::shouldReceive('basePath')
            ->andReturnUsing(static function (?string $path = null): string {
                if ($path === null || $path === '') {
                    return '/app';
                }

                return '/app/' . $path;
            });

        $bootstrapBlueprint = '/app/vendor/scenario/laravel/blueprint/bootstrap.blueprint';
        $configBlueprint = '/app/vendor/scenario/laravel/blueprint/config.blueprint';
        $bootstrapTarget = '/app/scenario/bootstrap.php';
        $configTarget = '/app/scenario.dist.xml';
        $bootstrapInstalled = false;

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::exactly(2))
            ->method('isConfigured')
            ->willReturn(true);

        File::shouldReceive('exists')
            ->times(6)
            ->andReturnUsing(static function (string $path) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
                &$bootstrapInstalled,
            ): bool {
                return match ($path) {
                    $bootstrapTarget => $bootstrapInstalled,
                    $bootstrapBlueprint, $configBlueprint, $configTarget => true,
                    default => false,
                };
            });
        File::shouldReceive('copy')
            ->twice()
            ->withArgs(static function (string $source, string $target) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
                &$bootstrapInstalled,
            ): bool {
                if ($source === $bootstrapBlueprint && $target === $bootstrapTarget) {
                    $bootstrapInstalled = true;
                    return true;
                }

                return $source === $configBlueprint && $target === $configTarget;
            });
        File::shouldReceive('delete')
            ->once()
            ->with($configTarget);
        File::shouldReceive('ensureDirectoryExists')
            ->once()
            ->with('/app/scenario/main');

        Shell::shouldReceive('run')->never();

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario was successfully installed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenBootstrapBlueprintIsMissingAndPhpUnitConfigurationStaysInvalid(): void
    {
        $this->commandMocks();

        App::shouldReceive('basePath')
            ->andReturnUsing(static function (?string $path = null): string {
                if ($path === null || $path === '') {
                    return '/app';
                }

                return '/app/' . $path;
            });

        $bootstrapBlueprint = '/app/vendor/scenario/laravel/blueprint/bootstrap.blueprint';
        $configBlueprint = '/app/vendor/scenario/laravel/blueprint/config.blueprint';
        $bootstrapTarget = '/app/scenario/bootstrap.php';
        $configTarget = '/app/scenario.dist.xml';

        $configured = self::createMock(ConfiguredInterface::class);
        $configured->expects(self::exactly(2))
            ->method('isConfigured')
            ->willReturn(false);

        File::shouldReceive('exists')
            ->times(5)
            ->andReturnUsing(static function (string $path) use (
                $bootstrapBlueprint,
                $configBlueprint,
                $bootstrapTarget,
                $configTarget,
            ): bool {
                return match ($path) {
                    $bootstrapBlueprint, $bootstrapTarget => false,
                    $configBlueprint => true,
                    $configTarget => false,
                    default => false,
                };
            });
        File::shouldReceive('copy')
            ->once()
            ->with($configBlueprint, $configTarget);
        File::shouldReceive('delete')->never();
        File::shouldReceive('ensureDirectoryExists')
            ->once()
            ->with('/app/scenario/main');

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    '/app/vendor/bin/scenario',
                    'install',
                    '--force',
                    '--quiet',
                ],
                '/app',
                null,
            )
            ->andReturn(true);

        $command = new ScenarioInstallCommand($configured);
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['yes', 'yes']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Configuring PHPUnit failed.', $tester->getDisplay());
        self::assertStringContainsString('Installation failed.', $tester->getDisplay());
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
