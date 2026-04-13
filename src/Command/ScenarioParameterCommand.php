<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Command;

use Illuminate\Support\Facades\App;
use Stateforge\Scenario\Laravel\Facades\Shell;
use const PHP_BINARY;

final class ScenarioParameterCommand extends ScenarioCommand
{
    protected $signature = 'scenario:parameter';

    protected $description = 'List all registered parameter types - should only be used for local/develop/testing';

    protected function executeCommand(): int
    {
        return Shell::run(
            [
                PHP_BINARY,
                $this->getCliPath(),
                'parameter',
                '--force',
                '--quiet',
            ],
            App::basePath(),
            $this->output,
        ) === true
        ? self::SUCCESS
        : self::FAILURE;
    }
}
