<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Scenario\Laravel\Runtime\Consumer as RuntimeConsumer;

/**
 * @method static void consume(string $queueName)
 */
final class Consumer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RuntimeConsumer::class;
    }
}
