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
use Stateforge\Scenario\Laravel\Parameter\TimeType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(TimeType::class)]
#[Group('scenario')]
#[Small]
final class TimeTypeTest extends TestCase
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

    public function testValidAcceptsTimeString(): void
    {
        $type = new TimeType();

        self::assertTrue($type->valid('14:30:45'));
        self::assertSame('14:30:45', $type->cast('14:30:45'));
        self::assertSame('14:30:45', $type->asString('14:30:45'));
    }

    public function testValidRejectsInvalidTimeString(): void
    {
        $type = new TimeType();

        self::assertFalse($type->valid('14:30'));
        self::assertNull($type->cast('14:30'));
        self::assertNull($type->asString('14:30'));
    }
}
