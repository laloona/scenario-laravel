# Scenario Laravel

Laravel integration for Stateforge Scenario Core.

This package provides framework-specific integration for Laravel applications,
enabling seamless scenario execution within PHPUnit tests and Artisan workflows.

It builds on top of ``stateforge/scenario-core`` and integrates with the Laravel container,
Artisan console, and testing environment.

## Requirements

Scenario Laravel requires the following:

* PHP >= 8.2
* Laravel 12+
* [stateforge/scenario-core](https://github.com/laloona/scenario-core)

## Installation

> This package is intended for local, develop and testing use only.

<pre><code>
composer require --dev stateforge/scenario-laravel
</code></pre>

After installation, run the setup command:

<pre><code>
php artisan scenario:install
</code></pre>

The installation command generates the required configuration files:
* creates ``scenario/bootstrap.php`` for runtime bootstrapping
* generates ``scenario.dist.xml`` for configuration
* places the extension into ``phpunit.xml`` or ``phpunit.dist.xml``

## What This Package Provides

Scenario Laravel integrates Scenario Core with:

* Laravel’s service container
* Laravel Artisan console
* Laravel application lifecycle
* PHPUnit integration

## Service Provider

The package automatically registers its service provider.

It handles:
* registering console commands
* wiring scenario services
* integrating the Scenario runtime with Laravel

## Database Reset

When using ``#[RefreshDatabase]``, the Laravel integration resets the database
using Laravel’s migration system.

The default behavior:
* runs ``migrate:fresh``
* optionally supports connection-specific resets

## Applying Scenarios in Unit Tests

Scenarios can be applied declaratively using the ```#[ApplyScenario]``` attribute:

<pre><code type="php">&lt;?php
use Stateforge\Scenario\Core\Attribute\ApplyScenario;

#[ApplyScenario('my-scenario')]
final class MyTest extends TestCase
{
    #[ApplyScenario('my-second-scenario')]
    public function testSomethingImportant(): void
    {
        // scenario has already been applied, data can be tested
    }
}
</code></pre>

## Console Commands

Scenario Laravel registers dedicated Artisan commands within your application.

You can discover them using:

<pre><code>
php artisan list scenario
</code></pre>

Available commands include:

* ``scenario:list`` – List available scenarios
* ``scenario:apply`` – Apply a scenario manually
* ``scenario:debug`` – Debug scenarios or unit tests
* ``scenario:make`` – Generate a new scenario
* ``scenario:install`` – Install Scenario into the project

## Environment Restrictions

Scenario commands are intended for local, development and testing environments only.

By default, they are restricted to:

* ``local``
* ``develop``
* ``testing``

This can be configured via:

```php
config('scenario.allowed_envs');