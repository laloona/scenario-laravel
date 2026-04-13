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
use Stateforge\Scenario\Laravel\Parameter\AlphaDashType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(AlphaDashType::class)]
#[Group('scenario')]
#[Small]
final class AlphaDashTypeTest extends TestCase
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

    public function testValidAcceptsAlphaDashAsciiString(): void
    {
        $type = new AlphaDashType();

        self::assertTrue($type->valid('Alpha-Dash_123'));
        self::assertSame('Alpha-Dash_123', $type->cast('Alpha-Dash_123'));
        self::assertSame('Alpha-Dash_123', $type->asString('Alpha-Dash_123'));
    }

    public function testValidRejectsStringWithSpaces(): void
    {
        $type = new AlphaDashType();

        self::assertFalse($type->valid('Alpha Dash'));
        self::assertNull($type->cast('Alpha Dash'));
        self::assertNull($type->asString('Alpha Dash'));
    }
}
