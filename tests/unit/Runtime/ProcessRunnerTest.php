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

#[CoversClass(ProcessRunner::class)]
#[Group('runtime')]
#[Small]
final class ProcessRunnerTest extends TestCase
{
    public function testRunReturnsTrueForSuccessfulProcess(): void
    {
        self::assertTrue((new ProcessRunner())->run(['/bin/sh', '-c', 'exit 0'], '/tmp', null));
    }

    public function testRunReturnsFalseForFailedProcess(): void
    {
        self::assertFalse((new ProcessRunner())->run(['/bin/sh', '-c', 'exit 1'], '/tmp', null));
    }

    public function testRunWritesProcessOutputWhenOutputStyleIsProvided(): void
    {
        $output = $this->createMock(OutputStyle::class);
        $output->expects(self::once())
            ->method('write')
            ->with(self::stringContains('hello'));

        self::assertTrue((new ProcessRunner())->run(['/bin/sh', '-c', 'printf hello'], '/tmp', $output));
    }
}
