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

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

trait CommandMock
{
    private function commandMocks(): void
    {
        Config::shouldReceive('get')
            ->once()
            ->with('scenario.allowed_envs', ['local', 'develop', 'testing'])
            ->andReturn(['local', 'develop', 'testing']);

        App::shouldReceive('environment')
            ->once()
            ->with(['local', 'develop', 'testing'])
            ->andReturn(true);
    }

    private function basePathMock(string $root): void
    {
        App::shouldReceive('basePath')
            ->atLeast()
            ->once()
            ->andReturnUsing(static function (?string $path = null) use ($root): string {
                return $path === null ? $root : $path;
            });
    }
}
