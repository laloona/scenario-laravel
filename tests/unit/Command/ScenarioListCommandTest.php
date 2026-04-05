<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Laravel\Command\ScenarioCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioListCommand;
use Symfony\Component\Console\Tester\CommandTester;
use const PHP_BINARY;

#[CoversClass(ScenarioCommand::class)]
#[CoversClass(ScenarioListCommand::class)]
#[Group('command')]
#[Small]
final class ScenarioListCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

    protected function setUp(): void
    {
        ScenarioRegistry::getInstance()->clear();
        $this->setUpFacades();
    }

    protected function tearDown(): void
    {
        $this->tearDownFacades();
    }

    public function testCommandIsConfigured(): void
    {
        $this->setUpInstalled(true, 2);
        $command = new ScenarioListCommand();

        self::assertSame('scenario:list', $command->getName());
        self::assertSame(
            'List all available scenarios, use --suite="name of your suite" if you want to see just one suite - should only be used for local/develop/testing',
            $command->getDescription(),
        );
        self::assertFalse($command->isHidden());
    }

    public function testExecuteCommandReturnsFailureWhenAllowedEnvsConfigIsNotArray(): void
    {
        $this->setUpInstalled(true, 1);
        $this->basePathMock('/app/root');

        Config::shouldReceive('get')
            ->once()
            ->with('scenario.allowed_envs', ['local', 'develop', 'testing'])
            ->andReturn('invalid');

        App::shouldReceive('environment')->never();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Configuration key "scenario.allowed_envs" must be an array.', $tester->getDisplay());
    }

    public function testExecuteCommandReturnsFailureWhenEnvironmentIsNotAllowed(): void
    {
        $this->setUpInstalled(true, 1);
        $this->basePathMock('/app/root');

        Config::shouldReceive('get')
            ->once()
            ->with('scenario.allowed_envs', ['local', 'develop', 'testing'])
            ->andReturn(['local', 'develop', 'testing']);

        App::shouldReceive('environment')
            ->once()
            ->with(['local', 'develop', 'testing'])
            ->andReturn(false);
        App::shouldReceive('environment')
            ->once()
            ->withNoArgs()
            ->andReturn('production');

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->never();

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock());

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Scenarios are not allowed in "production" environment.', $tester->getDisplay());
    }

    public function testExecuteCommandRunsListWithoutSuite(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
                    'list',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertSame(
            Command::SUCCESS,
            (new CommandTester($command))->execute([]),
        );
    }

    public function testExecuteCommandRunsListWithSuite(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
                    'list',
                    '--force',
                    '--quiet',
                    '--suite=main',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertSame(
            Command::SUCCESS,
            (new CommandTester($command))->execute([
                '--suite' => 'main',
            ]),
        );
    }

    public function testExecuteCommandReturnsFailureWhenShellFails(): void
    {
        $this->setUpInstalled(true, 2);
        $this->basePathMock('/app/root');
        $this->commandMocks();

        $process = $this->getProcessMock();
        /** @var Expectation $expectation */
        $expectation = $process->shouldReceive('run');
        $expectation->once()
            ->andReturn(false);

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
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

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertTrue($command->isHidden());
        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
    }
}
