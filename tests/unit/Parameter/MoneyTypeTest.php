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
use Stateforge\Scenario\Laravel\Parameter\MoneyType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(MoneyType::class)]
#[Group('scenario')]
#[Small]
final class MoneyTypeTest extends TestCase
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

    public function testValidAcceptsPositiveAmountWithTwoDecimals(): void
    {
        $type = new MoneyType();

        self::assertTrue($type->valid('10.99'));
        self::assertSame(10.99, $type->cast('10.99'));
        self::assertSame('10.99', $type->asString('10.99'));
    }

    public function testValidRejectsAmountWithTooManyDecimals(): void
    {
        $type = new MoneyType();

        self::assertFalse($type->valid('10.999'));
        self::assertNull($type->cast('10.999'));
        self::assertNull($type->asString('10.999'));
    }
}
