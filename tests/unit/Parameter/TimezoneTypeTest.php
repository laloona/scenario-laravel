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
use Stateforge\Scenario\Laravel\Parameter\TimezoneType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(TimezoneType::class)]
#[Group('scenario')]
#[Small]
final class TimezoneTypeTest extends TestCase
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

    public function testValidAcceptsTimezoneIdentifier(): void
    {
        $type = new TimezoneType();

        self::assertTrue($type->valid('Europe/Berlin'));
        self::assertSame('Europe/Berlin', $type->cast('Europe/Berlin'));
        self::assertSame('Europe/Berlin', $type->asString('Europe/Berlin'));
    }

    public function testValidRejectsInvalidTimezoneIdentifier(): void
    {
        $type = new TimezoneType();

        self::assertFalse($type->valid('Mars/Olympus'));
        self::assertNull($type->cast('Mars/Olympus'));
        self::assertNull($type->asString('Mars/Olympus'));
    }
}
