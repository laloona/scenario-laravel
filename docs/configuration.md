# Configuration
This document explains the Scenario configuration used by the Scenario Laravel adapter.

The Laravel adapter uses the shared Scenario XML configuration format from Scenario Core, but provides Laravel-specific installation and database integration.

---

## Overview
Scenario Laravel uses the same XML configuration format as Scenario Core.

The configuration defines how Scenario discovers scenarios, parameter types, bootstrap files, cache directories, and optional database integrations.

The Laravel adapter adds Laravel-specific defaults during installation and bootstrap.

---

## Creating the Configuration
The Laravel adapter provides an install command that generates the configuration automatically:

```bash
php artisan scenario:install
```
This command creates the XML configuration file in the project root directory and also generates the required Laravel bootstrap file.

## Default File Location
The default configuration file name is:
```text
scenario.dist.xml
```
located in the project root directory.

Projects may copy this file to `scenario.xml` for local customization.

## Example Configuration
```xml
<scenario xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:noNamespaceSchemaLocation="vendor/stateforge/scenario-core/xsd/scenario.xsd"
          cacheDirectory=".scenario.cache"
          parameterDirectory="scenario/parameter"
>
    <database>
        <connection name="mysql">database/migrations/main.php</connection>
    </database>

    <suites>
        <suite name="Main Scenario Suite">
            <directory>scenario/main</directory>
        </suite>
    </suites>

    <extensions>
        <bootstrap class="Stateforge\Scenario\Laravel\Extension"/>
    </extensions>
</scenario>
```

### Root Element

```xml
<scenario>
```
This is the root element of the configuration.

#### Attributes
##### cacheDirectory
```xml
cacheDirectory=".scenario.cache"
```
Directory used for generated cache files.

Scenario caches discovered scenarios and parameter types for faster startup.

##### parameterDirectory
```xml
parameterDirectory="scenario/parameter"
```
Directory containing custom parameter type classes.

All compatible parameter types in this directory are discovered automatically.

### Extensions
The `<extensions>` element registers framework adapters and custom initialization logic.
Place it after `<suites>` and keep the Laravel adapter registered:

```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Laravel\Extension" />
</extensions>
```

The adapter extension connects Scenario Core to Laravel's application bootstrap and
registers the framework-specific scenario and database refresh handlers.

Each `<bootstrap>` element requires a `class` attribute naming an autoloadable class
that implements `Stateforge\Scenario\Core\Extension`. Scenario instantiates the class
without constructor arguments. The element has no text content.

You can add custom extensions alongside the adapter:

```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Laravel\Extension" />
    <bootstrap class="App\Scenario\Extension" />
</extensions>
```

For custom extensions, `application()` returns an
`Stateforge\Scenario\Core\Runtime\ApplicationExtension` or `null`.
Its `prepare(Application $application)` hook runs before parameter types and scenarios
are loaded; its `boot(Application $application)` hook runs during Scenario application bootstrap.

The `cli()` method returns a `Stateforge\Scenario\Core\Console\CliApplicationExtension`
or `null`. Its `boot(CliApplication $cli)` hook can register commands for Scenario Core's
`vendor/bin/scenario` CLI. Framework console commands are registered through Laravel's
own command integration.

### Suites
Suites organize scenario classes into logical groups.

Example:
```xml
<suites>
    <suite name="main" description="Main Scenario Suite">
        <directory>scenario/main</directory>
    </suite>
    <suite name="cli" description="Cli for Tests">
        <directory>scenario/cli</directory>
    </suite>
</suites>
```

#### Suite Attributes
##### name
Human-readable suite name.
Used in CLI output and interactive commands.

##### description
Human-readable suite name.
Used in CLI output.

#### directory
Directory containing scenario classes.

All scenarios inside this directory are discovered automatically.

### Multiple Suites
You may define multiple suites.

This is useful for separating:
- test scenarios
- development scenarios
- fixtures
- domain-specific states

### Database Configuration
Optional database connections may be configured.
```xml
<database>
    <connection name="mysql">database/migrations/main.php</connection>
</database>
```
In the Laravel adapter, the name attribute is interpreted as the Laravel database connection name.

The value of the `<connection> element may be used as an optional migration path or migration context, depending on your project setup.

This allows Scenario Laravel to delegate database migration setup to Artisan and Laravel’s migration system.

#### Example:
```xml
<database>
    <connection name="mysql">database/migrations/main.php</connection>
</database>
```

This will be used like:
```bash
php artisan migrate --database=mysql --path=database/migrations/main.php --force
```

#### Why This Matters
Scenario Laravel does not require you to implement database migration logic manually.

Instead, it integrates with Laravel’s migration system and uses your configured connection and migration path.

This keeps the database refresh process aligned with normal Laravel project conventions.

#### Multiple Connections
You may define multiple named connections if your project uses more than one migration setup.

Example:
```xml
<database>
    <connection name="mysql">database/migrations/main.php</connection>
    <connection name="reporting">database/migrations/reporting.php</connection>
</database>
```

## Schema Validation
The configuration file is validated against the bundled XML schema:
```xml
vendor/stateforge/scenario-core/xsd/scenario.xsd
```
Invalid configuration values will fail early during bootstrap.

## Best Practices
- keep the file in project root
- use descriptive suite names
- separate scenarios into multiple suites when needed
- keep the generated Laravel bootstrap file
- use migration paths that match your normal Laravel project setup
- use correct Laravel connection names
- commit the configuration file to version control

---

## Next Steps
- [Scenarios](scenarios.md)
- [Parameter Types](parameter-types.md)
- [CLI Usage](cli.md)
- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)
