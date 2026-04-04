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

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Facades\Consumer;
use Stateforge\Scenario\Laravel\Facades\Shell;
use Stateforge\Scenario\Laravel\Scenario;
use Stateforge\Scenario\Laravel\Tests\Files\DbModel;
use Stateforge\Scenario\Laravel\Tests\Files\ValidScenario;
use stdClass;
use function file_exists;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const DIRECTORY_SEPARATOR;

#[CoversClass(Scenario::class)]
#[Group('scenario')]
#[Small]
final class ScenarioTest extends TestCase
{
    private string $tempDir;
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'scenario-laravel-' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testRootDirReturnsLaravelBasePath(): void
    {
        App::shouldReceive('basePath')
            ->once()
            ->andReturn('/app/root');

        self::assertSame('/app/root', (new ValidScenario())->publicRootDir());
    }

    public function testAbsoluteDirReturnsAbsolutePathForRelativeDirectory(): void
    {
        $existing = $this->tempDir . DIRECTORY_SEPARATOR . 'var';
        mkdir($existing, 0777, true);

        App::shouldReceive('basePath')
            ->twice()
            ->andReturn($this->tempDir);

        self::assertSame($existing, (new ValidScenario())->publicAbsoluteDir('var', false));
    }

    public function testAbsoluteDirKeepsAlreadyAbsoluteDirectory(): void
    {
        $existing = $this->tempDir . DIRECTORY_SEPARATOR . 'absolute-var';
        mkdir($existing, 0777, true);

        App::shouldReceive('basePath')
            ->once()
            ->andReturn($this->tempDir);

        self::assertSame($existing, (new ValidScenario())->publicAbsoluteDir($existing, false));
    }

    public function testAbsoluteDirCreatesDirectoryWhenRequested(): void
    {
        $expected = $this->tempDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';

        App::shouldReceive('basePath')
            ->twice()
            ->andReturn($this->tempDir);

        $result = (new ValidScenario())->publicAbsoluteDir('storage/cache', true);

        self::assertSame($expected, $result);
        self::assertTrue(file_exists($expected));
    }

    public function testAbsoluteDirReturnsFalseWhenDirectoryDoesNotExistAndIsNotCreated(): void
    {
        App::shouldReceive('basePath')
            ->twice()
            ->andReturn($this->tempDir);

        self::assertFalse((new ValidScenario())->publicAbsoluteDir('missing-directory', false));
    }

    public function testAbsoluteFileReturnsAbsoluteFilePath(): void
    {
        $expectedDirectory = $this->tempDir . DIRECTORY_SEPARATOR . 'fixtures';
        mkdir($expectedDirectory, 0777, true);

        App::shouldReceive('basePath')
            ->twice()
            ->andReturn($this->tempDir);

        $result = (new ValidScenario())->publicAbsoluteFile('fixtures/demo.txt', false);

        self::assertSame($expectedDirectory . DIRECTORY_SEPARATOR . 'demo.txt', $result);
    }

    public function testAbsoluteFileReturnsFalseWhenParentDirectoryCannotBeResolved(): void
    {
        App::shouldReceive('basePath')
            ->twice()
            ->andReturn($this->tempDir);

        self::assertFalse((new ValidScenario())->publicAbsoluteFile('missing/demo.txt', false));
    }

    public function testConfigDelegatesToLaravelConfigFacade(): void
    {
        Config::shouldReceive('get')
            ->once()
            ->with('scenario.allowed_envs', ['local', 'testing'])
            ->andReturn(['testing']);

        self::assertSame(
            ['testing'],
            (new ValidScenario())->publicConfig('scenario.allowed_envs', ['local', 'testing']),
        );
    }

    public function testCommandDelegatesToArtisanFacade(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('scenario:apply', ['name' => 'demo']);

        (new ValidScenario())->publicCommand('scenario:apply', ['name' => 'demo']);

        self::addToAssertionCount(1);
    }

    public function testFilesystemReturnsLaravelFilesystemInstance(): void
    {
        self::assertInstanceOf(Filesystem::class, (new ValidScenario())->publicFilesystem());
    }

    public function testModelDelegatesToLaravelAppFacade(): void
    {
        $model = new DbModel();

        App::shouldReceive('make')
            ->once()
            ->with(DbModel::class)
            ->andReturn($model);

        self::assertSame($model, (new ValidScenario())->publicModel(DbModel::class));
    }

    public function testEventDelegatesToLaravelEventFacade(): void
    {
        $event = new stdClass();

        Event::shouldReceive('dispatch')
            ->once()
            ->with($event);

        (new ValidScenario())->publicEvent($event);

        self::addToAssertionCount(1);
    }

    public function testMessageDelegatesToLaravelBusFacade(): void
    {
        $job = new stdClass();

        Bus::shouldReceive('dispatch')
            ->once()
            ->with($job);

        (new ValidScenario())->publicMessage($job);

        self::addToAssertionCount(1);
    }

    public function testConsumerDelegatesToConsumerFacade(): void
    {
        Consumer::shouldReceive('consume')
            ->once()
            ->with('emails');

        (new ValidScenario())->publicConsumer('emails');

        self::addToAssertionCount(1);
    }

    public function testShellDelegatesToShellFacadeWithRootDir(): void
    {
        App::shouldReceive('basePath')
            ->once()
            ->andReturn('/app/root');

        Shell::shouldReceive('run')
            ->once()
            ->with(['php', 'artisan', 'list'], '/app/root', null)
            ->andReturn(true);

        self::assertTrue((new ValidScenario())->publicShell(['php', 'artisan', 'list']));
    }

    private function removeDirectory(string $directory): void
    {
        if (file_exists($directory) === false) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path) === true) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
