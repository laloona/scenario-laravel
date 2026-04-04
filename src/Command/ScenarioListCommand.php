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
use function is_string;
use const PHP_BINARY;

final class ScenarioListCommand extends ScenarioCommand
{
    protected $signature = 'scenario:list {--suite= : Filters to given suite}';

    protected $description = 'List all available scenarios, use --suite="name of your suite" if you want to see just one suite - should only be used for local/develop/testing';

    protected function executeCommand(): int
    {
        $arguments = [
            PHP_BINARY,
            $this->getCliPath(),
            'list',
            '--force',
            '--quiet',
        ];

        $suite = $this->option('suite');
        if (is_string($suite) === true
            && $suite !== '') {
            $arguments[] = '--suite=' . $suite;
        }

        return Shell::run($arguments, App::basePath(), $this->output) === true
            ? self::SUCCESS
            : self::FAILURE;
    }
}
