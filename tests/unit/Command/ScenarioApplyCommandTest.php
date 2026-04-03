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
use Illuminate\Console\OutputStyle;
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Scenario\Core\Attribute\AsScenario;
use Scenario\Core\Attribute\Parameter;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Scenario\Core\Runtime\Exception\RegistryException;
use Scenario\Core\Runtime\Metadata\ExecutionType;
use Scenario\Core\Runtime\Metadata\ParameterType;
use Scenario\Core\Runtime\ScenarioDefinition;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Laravel\Command\ScenarioApplyCommand;
use Scenario\Laravel\Command\ScenarioCommand;
use Scenario\Laravel\Tests\Files\ValidScenario;
use Scenario\Laravel\Tests\Unit\CommandMock;
use Scenario\Laravel\Tests\Unit\LaravelMock;
use Symfony\Component\Console\Tester\CommandTester;
use const PHP_BINARY;

#[CoversClass(ScenarioApplyCommand::class)]
#[UsesClass(ExecutionType::class)]
#[UsesClass(Parameter::class)]
#[UsesClass(ParameterType::class)]
#[UsesClass(RegistryException::class)]
#[UsesClass(ScenarioCommand::class)]
#[UsesClass(ScenarioDefinition::class)]
#[UsesClass(ScenarioRegistry::class)]
#[Group('command')]
#[Medium]
final class ScenarioApplyCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

    protected function setUp(): void
    {
        $this->setScenarioConfiguration(self::createStub(Configuration::class));
        ScenarioRegistry::getInstance()->clear();
        $this->setUpFacades();
    }

    protected function tearDown(): void
    {
        $this->tearDownFacades();
        ScenarioRegistry::getInstance()->clear();
        $this->setScenarioConfiguration(null);
    }

    public function testCommandIsConfigured(): void
    {
        $this->setUpInstalled(true, 2);
        $command = new ScenarioApplyCommand();

        self::assertSame('scenario:apply', $command->getName());
        self::assertSame(
            'Apply a given scenario, use --up or --down to choose how the scenario should be applied - should only be used for local/develop/testing',
            $command->getDescription(),
        );
        self::assertTrue($command->getDefinition()->hasOption('up'));
        self::assertTrue($command->getDefinition()->hasOption('down'));
        self::assertTrue($command->getDefinition()->hasOption('parameter'));
        self::assertTrue($command->getDefinition()->hasOption('audit'));
        self::assertFalse($command->isHidden());
    }

    public function testExecuteFailsWhenUpAndDownAreUsedTogether(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();
        $this->registerScenario();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([
            'scenario' => 'valid',
            '--up' => true,
            '--down' => true,
        ]));
        self::assertStringContainsString('You can just use either up or down scenarios.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenNoScenariosWereFound(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('No scenarios were found, please create one.', $tester->getDisplay());
    }

    public function testExecuteRunsShellForDirectScenarioWithParametersAuditAndDown(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $this->registerScenario();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--down',
                    '--parameter=myparam=hello',
                    '--parameter=flag=yes',
                    '--audit',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            'scenario' => 'valid',
            '--down' => true,
            '--audit' => true,
            '--parameter' => ['myparam=hello', 'flag=yes'],
        ]));
        self::assertStringContainsString(
            'Scenario "' . ValidScenario::class . '::down" was applied successfully.',
            $tester->getDisplay(),
        );
    }

    public function testExecuteFallsBackToChoiceForUnknownScenarioAndPromptsForParameters(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $this->registerScenario([
            new Parameter('myBool', ParameterType::Boolean, required: true),
            new Parameter('myInts', ParameterType::Integer, required: true, repeatable: true),
        ]);

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--down',
                    '--parameter=myBool=yes',
                    '--parameter=myInts=5',
                    '--parameter=myInts=3',
                    '--audit',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['0', 'yes', '5', 'yes', '3', 'no']);

        self::assertSame(Command::SUCCESS, $tester->execute([
            'scenario' => 'unknown',
            '--down' => true,
            '--audit' => true,
        ], [
            'interactive' => true,
        ]));
        self::assertStringContainsString('Given scenario [unknown] is not registered.', $tester->getDisplay());
    }

    public function testExecuteRepeatsInputWhenRequiredParameterIsInvalid(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $this->registerScenario([
            new Parameter('myInt', ParameterType::Integer, required: true),
        ]);

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--parameter=myInt=5',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['0', 'abc', '5']);

        self::assertSame(Command::SUCCESS, $tester->execute(['scenario' => 'unknown'], ['interactive' => true]));
        self::assertStringContainsString('Input was invalid, please try again.', $tester->getDisplay());
    }

    public function testExecuteRepeatsInputWhenRepeatableParameterValueIsInvalid(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $this->registerScenario([
            new Parameter('myInts', ParameterType::Integer, required: true, repeatable: true),
        ]);

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--parameter=myInts=5',
                    '--parameter=myInts=3',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs(['0', '5', 'yes', 'abc', 'yes', '3', 'no']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Input was invalid, please try again.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenProcessFails(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $this->registerScenario([]);

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(false);

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);
        $tester->setInputs([]);

        self::assertSame(Command::FAILURE, $tester->execute(['scenario' => 'valid'], ['interactive' => true]));
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

        $command = new ScenarioApplyCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertTrue($command->isHidden());
        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
    }

    /**
     * @param list<Parameter> $parameters
     */
    private function registerScenario(array $parameters = []): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            $parameters,
        ));
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }
}
