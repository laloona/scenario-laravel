<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Runtime;

use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Runtime\ProcessRunner;
use function sys_get_temp_dir;
use const PHP_BINARY;

#[CoversClass(ProcessRunner::class)]
#[Group('runtime')]
#[Small]
final class ProcessRunnerTest extends TestCase
{
    public function testRunReturnsTrueForSuccessfulProcess(): void
    {
        self::assertTrue(
            (new ProcessRunner())->run(
                [PHP_BINARY, '-r', 'exit(0);'],
                sys_get_temp_dir(),
                null,
            ),
        );
    }

    public function testRunReturnsFalseForFailedProcess(): void
    {
        self::assertFalse(
            (new ProcessRunner())->run(
                [PHP_BINARY, '-r', 'exit(1);'],
                sys_get_temp_dir(),
                null,
            ),
        );
    }

    public function testRunWritesProcessOutputWhenOutputStyleIsProvided(): void
    {
        $output = $this->createMock(OutputStyle::class);
        $output->expects(self::once())
            ->method('write')
            ->with(self::stringContains('hello'));

        self::assertTrue(
            (new ProcessRunner())->run(
                [PHP_BINARY, '-r', 'echo "hello";'],
                sys_get_temp_dir(),
                $output,
            ),
        );
    }
}
