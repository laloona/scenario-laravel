<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Runtime;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Support\Facades\Artisan;

final class Consumer
{
    public function __construct(
        private readonly Factory $queue,
    ) {
    }

    public function consume(string $queueName): void
    {
        $connection = $this->queue->connection();
        while ($connection->size($queueName) > 0) {
            Artisan::call('queue:work', [
                '--once' => true,
                '--queue' => $queueName,
                '--no-interaction' => true,
            ]);
        }
    }
}
