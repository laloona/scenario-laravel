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
use Stateforge\Scenario\Laravel\Parameter\AlphaType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(AlphaType::class)]
#[Group('scenario')]
#[Small]
final class AlphaTypeTest extends TestCase
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

    public function testValidAcceptsAlphabeticAsciiString(): void
    {
        $type = new AlphaType();

        self::assertTrue($type->valid('AlphaOnly'));
        self::assertSame('AlphaOnly', $type->cast('AlphaOnly'));
        self::assertSame('AlphaOnly', $type->asString('AlphaOnly'));
    }

    public function testValidRejectsNonAlphabeticString(): void
    {
        $type = new AlphaType();

        self::assertFalse($type->valid('Alpha-Only'));
        self::assertNull($type->cast('Alpha-Only'));
        self::assertNull($type->asString('Alpha-Only'));
    }
}
