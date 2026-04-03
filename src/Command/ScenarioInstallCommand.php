<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Command;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Scenario\Core\PHPUnit\Configuration\ConfiguredInterface;
use Scenario\Laravel\Facades\Shell;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class ScenarioInstallCommand extends ScenarioCommand
{
    protected bool $requiresInstallation = false;

    protected $signature = 'scenario:install {--force}';

    protected $description = 'Install the Scenario Package (local/develop/testing only)';

    public function __construct(
        private readonly ConfiguredInterface $configured,
    ) {
        parent::__construct();
    }

    protected function executeCommand(): int
    {
        if ($this->confirm('Do you want to install Scenario?', true) === false) {
            $this->error('Scenario installation aborted.');
            return self::FAILURE;
        }

        File::ensureDirectoryExists(
            App::basePath('scenario'),
        );

        $this->copyBlueprint(
            'bootstrap.blueprint',
            App::basePath('scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php'),
        );

        File::ensureDirectoryExists(
            App::basePath('scenario' . DIRECTORY_SEPARATOR . 'main'),
        );

        $this->copyBlueprint(
            'config.blueprint',
            App::basePath('scenario.dist.xml'),
        );

        if ($this->isInstalled() === true) {
            if ($this->configured->isConfigured() === false
                && $this->confirm('Do you want to add configuration to PHPUnit?', true)) {

                $this->configurePHPUnit();
                if ($this->configured->isConfigured() === false) {
                    $this->error('Configuring PHPUnit failed.');
                }
            }

            $this->info('Scenario was successfully installed.');
            return self::SUCCESS;
        }

        $this->error('Scenario installation failed.');
        return self::FAILURE;
    }

    private function copyBlueprint(string $source, string $target): void
    {
        $sourcePath = $this->getBlueprint($source);
        if (File::exists($sourcePath) === false) {
            return;
        }

        if (File::exists($target) === false) {
            File::copy($sourcePath, $target);
        }
    }

    private function configurePHPUnit(): void
    {
        Shell::run(
            [
                PHP_BINARY,
                $this->getCliPath(),
                'install',
                '--force',
                '--quiet',
            ],
            App::basePath(),
            null,
        );
    }
}
