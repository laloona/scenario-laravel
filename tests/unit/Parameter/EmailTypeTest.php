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
use Stateforge\Scenario\Laravel\Parameter\EmailType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(EmailType::class)]
#[Group('scenario')]
#[Small]
final class EmailTypeTest extends TestCase
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

    public function testValidAcceptsEmailAddress(): void
    {
        $type = new EmailType();

        self::assertTrue($type->valid('user@example.com'));
        self::assertSame('user@example.com', $type->cast('user@example.com'));
        self::assertSame('user@example.com', $type->asString('user@example.com'));
    }

    public function testValidRejectsInvalidEmailAddress(): void
    {
        $type = new EmailType();

        self::assertFalse($type->valid('not-an-email'));
        self::assertNull($type->cast('not-an-email'));
        self::assertNull($type->asString('not-an-email'));
    }
}
