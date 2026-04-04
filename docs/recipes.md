# Recipes
This document contains practical examples of how to use Scenario in a Laravel application.

---

## Create a User for Tests

### Problem
Many tests require a user to exist.

### Solution
```php
#[AsScenario('user-exists')]
#[Parameter('id', ParameterType::Integer, required: true)]
final class UserExists extends Scenario
{
    public function up(int $id): void
    {
        User::create(['id' => $id]);
    }
}
```

Use in tests:
```php
#[ApplyScenario(UserExists::class, ['id' => 42])]
```

## User with Subscription

### Problem
You need a user with an active subscription.

### Solution
```php
#[AsScenario('user-with-subscription')]
#[ApplyScenario(UserExists::class)]
final class UserHasSubscription extends Scenario
{
    public function up(): void
    {
        $user = $this->repository(User::class)->findOneBy([]);

        $subscription = new Subscription($user);
        $this->entityManager()->persist($subscription);
        $this->entityManager()->flush();
    }
}
```

## Combine Multiple States

### Problem
Tests require a fully prepared system state.

### Solution
```php
#[ApplyScenario(UserExists::class, ['id' => 42])]
#[ApplyScenario(UserHasSubscription::class)]
final class SubscriptionTest extends TestCase
{
}
```

## Reset Database Before Test

### Problem
Tests interfere with each other.

### Solution
```php
#[RefreshDatabase]
final class MyTest extends TestCase
{
}
```

## Run Artisan Commands in a Scenario

### Problem
Your application already has an Artisan command for setup logic.

### Solution
```php
$this->command('app:import-users', [
    '--file' => 'storage/users.csv',
]);
```

## Dispatch Events

### Problem
You need to trigger application events.

### Solution
```php
$this->event(new UserRegistered($user));
```

## Dispatch Jobs

### Problem
Your application uses queued jobs.

### Solution
```php
$this->message(new SendWelcomeEmail($user));
$this->consumer('default');
```

## Work with Files

### Problem
You need to create or manipulate files.

### Solution
```php
$file = $this->absoluteFile('storage/users.csv', true);
$this->filesystem()->put($file, 'content');
```

## Reproduce a Bug Locally

## Problem
A bug only occurs with specific data.

## Solution
1.	Create a scenario:
```php
#[AsScenario('bug-123-state')]
final class Bug123State extends Scenario
{
    public function up(): void
    {
        // prepare exact failing state
    }
}
```
2.	Apply it:
```bash
php artisan scenario:apply bug-123-state
```
Now you can debug the issue in a reproducible environment.