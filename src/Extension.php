<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel;

use Stateforge\Scenario\Core\Console\CliApplicationExtension;
use Stateforge\Scenario\Core\Extension as ExtensionInterface;
use Stateforge\Scenario\Core\Runtime\ApplicationExtension;
use Stateforge\Scenario\Laravel\Runtime\Application;

final class Extension implements ExtensionInterface
{
    public function application(): ApplicationExtension
    {
        return new Application();
    }

    public function cli(): ?CliApplicationExtension
    {
        return null;
    }
}
