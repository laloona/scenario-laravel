# Scenarios
This document explains how scenarios are defined, composed, and used in Scenario Laravel.

It focuses on structure, behavior, framework integration, and best practices.

---

## What is a Scenario?
A scenario describes a **reproducible application state**.

In Laravel, a scenario is a PHP class that:
- extends `Stateforge\Scenario\Laravel\Scenario`
- is marked with `#[AsScenario]`
- defines how a state is created (`up`) and optionally removed (`down`)

## Basic Structure

```php
<?php declare(strict_types=1);

use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Laravel\Scenario;

#[AsScenario('create-user')]
final class CreateUserScenario extends Scenario
{
    public function up(): void
    {
        // create data
    }

    public function down(): void
    {
        // optional cleanup
    }
}
```

## Scenario Naming
The name defined in `#[AsScenario]` is used:
- in CLI (`php artisan scenario:apply create-user`)
- in `#[ApplyScenario]`
- as a unique identifier

### Best practices
- use descriptive names: user-with-subscription
- keep names stable (avoid breaking references)
- prefer kebab-case, snake_case or camelCase

## Applying Scenarios
Scenarios are applied using the `#[ApplyScenario]` attribute.
```php
use Stateforge\Scenario\Core\Attribute\ApplyScenario;

#[ApplyScenario('create-user')]
final class MyTest extends TestCase
{
}
```
You can also reference the class directly:
```php
#[ApplyScenario(CreateUserScenario::class)]
```

## Scenario Composition
Scenarios can apply other scenarios.
```php
#[ApplyScenario(UserExists::class)]
#[ApplyScenario(UserHasSubscription::class)]
final class UserReadyScenario extends Scenario
{
    public function up(): void {}
}
```

### Why composition matters
- reuse smaller building blocks
- avoid duplication
- keep scenarios focused

### Execution Order
Scenarios are executed in the order they are applied.
- class-level attributes run before method-level attributes
- composed scenarios run before the current scenario

### Parameters
Scenarios can define parameters using `#[Parameter]`.
```php
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Core\Runtime\Metadata\ParameterType;

#[AsScenario('create-user')]
#[Parameter('email', ParameterType::String, required: true)]
final class CreateUserScenario extends Scenario
{
    public function up(string $email): void
    {
        // use parameter
    }
}
```

### Passing parameters

#### CLI
```bash
php artisan scenario:apply create-user --parameter=email=test@example.com
```

#### PHPUnit
```php
#[ApplyScenario(CreateUserScenario::class, ['email' => 'test@example.com'])]
```

### Parameter Behavior
- parameters are validated before execution
- invalid values throw exceptions
- optional parameters may define defaults

## Repeatable Parameters
In some cases, a parameter may be provided multiple times.

This is useful when a scenario needs to work with a list of values.

### Example
```php
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Core\Runtime\Metadata\ParameterType;

#[AsScenario('create-users')]
#[Parameter('email', ParameterType::String, repeatable: true)]
final class CreateUsersScenario implements ScenarioInterface
{
    /**
     * @param list<string|null> $email
     */
    public function up(array $email): void
    {
        foreach ($email as $value) {
            if ($value === null) {
                continue;
            }

            // create user for each email
        }
    }
}
```

### Passing Repeatable Parameters

#### CLI
You can pass the same parameter multiple times:
```bash
php artisan scenario:apply create-users --parameter=email=first@example.com --parameter=email=second@example.com
```

#### PHPUnit

Provide values as a list:
```php
#[ApplyScenario(CreateUsersScenario::class, [
    'email' => [
        'first@example.com',
        'second@example.com',
    ],
])]
```

### Behavior
- repeatable parameters are always passed as a list
- each occurrence is added to the list in order
- values may be null if no value was provided
- validation is applied to each individual value

### When to use repeatable parameters
Use repeatable parameters when:
- creating multiple entities of the same type
- applying bulk operations
- working with flexible input sets

### Best Practices
- Prefer repeatable parameters over comma-separated strings
- Keep parameter names singular (email, not emails)
- Always handle null values explicitly if allowed
- Keep logic simple — iterate over values and apply the same operation

---

## Laravel Scenario Helpers
The Laravel base scenario class provides additional helper methods for framework integration.

### Project paths
- `rootDir()`: Returns the Laravel project root directory.
```php
$root = $this->rootDir();
```
- `absoluteDir()`: Resolves a directory relative to the project root.
```php
$path = $this->absoluteDir('var/data', true);
```
If the second argument is true, the directory is created if it does not exist.
- `absoluteFile()`: Resolves a file path relative to the project root.
```php
$file = $this->absoluteFile('var/data/users.csv', true);
```
If the second argument is true, missing parent directories are created.

### Configuration
- `config()`: Reads configuration values through Laravel’s config system.
```php
$value = $this->config('app.some_key', 'default value');
```
If the second argument is provided, it is the default value when the config value is null.

### Artisan Commands
- `command()`: Executes an Artisan command from within a scenario.
```php
$this->command('app:import-users', [
    '--file' => 'var/data/users.csv',
]);
```
Use this when your application already provides a command for the setup step.

### Filesystem
- `filesystem()`: Returns a Laravel Filesystem instance.
```php
$this->filesystem()->put($file, 'content');
```

### Models
- `model()`: Builds a model instance through the Laravel container.
```php
$user = $this->model(User::class);
```
This is useful when model creation depends on container wiring or custom construction.

### Events
- `event()`: Dispatches a Laravel event.
```php
$this->event(new UserImported($user));
```

### Jobs / Messages
- `message()`: Dispatches a job through Laravel’s bus.
```php
$this->message(new ImportUsersJob());
```
- `consumer()`: Consumes jobs from the given queue.
```php
$this->consumer('default');
```
This is useful when a scenario triggers asynchronous behavior that should be processed immediately.

### Shell Commands
- `shell()`: Executes a shell command and returns whether it was successful.
```php
$this->shell([
    PHP_BINARY,
    'bin/console',
    'cache:clear',
]);
```
Use this only when the task cannot be expressed more directly through Laravel services or Artisan commands.

---

## Up and Down Methods

### up()
Defines how the state is created:
- should be deterministic
- should not depend on external state

### down()
Optional cleanup method.<br>
Use when:
- state needs to be reverted
- scenarios are used in reversible workflows

## Idempotency
Scenarios should ideally be idempotent:
- running them multiple times should not break the system
- avoid duplicate data creation
- check existing state when needed

## Error Handling
Scenario Laravel handles:
- invalid parameters
- missing scenarios
- execution failures
- framework integration failures

Failures are surfaced via exceptions and PHPUnit integration.

## Best Practices
- Keep scenarios small: avoid large, monolithic scenarios
- Prefer composition: build complex states from smaller scenarios
- Avoid hidden dependencies
- Make dependencies explicit via:
    - composition
    - parameters
- Use clear naming: names should describe the resulting state, not the implementation
- Keep logic minimal: scenarios should orchestrate state, not contain business logic
- Prefer Laravel services, models, events, jobs, and Artisan commands over raw shell calls

---

## Next Steps

- [CLI Usage](cli.md)
- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)
