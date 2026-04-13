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
use Stateforge\Scenario\Laravel\Parameter\AlphaNumType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(AlphaNumType::class)]
#[Group('scenario')]
#[Small]
final class AlphaNumTypeTest extends TestCase
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

    public function testValidAcceptsAlphaNumericAsciiString(): void
    {
        $type = new AlphaNumType();

        self::assertTrue($type->valid('Alpha123'));
        self::assertSame('Alpha123', $type->cast('Alpha123'));
        self::assertSame('Alpha123', $type->asString('Alpha123'));
    }

    public function testValidRejectsStringWithDash(): void
    {
        $type = new AlphaNumType();

        self::assertFalse($type->valid('Alpha-123'));
        self::assertNull($type->cast('Alpha-123'));
        self::assertNull($type->asString('Alpha-123'));
    }
}
