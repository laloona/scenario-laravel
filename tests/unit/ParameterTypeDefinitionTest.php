<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\ParameterTypeDefinition;
use Stateforge\Scenario\Laravel\Tests\Files\IntegerParameterType;

#[CoversClass(ParameterTypeDefinition::class)]
#[Group('scenario')]
#[Small]
final class ParameterTypeDefinitionTest extends TestCase
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

    public function testCastReturnsValueWhenValidationPasses(): void
    {
        $type = new IntegerParameterType();

        self::assertSame(5, $type->cast(5));
    }

    public function testCastReturnsNullWhenValidationFails(): void
    {
        $type = new IntegerParameterType();

        self::assertNull($type->cast('not-an-integer'));
    }
}
