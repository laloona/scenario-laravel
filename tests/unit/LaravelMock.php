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

use Illuminate\Config\Repository;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use Stateforge\Scenario\Laravel\Runtime\ProcessInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function str_contains;

trait LaravelMock
{
    private Application $app;

    private function getLaravelMock(): Application
    {
        return $this->app;
    }

    private function setUpFacades(): void
    {
        $this->app = new Application(__DIR__);
        $this->app->instance('config', new Repository([]));

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
    }

    private function tearDownFacades(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    private function setUpInstalled(bool $isInstalled, int $times): Filesystem&MockInterface
    {
        /** @var Filesystem&MockInterface $filesystem */
        $filesystem = Mockery::mock(Filesystem::class);

        /** @var Expectation $expectation */
        $expectation = $filesystem->shouldReceive('exists');
        $expectation->times($times)
            ->with(Mockery::on(
                static fn (string $path): bool => str_contains($path, 'scenario.xml') || str_contains($path, 'scenario.dist.xml'),
            ))
            ->andReturn($isInstalled);

        $this->app->instance('files', $filesystem);

        return $filesystem;
    }

    private function getProcessMock(): ProcessInterface&MockInterface
    {
        /** @var ProcessInterface&MockInterface $process */
        $process = Mockery::mock(ProcessInterface::class);
        $this->app->instance(
            ProcessInterface::class,
            $process,
        );

        $this->app->bind(
            OutputStyle::class,
            /**
             * @param array<string, mixed> $params
             */
            static function (Application $app, array $params): OutputStyle {
                /** @var InputInterface $input */
                $input = $params['input'];

                /** @var OutputInterface $output */
                $output = $params['output'];

                return new OutputStyle($input, $output);
            },
        );

        $this->app->bind(Factory::class, function () {
            return Mockery::mock(Factory::class);
        });

        return $process;
    }
}
