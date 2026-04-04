<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Runtime;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Laravel\Runtime\Exception\ScenarioUnknownException;
use Stateforge\Scenario\Laravel\Runtime\Exception\WrongScenarioSubclassException;
use Stateforge\Scenario\Laravel\Runtime\StateBuilder;
use Stateforge\Scenario\Laravel\Scenario;
use Stateforge\Scenario\Laravel\Tests\Files\FailedScenario;
use Stateforge\Scenario\Laravel\Tests\Files\InvalidScenario;
use Stateforge\Scenario\Laravel\Tests\Files\ValidScenario;

#[CoversClass(StateBuilder::class)]
#[UsesClass(ScenarioUnknownException::class)]
#[UsesClass(WrongScenarioSubclassException::class)]
#[Group('runtime')]
#[Small]
final class ScenarioBuilderTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testBuildReturnsScenarioInstanceWhenResolvedObjectIsValid(): void
    {
        $scenario = new ValidScenario();

        App::shouldReceive('make')
            ->once()
            ->with(ValidScenario::class)
            ->andReturn($scenario);

        $result = (new StateBuilder())->build(ValidScenario::class);

        self::assertSame($scenario, $result);
        self::assertInstanceOf(ValidScenario::class, $result);
    }

    public function testBuildThrowsScenarioUnknownExceptionWhenResolutionFails(): void
    {
        App::shouldReceive('make')
            ->once()
            ->with(FailedScenario::class)
            ->andThrow(new RuntimeException('Service container failed'));

        $this->expectException(ScenarioUnknownException::class);
        $this->expectExceptionMessage(FailedScenario::class . ' was not found');

        (new StateBuilder())->build(FailedScenario::class);
    }

    public function testBuildThrowsWrongScenarioSubclassExceptionWhenResolvedObjectIsNotScenarioSubclass(): void
    {
        App::shouldReceive('make')
            ->once()
            ->with(InvalidScenario::class)
            ->andReturn(new InvalidScenario());

        $this->expectException(WrongScenarioSubclassException::class);
        $this->expectExceptionMessage(InvalidScenario::class . ' is not from type ' . Scenario::class);

        (new StateBuilder())->build(InvalidScenario::class);
    }
}
