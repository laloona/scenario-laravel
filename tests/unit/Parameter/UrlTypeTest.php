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
use Stateforge\Scenario\Laravel\Parameter\UrlType;
use Stateforge\Scenario\Laravel\Tests\Unit\LaravelMock;

#[CoversClass(UrlType::class)]
#[Group('scenario')]
#[Small]
final class UrlTypeTest extends TestCase
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

    public function testValidAcceptsHttpAndHttpsUrl(): void
    {
        $type = new UrlType();

        self::assertTrue($type->valid('https://example.com/path'));
        self::assertSame('https://example.com/path', $type->cast('https://example.com/path'));
        self::assertSame('https://example.com/path', $type->asString('https://example.com/path'));
    }

    public function testValidRejectsUnsupportedScheme(): void
    {
        $type = new UrlType();

        self::assertFalse($type->valid('not-a-url'));
        self::assertNull($type->cast('not-a-url'));
        self::assertNull($type->asString('not-a-url'));
    }
}
