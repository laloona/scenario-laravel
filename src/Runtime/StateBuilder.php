<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Runtime;

use Illuminate\Support\Facades\App;
use Stateforge\Scenario\Core\Contract\ScenarioBuilderInterface;
use Stateforge\Scenario\Core\Contract\ScenarioInterface;
use Stateforge\Scenario\Laravel\Runtime\Exception\ScenarioUnknownException;
use Stateforge\Scenario\Laravel\Runtime\Exception\WrongScenarioSubclassException;
use Stateforge\Scenario\Laravel\Scenario;
use Throwable;
use function is_object;
use function is_subclass_of;

final class StateBuilder implements ScenarioBuilderInterface
{
    public function build(string $scenarioClass): ScenarioInterface
    {
        try {
            $scenario = App::make($scenarioClass);
        } catch (Throwable $exception) {
            throw new ScenarioUnknownException($scenarioClass);
        }

        if (is_object($scenario) === true
            && is_subclass_of($scenario, Scenario::class)) {
            return $scenario;
        }

        throw new WrongScenarioSubclassException($scenarioClass);
    }
}
