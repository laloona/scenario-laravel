<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Tests\Files;

use Illuminate\Filesystem\Filesystem;
use Scenario\Laravel\Scenario;

final class ValidScenario extends Scenario
{
    public function up(): void
    {
    }

    public function publicRootDir(): string
    {
        return $this->rootDir();
    }

    public function publicAbsoluteDir(string $directory, bool $create): string|false
    {
        return $this->absoluteDir($directory, $create);
    }

    public function publicAbsoluteFile(string $file, bool $create): string|false
    {
        return $this->absoluteFile($file, $create);
    }

    public function publicConfig(string $key, mixed $default = null): mixed
    {
        return $this->config($key, $default);
    }

    /**
     * @param array<string, string> $params
     */
    public function publicCommand(string $command, array $params = []): void
    {
        $this->command($command, $params);
    }

    public function publicFilesystem(): Filesystem
    {
        return $this->filesystem();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param class-string<TModel> $model
     * @return TModel
     */
    public function publicModel(string $model): object
    {
        return $this->model($model);
    }

    public function publicEvent(object $event): void
    {
        $this->event($event);
    }

    public function publicMessage(object $job): void
    {
        $this->message($job);
    }

    public function publicConsumer(string $queue): void
    {
        $this->consumer($queue);
    }

    /**
     * @param list<string> $cli
     */
    public function publicShell(array $cli): bool
    {
        return $this->shell($cli);
    }
}
