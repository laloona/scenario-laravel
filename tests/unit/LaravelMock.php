<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Laravel\Tests\Unit;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Foundation\Application;
use Mockery;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait LaravelMock
{
    private function getLaravelMock(string $directory): Application
    {
        $app = new Application($directory);
        $app->bind(
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

        $app->bind(Factory::class, function () {
            return Mockery::mock(Factory::class);
        });

        return $app;
    }
}
