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
use Stateforge\Scenario\Laravel\Parameter\DateType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(DateType::class)]
#[Group('scenario')]
#[Small]
final class DateTypeTest extends TestCase
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

    public function testValidAcceptsDateString(): void
    {
        $type = new DateType();

        self::assertTrue($type->valid('2026-04-12'));
        self::assertSame('2026-04-12', $type->cast('2026-04-12'));
        self::assertSame('2026-04-12', $type->asString('2026-04-12'));
    }

    public function testValidRejectsInvalidDateString(): void
    {
        $type = new DateType();

        self::assertFalse($type->valid('not-a-date'));
        self::assertNull($type->cast('not-a-date'));
        self::assertNull($type->asString('not-a-date'));
    }
}
