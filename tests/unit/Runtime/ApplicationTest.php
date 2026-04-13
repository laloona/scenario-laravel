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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Core\Runtime\Application as CoreApplication;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Laravel\Runtime\Application;
use function define;
use function defined;
use const DIRECTORY_SEPARATOR;

#[CoversClass(Application::class)]
#[Group('runtime')]
#[Small]
final class ApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setCoreConfiguration(null);
    }

    public function testPrepareAddsLaravelParameterDirectoryToConfiguration(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects(self::once())
            ->method('addParameterDirectory')
            ->with(
                'vendor' . DIRECTORY_SEPARATOR .
                'stateforge' . DIRECTORY_SEPARATOR .
                'scenario-laravel' . DIRECTORY_SEPARATOR .
                'src' . DIRECTORY_SEPARATOR . 'Parameter',
            );

        $this->setCoreConfiguration($configuration);

        (new Application())->prepare();
    }

    public function testPrepareDoesNothingWhenCoreConfigurationWasNotLoaded(): void
    {
        $this->setCoreConfiguration(null);

        (new Application())->prepare();

        self::assertNull(CoreApplication::config());
    }

    #[RunInSeparateProcess]
    public function testBootDoesNothingWhenScenarioCliDisabledIsNotDefined(): void
    {
        $this->setCoreConfiguration(self::createStub(Configuration::class));

        (new Application())->boot();

        self::assertFalse(defined('SCENARIO_CLI_DISABLED'));
    }

    public function testBootDoesNothingWhenCoreConfigurationWasNotLoaded(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false) {
            define('SCENARIO_CLI_DISABLED', true);
        }

        $this->setCoreConfiguration(null);

        (new Application())->boot();

        self::assertNull(CoreApplication::config());
    }

    private function setCoreConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(CoreApplication::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }
}
