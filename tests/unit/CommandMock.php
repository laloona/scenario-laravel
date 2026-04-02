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
            ->with('scenario.allowed_envs', ['local', 'testing'])
            ->andReturn(['local', 'testing']);

        App::shouldReceive('environment')
            ->once()
            ->with(['local', 'testing'])
            ->andReturn(true);
    }

    private function basePathMock(): void
    {
        App::shouldReceive('basePath')
            ->twice()
            ->andReturnUsing(static function (?string $path = null): string {
                return $path === 'vendor/bin/scenario'
                    ? 'vendor/bin/scenario'
                    : '/app/root';
            });
    }
}
