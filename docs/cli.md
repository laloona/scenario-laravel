# CLI Usage
This document describes the Scenario CLI integration in a Laravel application.

---

## Overview
Scenario provides Artisan commands for applying and managing scenarios.

Typical use cases:
- preparing local development data
- reproducing bugs
- setting up CI environments
- debugging specific states

---

## Basic Usage

```bash
php artisan scenario:<command>
```
To see all available commands:
```bash
php artisan list scenario
```

## Available Commands
- __scenario:install__: Adds the Scenario extension to PHPUnit and prepares the project.
- __scenario:list__: List all available scenarios.<br>
  Available options (optional):
```bash
--suite=<name>
```
- __scenario:apply__: Apply a scenario. Argument (optional): `scenario`<br>
  Available options (optional):
```bash
--up       Apply the scenario (default)
--down     Revert the scenario
--audit    Print out the audit
```
Parameters can be passed as CLI options:
```bash
php artisan scenario:apply create-user --parameter=email=test@example.com
```
If parameters are not provided:
- you may be prompted interactively
- defaults will be used if defined
  If required parameters are missing, the CLI may ask:
```bash
Please insert value for string parameter "email" (required)
>
```
- __scenario:debug__: Inspect a scenario or test. Arguments (optional): `class method`<br>
Use this to:
  - verify scenario resolution
  - inspect applied scenarios
  - debug execution flow
- __scenario:make:scenario__: Generate a new scenario.
- __scenario:make:parameter__: Generate a new parameter type.
- __scenario:refresh__: Execute database or environment refresh logic. Available options (optional):
```bash
--connection=<name>
```
> **Note:** The actual database handling depends on your Laravel setup (e.g. `migrate:fresh or custom connections)

---

## Next Steps

- [Testing with PHPUnit](testing-with-phpunit.md)
- [Recipes](recipes.md)