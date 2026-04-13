<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Parameter;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Parameter\DateTimeType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(DateTimeType::class)]
#[Group('scenario')]
#[Small]
final class DateTimeTypeTest extends TestCase
{
    use LaravelMock;

    protected function setUp(): void
    {
        $this->setUpFacades();
        $this->getLaravelMock()->instance('validator', new Factory(new Translator(new ArrayLoader(), 'en')));
    }

    protected function tearDown(): void
    {
        $this->tearDownFacades();
    }

    public function testValidAcceptsDateTimeString(): void
    {
        $type = new DateTimeType();

        self::assertTrue($type->valid('2026-04-12 14:30:45'));
        self::assertSame('2026-04-12 14:30:45', $type->cast('2026-04-12 14:30:45'));
        self::assertSame('2026-04-12 14:30:45', $type->asString('2026-04-12 14:30:45'));
    }

    public function testValidRejectsInvalidDateTimeString(): void
    {
        $type = new DateTimeType();

        self::assertFalse($type->valid('2026-04-12T14:30:45'));
        self::assertNull($type->cast('2026-04-12T14:30:45'));
        self::assertNull($type->asString('2026-04-12T14:30:45'));
    }
}
