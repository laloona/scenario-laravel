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
use Stateforge\Scenario\Laravel\Parameter\NegativeFloatType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(NegativeFloatType::class)]
#[Group('scenario')]
#[Small]
final class NegativeFloatTypeTest extends TestCase
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

    public function testValidAcceptsNegativeFloat(): void
    {
        $type = new NegativeFloatType();

        self::assertTrue($type->valid(-1.5));
        self::assertSame(-1.5, $type->cast(-1.5));
        self::assertSame('-1.5', $type->asString(-1.5));
    }

    public function testValidRejectsZero(): void
    {
        $type = new NegativeFloatType();

        self::assertFalse($type->valid(0.0));
        self::assertNull($type->cast(0.0));
        self::assertNull($type->asString(0.0));
    }
}
