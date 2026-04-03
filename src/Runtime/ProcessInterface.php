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

interface ProcessInterface
{
    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments, string $directory, ?OutputStyle $output): bool;
}
