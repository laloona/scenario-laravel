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

use Illuminate\Support\Facades\File;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Scenario\Laravel\Command\ScenarioCommand;
use Scenario\Laravel\Command\ScenarioMakeCommand;
use Scenario\Laravel\Tests\Unit\CommandMock;
use Scenario\Laravel\Tests\Unit\LaravelMock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ScenarioMakeCommand::class)]
#[UsesClass(ScenarioCommand::class)]
#[UsesClass(Application::class)]
#[UsesClass(DefaultConfiguration::class)]
#[UsesClass(LoadedConfiguration::class)]
#[UsesClass(SuiteValue::class)]
#[Group('command')]
#[Medium]
final class ScenarioMakeCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

    protected function setUp(): void
    {
        $configuration = new LoadedConfiguration(new DefaultConfiguration());
        $configuration->setSuites([
            'main' => new SuiteValue('main', 'scenario/main'),
        ]);
        $this->setScenarioConfiguration($configuration);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->setScenarioConfiguration(null);
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioMakeCommand();

        self::assertSame('scenario:make', $command->getName());
        self::assertSame('Make a scenario - should only be used for local/testing', $command->getDescription());
    }

    public function testExecuteGeneratesScenarioFileFromBlueprint(): void
    {
        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';
        $scenarioFile = '/app/scenario/main/DemoScenario.php';
        $scenarioExists = false;

        $this->commandMocks();

        File::shouldReceive('exists')
            ->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        File::shouldReceive('get')
            ->once()
            ->with($blueprint)
            ->andReturn(<<<'PHP'
<?php

namespace %nameSpace%;

final class %className%
{
}
PHP);
        File::shouldReceive('put')
            ->once()
            ->with(
                $scenarioFile,
                Mockery::on(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('namespace Scenario\\Main;', $content);
                    self::assertStringContainsString('final class DemoScenario', $content);
                    return true;
                }),
            );

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Scenario "' . $scenarioFile . '" generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenBlueprintDoesNotExist(): void
    {
        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';

        $this->commandMocks();

        File::shouldReceive('exists')
            ->once()
            ->with($blueprint)
            ->andReturn(false);
        File::shouldReceive('get')->never();
        File::shouldReceive('put')->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenScenarioAlreadyExists(): void
    {
        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';
        $scenarioFile = '/app/scenario/main/ExistingScenario.php';

        $this->commandMocks();

        File::shouldReceive('exists')
            ->times(2)
            ->andReturnUsing(static function (string $path) use ($blueprint, $scenarioFile): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => true,
                    default => false,
                };
            });
        File::shouldReceive('get')->never();
        File::shouldReceive('put')->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['existingScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario already exists.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenNoSuitesAreConfigured(): void
    {
        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')
            ->willReturn([]);
        $this->setScenarioConfiguration($configuration);

        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';

        $this->commandMocks();

        File::shouldReceive('exists')
            ->once()
            ->with($blueprint)
            ->andReturn(true);
        File::shouldReceive('get')->never();
        File::shouldReceive('put')->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Application configuration not found.', $tester->getDisplay());
    }

    public function testExecuteRepeatsQuestionUntilScenarioNameIsValid(): void
    {
        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';
        $scenarioFile = '/app/scenario/main/CleanScenario.php';
        $scenarioExists = false;

        $this->commandMocks();

        File::shouldReceive('exists')
            ->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        File::shouldReceive('get')
            ->once()
            ->with($blueprint)
            ->andReturn('<?php final class %className% {}');
        File::shouldReceive('put')
            ->once()
            ->with(
                $scenarioFile,
                Mockery::on(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('final class CleanScenario', $content);
                    return true;
                }),
            );

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['bad name!', 'cleanScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Input was invalid, please try again.', $tester->getDisplay());
    }

    public function testExecuteGeneratesScenarioInSelectedSuite(): void
    {
        $configuration = new LoadedConfiguration(new DefaultConfiguration());
        $configuration->setSuites([
            'main' => new SuiteValue('main', 'scenario/main'),
            'admin' => new SuiteValue('admin', 'scenario/admin/user'),
        ]);
        $this->setScenarioConfiguration($configuration);

        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';
        $scenarioFile = '/app/scenario/admin/user/BackofficeScenario.php';
        $scenarioExists = false;

        $this->commandMocks();

        File::shouldReceive('exists')
            ->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        File::shouldReceive('get')
            ->once()
            ->with($blueprint)
            ->andReturn('<?php namespace %nameSpace%; final class %className% {}');
        File::shouldReceive('put')
            ->once()
            ->with(
                $scenarioFile,
                Mockery::on(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('namespace Scenario\\Admin\\User;', $content);
                    self::assertStringContainsString('final class BackofficeScenario', $content);
                    return true;
                }),
            );

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['admin', 'backofficeScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Scenario "' . $scenarioFile . '" generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenGeneratedScenarioFileCannotBeVerified(): void
    {
        $blueprint = '/app/vendor/scenario/laravel/blueprint/scenario.blueprint';
        $scenarioFile = '/app/scenario/main/DemoScenario.php';

        $this->commandMocks();

        File::shouldReceive('exists')
            ->times(3)
            ->andReturnUsing(static function (string $path) use ($blueprint, $scenarioFile): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => false,
                    default => false,
                };
            });
        File::shouldReceive('get')
            ->once()
            ->with($blueprint)
            ->andReturn('<?php final class %className% {}');
        File::shouldReceive('put')
            ->once()
            ->with($scenarioFile, Mockery::type('string'));

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock('/app'));

        $tester = new CommandTester($command);
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }

}
