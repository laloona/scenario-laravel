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
use Mockery;
use Mockery\Expectation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Command\ScenarioCommand;
use Stateforge\Scenario\Laravel\Command\ScenarioParameterCommand;
use Symfony\Component\Console\Tester\CommandTester;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

#[CoversClass(ScenarioCommand::class)]
#[CoversClass(ScenarioParameterCommand::class)]
#[Group('command')]
#[Small]
final class ScenarioParameterCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

    protected function setUp(): void
    {
        $this->setUpFacades();
    }

    protected function tearDown(): void
    {
        $this->tearDownFacades();
    }

    public function testCommandIsConfigured(): void
    {
        $this->setUpInstalled(true, 2);
        $command = new ScenarioParameterCommand();

        self::assertSame('scenario:parameter', $command->getName());
        self::assertSame(
            'List all registered parameter types - should only be used for local/develop/testing',
            $command->getDescription(),
        );
        self::assertFalse($command->isHidden());
    }

    public function testExecuteCommandRunsParameterListing(): void
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
                    'parameter',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertSame(
            Command::SUCCESS,
            (new CommandTester($command))->execute([]),
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

        $command = new ScenarioParameterCommand();
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

        $command = new ScenarioParameterCommand();
        $command->setLaravel($this->getLaravelMock());

        self::assertTrue($command->isHidden());
        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
    }
}
