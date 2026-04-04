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
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Stateforge\Scenario\Laravel\Command\ScenarioCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioMakeCommand;
use Stateforge\Scenario\Laravel\Tests\Unit\CommandMock;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;
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
        $this->setUpFacades();
        $configuration = new LoadedConfiguration(new DefaultConfiguration());
        $configuration->setSuites([
            'main' => new SuiteValue('main', 'scenario/main'),
        ]);
        $this->setScenarioConfiguration($configuration);
    }

    protected function tearDown(): void
    {
        $this->tearDownFacades();
        $this->setScenarioConfiguration(null);
    }

    public function testCommandIsConfigured(): void
    {
        $this->setUpInstalled(true, 2);
        $command = new ScenarioMakeCommand();

        self::assertSame('scenario:make', $command->getName());
        self::assertSame('Make a scenario - should only be used for local/develop/testing', $command->getDescription());
        self::assertFalse($command->isHidden());
    }

    public function testExecuteGeneratesScenarioFileFromBlueprint(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';
        $scenarioFile = 'scenario/main/DemoScenario.php';
        $scenarioExists = false;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->with($blueprint)
            ->andReturn(<<<'PHP'
<?php

namespace Stateforge\Suite\%nameSpace%;

final class %className%
{
}
PHP);
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->with(
                $scenarioFile,
                Mockery::on(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('namespace Stateforge\\Suite\\Scenario\\Main;', $content);
                    self::assertStringContainsString('final class DemoScenario', $content);
                    return true;
                }),
            );

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Scenario "' . $scenarioFile . '" generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenBlueprintDoesNotExist(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->once()
            ->with($blueprint)
            ->andReturn(false);
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->never();
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenScenarioAlreadyExists(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';
        $scenarioFile = 'scenario/main/ExistingScenario.php';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(2)
            ->andReturnUsing(static function (string $path) use ($blueprint, $scenarioFile): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => true,
                    default => false,
                };
            });
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->never();
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['existingScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario already exists.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenNoSuitesAreConfigured(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')
            ->willReturn([]);
        $this->setScenarioConfiguration($configuration);

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->once()
            ->with($blueprint)
            ->andReturn(true);
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->never();
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Application configuration not found.', $tester->getDisplay());
    }

    public function testExecuteRepeatsQuestionUntilScenarioNameIsValid(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';
        $scenarioFile = 'scenario/main/CleanScenario.php';
        $scenarioExists = false;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->with($blueprint)
            ->andReturn('<?php final class %className% {}');
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->with(
                $scenarioFile,
                Mockery::on(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('final class CleanScenario', $content);
                    return true;
                }),
            );

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['bad name!', 'cleanScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Input was invalid, please try again.', $tester->getDisplay());
    }

    public function testExecuteGeneratesScenarioInSelectedSuite(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $configuration = new LoadedConfiguration(new DefaultConfiguration());
        $configuration->setSuites([
            'main' => new SuiteValue('main', 'scenario/main'),
            'admin' => new SuiteValue('admin', 'scenario/admin/user'),
        ]);
        $this->setScenarioConfiguration($configuration);

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';
        $scenarioFile = 'scenario/admin/user/BackofficeScenario.php';
        $scenarioExists = false;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->with($blueprint)
            ->andReturn('<?php namespace Stateforge\\Suite\\%nameSpace%; final class %className% {}');
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->with(
                $scenarioFile,
                Mockery::on(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('namespace Stateforge\\Suite\\Scenario\\Admin\\User;', $content);
                    self::assertStringContainsString('final class BackofficeScenario', $content);
                    return true;
                }),
            );

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['admin', 'backofficeScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Scenario "' . $scenarioFile . '" generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenGeneratedScenarioFileCannotBeVerified(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/scenario.blueprint';
        $scenarioFile = 'scenario/main/DemoScenario.php';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(static function (string $path) use ($blueprint, $scenarioFile): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => false,
                    default => false,
                };
            });
        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->with($blueprint)
            ->andReturn('<?php final class Stateforge\\Suite\\%className% {}');
        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->with($scenarioFile, Mockery::type('string'));

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    public function testExecuteCommandReturnsFailureWhenScenarioIsNotInstalled(): void
    {
        $this->setUpInstalled(false, 8);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioMakeCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertTrue($command->isHidden());
        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }

}
