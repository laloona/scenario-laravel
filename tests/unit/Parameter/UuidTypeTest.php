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
use Stateforge\Scenario\Laravel\Parameter\UuidType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(UuidType::class)]
#[Group('scenario')]
#[Small]
final class UuidTypeTest extends TestCase
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

    public function testValidAcceptsUuidString(): void
    {
        $type = new UuidType();
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        self::assertTrue($type->valid($uuid));
        self::assertSame($uuid, $type->cast($uuid));
        self::assertSame($uuid, $type->asString($uuid));
    }

    public function testValidRejectsInvalidUuidString(): void
    {
        $type = new UuidType();

        self::assertFalse($type->valid('not-a-uuid'));
        self::assertNull($type->cast('not-a-uuid'));
        self::assertNull($type->asString('not-a-uuid'));
    }
}
