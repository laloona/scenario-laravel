<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Runtime\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Runtime\Exception\ScenarioUnknownException;

#[CoversClass(ScenarioUnknownException::class)]
#[Group('runtime')]
#[Small]
final class ScenarioUnknownExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new ScenarioUnknownException(
            'UnknownScenario',
        );

        self::assertSame(
            'UnknownScenario was not found',
            $exception->getMessage(),
        );
    }
}
