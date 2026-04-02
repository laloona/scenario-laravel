<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Tests\Unit;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\App;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Scenario\Laravel\Command\ScenarioCommand;
use Scenario\Laravel\Command\ScenarioListCommand;
use Scenario\Laravel\Facades\Shell;
use Symfony\Component\Console\Tester\CommandTester;
use function fwrite;
use const PHP_BINARY;
use const PHP_EOL;
use const STDERR;

#[CoversClass(ScenarioCommand::class)]
#[CoversClass(ScenarioListCommand::class)]
#[Group('command')]
#[Small]
final class ScenarioListCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

    public function test_command_file_path(): void
    {
        $file = (new ReflectionClass(\Scenario\Laravel\Command\ScenarioListCommand::class))->getFileName();

        fwrite(STDERR, $file . PHP_EOL);

        self::assertNotFalse($file);
        self::assertStringContainsString('/app/src/Command/ScenarioListCommand.php', $file);
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioListCommand();

        self::assertSame('scenario:list', $command->getName());
        self::assertSame(
            'List all available scenarios, use --suite="name of your suite" if you want to see just one suite - should only be used for local/testing',
            $command->getDescription(),
        );
    }

    public function testExecuteCommandRunsListWithoutSuite(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        App::shouldReceive('basePath')
            ->twice()
            ->andReturn('vendor/bin/scenario', '/app/root');

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'list',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        self::assertSame(
            Command::SUCCESS,
            (new CommandTester($command))->execute([]),
        );
    }

    public function testExecuteCommandRunsListWithSuite(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
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
        $command->setLaravel($this->getLaravelMock('/app/root'));

        self::assertSame(
            Command::SUCCESS,
            (new CommandTester($command))->execute([
                '--suite' => 'main',
            ]),
        );
    }

    public function testExecuteCommandReturnsFailureWhenShellFails(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        Shell::shouldReceive('run')
            ->once()
            ->andReturn(false);

        $command = new ScenarioListCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        self::assertSame(Command::FAILURE, (new CommandTester($command))->execute([]));
    }
}
