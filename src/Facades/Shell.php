<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Facades;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Facade;
use Stateforge\Scenario\Laravel\Runtime\ProcessInterface;

/**
 * @method static bool run(list<string> $arguments, string $directory, ?OutputStyle $output)
 */
final class Shell extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ProcessInterface::class;
    }
}
