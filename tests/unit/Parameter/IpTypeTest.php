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
use Stateforge\Scenario\Laravel\Parameter\IpType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(IpType::class)]
#[Group('scenario')]
#[Small]
final class IpTypeTest extends TestCase
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

    public function testValidAcceptsIpAddress(): void
    {
        $type = new IpType();

        self::assertTrue($type->valid('127.0.0.1'));
        self::assertSame('127.0.0.1', $type->cast('127.0.0.1'));
        self::assertSame('127.0.0.1', $type->asString('127.0.0.1'));
    }

    public function testValidRejectsInvalidIpAddress(): void
    {
        $type = new IpType();

        self::assertFalse($type->valid('999.999.999.999'));
        self::assertNull($type->cast('999.999.999.999'));
        self::assertNull($type->asString('999.999.999.999'));
    }
}
