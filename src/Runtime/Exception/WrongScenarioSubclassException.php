<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Runtime\Exception;

use Scenario\Core\Runtime\Exception\Exception;
use Scenario\Laravel\Scenario;
use function sprintf;

final class WrongScenarioSubclassException extends Exception
{
    public function __construct(string $scenarioClass)
    {
        parent::__construct(
            sprintf(
                '%s is not from type %s',
                $scenarioClass,
                Scenario::class,
            ),
        );
    }
}
