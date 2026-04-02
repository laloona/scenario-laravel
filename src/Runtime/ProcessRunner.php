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

use Illuminate\Console\OutputStyle;
use Symfony\Component\Process\Process;

final class ProcessRunner
{
    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments, string $directory, ?OutputStyle $output): bool
    {
        $callback = null;

        if ($output !== null) {
            $callback = static function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            };
        }

        $process = new Process($arguments, $directory);
        $process->setTty($output === null && Process::isTtySupported());
        $process->setTimeout(null);
        $process->run($callback);

        return $process->isSuccessful();
    }
}
