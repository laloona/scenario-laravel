# Parameter Types
This document explains the additional parameter types provided by the Scenario Laravel adapter.

These parameter types use the Laravel Validator component and are automatically registered when the adapter is installed.

---

## Overview
The Laravel adapter extends the built-in Scenario Core parameter types with rule-based types for common input formats.

Examples include:
- email addresses
- URLs
- UUIDs
- IP addresses
- dates and times
- numeric constraints
- ASCII string validation
- financial values

---

## Automatic Registration
All Laravel parameter types are registered automatically during bootstrap.

No manual configuration is required.

Some parameter types may depend on optional PHP extensions or installed Laravel components.

If the required dependency is not available, the type is skipped automatically.
This allows optional integrations without forcing additional dependencies.

## Generate Custom Parameter Types
You can generate a new Laravel parameter type using the CLI command:

```php
php artisan scenario:make:parameter
```

The generated class already includes:
- `#[AsParameterType(...)]`
- Laravel validator integration
- a `rules()` method
- a `valueType()` method

You only need to define the validation rules and the resulting value type.

Example Generated Parameter Type:
```php
#[AsParameterType('some useful description')]
final class EmailType extends ParameterTypeDefinition
{
    protected function rules(): array
    {
        return [
            'email',
        ];
    }

    protected function valueType(mixed $value): StringType
    {
        return new StringType($value);
    }
}
```

This makes creating custom Laravel-based parameter types fast and consistent.

## Available Types

### String Validation
| Type | Description |
|------|-------------|
| EmailType | Validates email addresses |
| UrlType | Validates URLs |
| UuidType | Validates UUID values |
| IpType | Validates IPv4 and IPv6 addresses |
| AlphaType | Validates ASCII letters only |
| AlphaNumType | Validates ASCII letters and numbers |
| AlphaDashType | Validates ASCII letters, numbers, dashes and underscores |

### Date and Time
| Type | Description |
|------|-------------|
| DateType | Validates dates |
| DateTimeType | Validates datetime values |
| TimezoneType | Validates timezone identifiers |

### Numeric Types
| Type | Description |
|------|-------------|
| PositiveIntegerType | Validates positive integers |
| PositiveOrZeroIntegerType | Validates positive integers including zero |
| NegativeIntegerType | Validates negative integers |
| NegativeOrZeroIntegerType | Validates negative integers including zero |
| PositiveFloatType | Validates positive floating-point numbers |
| PositiveOrZeroFloatType | Validates positive floating-point numbers including zero |
| NegativeFloatType | Validates negative floating-point numbers |
| NegativeOrZeroFloatType | Validates negative floating-point numbers including zero |
| MoneyType | Validates values greater than or equal to zero with up to two decimal places |

### Example Usage:
```php
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Laravel\Parameter\EmailType;

#[Parameter('email', EmailType::class)]
```

## Checking Installed Types
Use the CLI command to verify which Laravel parameter types were loaded:

```bash
php artisan scenario:parameter
```

This is especially useful when optional dependencies are missing or when developing custom parameter types.

## Why Use Laravel Types?
Laravel parameter types allow you to reuse Laravel’s mature validation rule system inside Scenario definitions.

Benefits:
- reliable validation
- expressive rule syntax
- easy custom extensions
- no custom regex needed for common cases
- clear intent in scenario definitions

## Best Practices
- Use Laravel types for common formats such as email, UUID, URL, or money
- Prefer semantic types over generic String
- Use numeric types to validate scenario input early
- Check the CLI listing when a type is unexpectedly unavailable

---

## Next Steps
- [CLI Usage](cli.md)
- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)