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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Laravel\Extension;
use Stateforge\Scenario\Laravel\Runtime\Application;

#[CoversClass(Extension::class)]
#[Group('scenario')]
#[Small]
final class ExtensionTest extends TestCase
{
    public function testApplicationReturnsLaravelApplication(): void
    {
        $extension = new Extension();

        self::assertInstanceOf(Application::class, $extension->application());
    }

    public function testCliReturnsNull(): void
    {
        $extension = new Extension();

        self::assertNull($extension->cli());
    }
}
