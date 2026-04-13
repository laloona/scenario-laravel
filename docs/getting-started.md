# Getting Started
This guide complements the README and focuses on practical usage patterns in a Laravel application.

---

## When to use Scenario
Scenario is useful when:
- test setup becomes complex
- fixtures are hard to maintain
- application state needs to be reproducible
- debugging requires consistent environments

---

## Typical Workflow
1. Define a scenario
2. Apply it in tests or via Artisan
3. Compose scenarios for more complex states

---

## Using Parameters Effectively
Example:
```php
#[Parameter('userId', ParameterType::Integer, required: true)]
```

Tips:
- Use parameters for reusable scenarios
- Prefer explicit values over hidden defaults
- Validate inputs using ParameterType

## Scenario Composition
Scenarios can apply other scenarios:
```php
#[ApplyScenario(UserExists::class)]
#[ApplyScenario(UserHasSubscription::class)]
```

This allows building complex application states from smaller building blocks.

---

## CLI vs PHPUnit
Use Artisan when:
- preparing local state
- debugging scenarios
- creating or applying scenarios manually
```php
php artisan scenario:apply
```

Use PHPUnit when:
- writing tests
- verifying behavior
```php
#[ApplyScenario(MyScenario::class)]
```

---

## Database State

When using:
```php
#[RefreshDatabase]
```
The Laravel adapter resets the database using Laravel’s migration system:
- runs migrate:fresh
- optionally supports connection-specific resets

---

## Best Practices
- Keep scenarios small and focused
- Avoid hidden side effects
- Prefer composition over large scenarios
- Use clear naming

---

## Next Steps
- [Scenarios](scenarios.md)
- [Parameter Types](parameter-types.md)
- [CLI Usage](cli.md)
- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)
