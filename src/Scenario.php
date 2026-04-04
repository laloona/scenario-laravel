<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Stateforge\Scenario\Core\Scenario as CoreScenario;
use Stateforge\Scenario\Laravel\Facades\Consumer;
use Stateforge\Scenario\Laravel\Facades\Shell;
use function basename;
use function dirname;
use function file_exists;
use function mkdir;
use function realpath;
use function strpos;
use const DIRECTORY_SEPARATOR;

abstract class Scenario extends CoreScenario
{
    final protected function rootDir(): string
    {
        return App::basePath();
    }

    final protected function absoluteDir(string $directory, bool $create): string|false
    {
        $absolute = (strpos($directory, $this->rootDir()) === false)
            ? $this->rootDir() . DIRECTORY_SEPARATOR . $directory
            : $directory;
        if ($create === true
            && file_exists($absolute) === false) {
            mkdir($absolute, 0777, true);
        }

        return realpath($absolute);
    }

    final protected function absoluteFile(string $file, bool $create): string|false
    {
        $directory = $this->absoluteDir(dirname($file), $create);
        if ($directory === false) {
            return false;
        }

        return $directory . DIRECTORY_SEPARATOR . basename($file);
    }

    final protected function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }

    /**
     * @param array<string, string> $params
     */
    final protected function command(string $command, array $params = []): void
    {
        Artisan::call($command, $params);
    }

    final protected function filesystem(): Filesystem
    {
        return new Filesystem();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param class-string<TModel> $model
     * @return TModel
     */
    final protected function model(string $model): object
    {
        /** @var TModel $instance */
        $instance = App::make($model);

        return $instance;
    }

    final protected function event(object $event): void
    {
        Event::dispatch($event);
    }

    final protected function message(object $job): void
    {
        Bus::dispatch($job);
    }

    final protected function consumer(string $queue): void
    {
        Consumer::consume($queue);
    }

    /**
     * @param list<string> $cli
     */
    final protected function shell(array $cli): bool
    {
        return Shell::run($cli, $this->rootDir(), null);
    }
}
