<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Runtime;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Runtime\Consumer;

#[CoversClass(Consumer::class)]
#[Group('runtime')]
#[Small]
final class ConsumerTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testConsumeDoesNothingWhenQueueIsEmpty(): void
    {
        $connection = $this->createMock(Queue::class);
        $connection->expects(self::once())
            ->method('size')
            ->with('emails')
            ->willReturn(0);

        $queue = $this->createMock(Factory::class);
        $queue->expects(self::once())
            ->method('connection')
            ->with()
            ->willReturn($connection);

        Artisan::shouldReceive('call')->never();

        (new Consumer($queue))->consume('emails');
    }

    public function testConsumeProcessesQueueUntilItIsEmpty(): void
    {
        $connection = $this->createMock(Queue::class);
        $connection->expects(self::exactly(3))
            ->method('size')
            ->with('emails')
            ->willReturnOnConsecutiveCalls(2, 1, 0);

        $queue = $this->createMock(Factory::class);
        $queue->expects(self::once())
            ->method('connection')
            ->with()
            ->willReturn($connection);

        Artisan::shouldReceive('call')
            ->twice()
            ->with('queue:work', [
                '--once' => true,
                '--queue' => 'emails',
                '--no-interaction' => true,
            ]);

        (new Consumer($queue))->consume('emails');
    }
}
