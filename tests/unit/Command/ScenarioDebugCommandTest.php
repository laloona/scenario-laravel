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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Scenario\Core\Attribute\ApplyScenario;
use Scenario\Core\Attribute\AsScenario;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Scenario\Core\Runtime\ScenarioDefinition;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Laravel\Command\ScenarioCommand;
use Scenario\Laravel\Command\ScenarioDebugCommand;
use Scenario\Laravel\Facades\Shell;
use Scenario\Laravel\Tests\Files\ValidScenario;
use Scenario\Laravel\Tests\Unit\CommandMock;
use Scenario\Laravel\Tests\Unit\LaravelMock;
use SplFileInfo;
use Symfony\Component\Console\Tester\CommandTester;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const PHP_BINARY;

#[CoversClass(ScenarioDebugCommand::class)]
#[UsesClass(Application::class)]
#[UsesClass(ApplyScenario::class)]
#[UsesClass(AsScenario::class)]
#[UsesClass(ScenarioCommand::class)]
#[UsesClass(ScenarioDefinition::class)]
#[UsesClass(ScenarioRegistry::class)]
#[Group('command')]
#[Medium]
final class ScenarioDebugCommandTest extends TestCase
{
    use LaravelMock;
    use CommandMock;

    private string $rootDir;

    protected function setUp(): void
    {
        $this->resetApplication();
        $this->resetScenarioRegistry();
        $this->createRootDir();
        $this->setScenarioConfiguration(new LoadedConfiguration(new DefaultConfiguration()));

        mkdir(Application::getRootDir() . '/tests/unit', 0777, true);
        file_put_contents(
            Application::getRootDir() . '/phpunit.xml',
            <<<XML
<?xml version="1.0"?>
<phpunit>
  <testsuites>
    <testsuite name="unit">
      <directory>tests/unit</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->resetScenarioRegistry();
        $this->resetApplication();
        $this->removeRootDir();
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioDebugCommand();

        self::assertSame('scenario:debug', $command->getName());
        self::assertSame(
            'Debug a given scenario or unit test - should only be used for local/testing',
            $command->getDescription(),
        );
    }

    public function testExecuteCommandRunsDebugForScenario(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [],
        ));

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'debug',
                    ValidScenario::class,
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $tester = new CommandTester($command);
        $tester->setInputs(['0']);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testExecuteCommandRunsDebugForFoundUnitTestMethod(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        $suffix = 'Fixture' . uniqid();
        file_put_contents(Application::getRootDir() . '/tests/unit/MethodLevelScenarioTest.php', <<<PHP
<?php declare(strict_types=1);

namespace Scenario\\Laravel\\Tests\\Fixtures\\{$suffix};

use PHPUnit\\Framework\\TestCase;
use Scenario\\Core\\Attribute\\ApplyScenario;

final class MethodLevelScenarioTest extends TestCase
{
    #[ApplyScenario('demo')]
    public function testDebuggable(): void
    {
    }
}
PHP);

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'debug',
                    'Scenario\\Laravel\\Tests\\Fixtures\\' . $suffix . '\\MethodLevelScenarioTest',
                    'testDebuggable',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testExecuteCommandFailsWhenNoScenariosOrUnitTestsWereFound(): void
    {
        $this->commandMocks();

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'No scenarios or unit tests were found, please create one.',
            $tester->getDisplay(),
        );
    }

    public function testExecuteCommandRunsDebugForSelectedUnitTestClass(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [],
        ));

        $suffix = 'Fixture' . uniqid();
        $firstClass = 'Scenario\\Laravel\\Tests\\Fixtures\\' . $suffix . '\\FirstScenarioTest';
        $secondClass = 'Scenario\\Laravel\\Tests\\Fixtures\\' . $suffix . '\\SecondScenarioTest';

        $this->writeFixture('FirstScenarioTest.php', <<<PHP
<?php declare(strict_types=1);

namespace Scenario\\Laravel\\Tests\\Fixtures\\{$suffix};

use PHPUnit\\Framework\\TestCase;
use Scenario\\Core\\Attribute\\ApplyScenario;

final class FirstScenarioTest extends TestCase
{
    #[ApplyScenario('demo')]
    public function testOne(): void
    {
    }
}
PHP);

        $this->writeFixture('SecondScenarioTest.php', <<<PHP
<?php declare(strict_types=1);

namespace Scenario\\Laravel\\Tests\\Fixtures\\{$suffix};

use PHPUnit\\Framework\\TestCase;
use Scenario\\Core\\Attribute\\ApplyScenario;

final class SecondScenarioTest extends TestCase
{
    #[ApplyScenario('demo')]
    public function testFirstDebuggable(): void
    {
    }
}
PHP);

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'debug',
                    $secondClass,
                    'testFirstDebuggable',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $tester = new CommandTester($command);
        $tester->setInputs(['Unit Test', $secondClass]);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testExecuteCommandRunsDebugForUnitTestWithoutMethodSelection(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        $suffix = 'Fixture' . uniqid();
        $className = 'Scenario\\Laravel\\Tests\\Fixtures\\' . $suffix . '\\ClassLevelScenarioTest';

        $this->writeFixture('ClassLevelScenarioTest.php', <<<PHP
<?php declare(strict_types=1);

namespace Scenario\\Laravel\\Tests\\Fixtures\\{$suffix};

use PHPUnit\\Framework\\TestCase;
use Scenario\\Core\\Attribute\\ApplyScenario;

#[ApplyScenario('demo')]
final class ClassLevelScenarioTest extends TestCase
{
    public function testDebuggable(): void
    {
    }
}
PHP);

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'debug',
                    $className,
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testDebugTestRunsSelectedMethodWhenMultipleMethodsAreGiven(): void
    {
        $this->commandMocks();
        $this->basePathMock();

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'debug',
                    'Scenario\\Laravel\\Tests\\Fixtures\\MultiMethodScenarioTest',
                    'testSecondDebuggable',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                Mockery::type(OutputStyle::class),
            )
            ->andReturn(true);

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $tester = new CommandTester($command);
        $tester->setInputs(['testSecondDebuggable']);
        self::assertSame(Command::FAILURE, $tester->execute([]));

        $method = (new ReflectionClass(ScenarioDebugCommand::class))->getMethod('debugTest');
        $method->setAccessible(true);

        self::assertSame(
            Command::SUCCESS,
            $method->invoke($command, [
                'Scenario\\Laravel\\Tests\\Fixtures\\MultiMethodScenarioTest' => [
                    'testFirstDebuggable',
                    'testSecondDebuggable',
                ],
            ]),
        );
    }

    public function testGetSelectedTypeReturnsExpectedValues(): void
    {
        $command = new ScenarioDebugCommand();

        $method = (new ReflectionClass(ScenarioDebugCommand::class))->getMethod('getSelectedType');
        $method->setAccessible(true);

        self::assertFalse($method->invoke($command, [], []));
        self::assertSame('Unit Test', $method->invoke($command, [], [
            'Scenario\\Laravel\\Tests\\Fixtures\\DemoTest' => ['testDemo'],
        ]));
        self::assertSame('Scenario', $method->invoke($command, [
            ValidScenario::class => new ScenarioDefinition(
                'main',
                ValidScenario::class,
                new AsScenario('valid'),
                [],
            ),
        ], []));
    }

    public function testRunDebugClassPassesMethodArgumentWhenGiven(): void
    {
        $this->basePathMock();

        Shell::shouldReceive('run')
            ->once()
            ->with(
                [
                    PHP_BINARY,
                    'vendor/bin/scenario',
                    'debug',
                    ValidScenario::class,
                    'testDebuggable',
                    '--force',
                    '--quiet',
                ],
                '/app/root',
                null,
            )
            ->andReturn(true);

        $command = new ScenarioDebugCommand();
        $command->setLaravel($this->getLaravelMock('/app/root'));

        $method = (new ReflectionClass(ScenarioDebugCommand::class))->getMethod('runDebugClass');
        $method->setAccessible(true);

        self::assertTrue($method->invoke($command, ValidScenario::class, 'testDebuggable'));
    }

    private function createRootDir(): void
    {
        $this->rootDir = sys_get_temp_dir() . '/scenario_laravel_' . uniqid();
        mkdir($this->rootDir);

        $property = (new ReflectionClass(Application::class))->getProperty('rootDir');
        $property->setValue(null, $this->rootDir);
    }

    private function removeRootDir(): void
    {
        if (is_dir($this->rootDir) === false) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir() === true) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($this->rootDir);
    }

    private function resetApplication(): void
    {
        $reflection = new ReflectionClass(Application::class);

        $rootDir = $reflection->getProperty('rootDir');
        $rootDir->setValue(null, null);

        $configuration = $reflection->getProperty('configuration');
        $configuration->setValue(null, null);

        $isBooted = $reflection->getProperty('isBooted');
        $isBooted->setValue(null, false);
    }

    private function resetScenarioRegistry(): void
    {
        $property = (new ReflectionClass(ScenarioRegistry::class))->getProperty('instance');
        $property->setValue(null, null);
    }

    private function setScenarioConfiguration(?LoadedConfiguration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }

    private function writeFixture(string $filename, string $content): void
    {
        file_put_contents(Application::getRootDir() . '/tests/unit/' . $filename, $content);
    }
}
