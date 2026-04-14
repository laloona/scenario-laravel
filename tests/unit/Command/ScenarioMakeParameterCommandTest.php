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
use Stateforge\Scenario\Laravel\Command\ScenarioMakeParameterCommand;
use Stateforge\Scenario\Laravel\Tests\Unit\CommandMock;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;
use Stateforge\Scenario\Laravel\Tests\Unit\PathHelper;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(ScenarioMakeParameterCommand::class)]
#[CoversClass(ScenarioMakeCommand::class)]
#[UsesClass(ScenarioCommand::class)]
#[Group('command')]
#[Medium]
final class ScenarioMakeParameterCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;
    use PathHelper;

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
        $command = new ScenarioMakeParameterCommand();

        self::assertSame('scenario:make:parameter', $command->getName());
        self::assertSame('Make a parameter type - should only be used for local/develop/testing', $command->getDescription());
        self::assertFalse($command->isHidden());
    }

    public function testExecuteGeneratesParameterTypeFileFromBlueprint(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/parameter.blueprint';
        $parameterFile = 'scenario/parameter/DemoParameter.php';
        $parameterExists = false;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $parameterFile, &$parameterExists): bool {
                if ($this->pathEndsWith($path, $blueprint)) {
                    return true;
                }

                if ($this->pathEndsWith($path, $parameterFile)) {
                    return $parameterExists;
                }

                return false;
            });

        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, $blueprint))
            ->andReturn(<<<'PHP'
<?php

namespace Stateforge\Parameter\%nameSpace%;

final class %className%
{
}
PHP);

        /** @var Expectation $ensureDirectoryExists */
        $ensureDirectoryExists = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureDirectoryExists->once();

        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->withArgs(function (string $path, string $content) use ($parameterFile, &$parameterExists): bool {
                if (! $this->pathEndsWith($path, $parameterFile)) {
                    return false;
                }

                $parameterExists = true;
                self::assertStringContainsString('namespace Stateforge\\Parameter\\Scenario\\Parameter;', $content);
                self::assertStringContainsString('final class DemoParameter', $content);

                return true;
            });

        $command = new ScenarioMakeParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['demoParameter']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('DemoParameter.php', $tester->getDisplay());
    }

    public function testExecuteFailsWhenBlueprintDoesNotExist(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/parameter.blueprint';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, $blueprint))
            ->andReturn(false);

        /** @var Expectation $ensureDirectoryExists */
        $ensureDirectoryExists = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureDirectoryExists->never();

        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->never();

        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->never();

        $command = new ScenarioMakeParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Parameter type generation failed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenParameterTypeAlreadyExists(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/parameter.blueprint';
        $parameterFile = 'scenario/parameter/ExistingParameter.php';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(2)
            ->andReturnUsing(function (string $path) use ($blueprint, $parameterFile): bool {
                if ($this->pathEndsWith($path, $blueprint)) {
                    return true;
                }

                if ($this->pathEndsWith($path, $parameterFile)) {
                    return true;
                }

                return false;
            });

        /** @var Expectation $ensureDirectoryExists */
        $ensureDirectoryExists = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureDirectoryExists->never();

        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->never();

        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->never();

        $command = new ScenarioMakeParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['existingParameter']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Parameter type already exists.', $tester->getDisplay());
    }

    public function testExecuteRepeatsQuestionUntilParameterTypeNameIsValid(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/parameter.blueprint';
        $parameterFile = 'scenario/parameter/ValidParameter.php';
        $parameterExists = false;

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $parameterFile, &$parameterExists): bool {
                if ($this->pathEndsWith($path, $blueprint)) {
                    return true;
                }

                if ($this->pathEndsWith($path, $parameterFile)) {
                    return $parameterExists;
                }

                return false;
            });

        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, $blueprint))
            ->andReturn('<?php final class %className% {}');

        /** @var Expectation $ensureDirectoryExists */
        $ensureDirectoryExists = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureDirectoryExists->once();

        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->withArgs(function (string $path, string $content) use ($parameterFile, &$parameterExists): bool {
                if (! $this->pathEndsWith($path, $parameterFile)) {
                    return false;
                }

                $parameterExists = true;
                self::assertStringContainsString('final class ValidParameter', $content);

                return true;
            });

        $command = new ScenarioMakeParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['bad name!', 'validParameter']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Input was invalid, please try again.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenGeneratedParameterTypeFileCannotBeVerified(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/parameter.blueprint';
        $parameterFile = 'scenario/parameter/BrokenParameter.php';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->times(3)
            ->andReturnUsing(function (string $path) use ($blueprint, $parameterFile): bool {
                if ($this->pathEndsWith($path, $blueprint)) {
                    return true;
                }

                if ($this->pathEndsWith($path, $parameterFile)) {
                    return false;
                }

                return false;
            });

        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, $blueprint))
            ->andReturn('<?php final class %className% {}');

        /** @var Expectation $ensureDirectoryExists */
        $ensureDirectoryExists = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureDirectoryExists->once();

        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->once()
            ->withArgs(fn (string $path, string $content): bool => $this->pathEndsWith($path, $parameterFile));

        $command = new ScenarioMakeParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['brokenParameter']);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Parameter type generation failed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenParameterTypeNameWasNotProvided(): void
    {
        $filesystem = $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $blueprint = 'vendor/stateforge/scenario-laravel/blueprint/parameter.blueprint';

        /** @var Expectation $exists */
        $exists = $filesystem->shouldReceive('exists');
        $exists->once()
            ->withArgs(fn (string $path): bool => $this->pathEndsWith($path, $blueprint))
            ->andReturn(true);

        /** @var Expectation $ensureDirectoryExists */
        $ensureDirectoryExists = $filesystem->shouldReceive('ensureDirectoryExists');
        $ensureDirectoryExists->never();

        /** @var Expectation $get */
        $get = $filesystem->shouldReceive('get');
        $get->never();

        /** @var Expectation $put */
        $put = $filesystem->shouldReceive('put');
        $put->never();

        $command = new ScenarioMakeParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => false]));
        self::assertStringContainsString('Parameter type generation failed.', $tester->getDisplay());
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

        $command = new ScenarioMakeParameterCommand();
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
