<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Runtime;

use Illuminate\Support\Facades\Artisan;
use Scenario\Core\Attribute\RefreshDatabase;
use Scenario\Core\Contract\DatabaseRefreshExecutorInterface;
use Scenario\Core\Runtime\Application;

final class DatabaseRefreshExecutor implements DatabaseRefreshExecutorInterface
{
    public function execute(RefreshDatabase $metaData): void
    {
        $parameters = [ '--force' => true ];
        if ($metaData->connection !== null) {
            $parameters['--database'] = $metaData->connection;
        }

        $connections = Application::config()?->getConnections() ?? [];
        if (isset($connections[$metaData->connection]) === true) {
            $parameters['--path'] = $connections[$metaData->connection]->config;
        }

        Artisan::call('migrate:fresh', $parameters);
    }
}
