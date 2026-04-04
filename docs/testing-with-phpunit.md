# Testing with PHPUnit

This document explains how Scenario integrates with PHPUnit in a Laravel application.

---

## Why use Scenario in tests?
Traditional test setup often involves:
- manual fixture creation
- duplicated setup logic
- hard-to-maintain test data

Scenario replaces this with **declarative state definition**.

Instead of writing setup code, you describe the desired state:

```php
#[ApplyScenario(UserExists::class)]
```

## Enabling PHPUnit Integration
Make sure the Scenario PHPUnit extension is registered:
```xml
<extensions>
    <bootstrap class="Stateforge\Scenario\Core\PHPUnit\Extension" />
</extensions>
```

Alternatively, the CLI install command can configure PHPUnit automatically:
```bash
php artisan scenario:install
```
This ensures that all scenario attributes are processed before test execution.

## Laravel Test Environment
Scenario integrates with the Laravel application container:
- services are resolved via the container
- Eloquent models are available
- events, jobs, and queues can be used

This allows scenarios to interact with real application behavior.

## Applying Scenarios

### At class level
```php
use Stateforge\Scenario\Core\Attribute\ApplyScenario;

#[ApplyScenario(UserExists::class)]
final class MyTest extends TestCase
{
}
```
The scenario is applied before each test method.

### At method level
```php
#[ApplyScenario(UserExists::class)]
public function testSomething(): void
{
}
```

### Combining both
```php
#[ApplyScenario(UserExists::class)]
final class MyTest extends TestCase
{
    #[ApplyScenario(UserHasSubscription::class)]
    public function testAccess(): void
    {
    }
}
```
Execution order:
1. class-level scenarios
2. method-level scenarios

## Passing Parameters
```php
#[ApplyScenario(CreateUserScenario::class, ['email' => 'test@example.com'])]
```
Parameters are validated before execution.

## Resetting State
Use `#[RefreshDatabase]` to ensure a clean environment:

```php
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

#[RefreshDatabase]
final class MyTest extends TestCase
{
}
```

In Laravel, the database reset is handled via migrations:
- runs `migrate:fresh`
- optionally supports connection-specific resets


## Scenario Composition in Tests
You can compose complex states:
```php
#[ApplyScenario(UserExists::class, ['id' => 42])]
#[ApplyScenario(UserHasSubscription::class, ['id' => 42])]
final class SubscriptionTest extends TestCase
{
}
```
This keeps tests:
- readable
- reusable
- focused

## Error Handling
If a scenario fails:
- the test fails immediately
- the exception is wrapped and reported via PHPUnit

__Example:__
- application failure → `ApplicationFailureException`
- class-level failure → `TestClassFailureException`
- method-level failure → `TestMethodFailureException`

## Summary
Scenario is not a replacement for:
- unit-level object testing
- pure domain logic tests

Use it when:
- state preparation is complex
- integration behavior is tested
- data dependencies exist

---

## Next Steps

- [Recipes](recipes.md)