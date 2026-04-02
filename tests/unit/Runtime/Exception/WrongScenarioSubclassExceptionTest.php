<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Tests\Unit\Runtime\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Scenario\Laravel\Runtime\Exception\WrongScenarioSubclassException;

#[CoversClass(WrongScenarioSubclassException::class)]
#[Group('runtime')]
#[Small]
final class WrongScenarioSubclassExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new WrongScenarioSubclassException(
            'WrongExtendedScenario',
        );

        self::assertSame(
            'WrongExtendedScenario is not from type Scenario\Laravel\Scenario',
            $exception->getMessage(),
        );
    }
}
