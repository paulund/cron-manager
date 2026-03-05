# Contributing

Thanks for your interest in contributing to Cron Manager.

## Getting started

1. Fork the repository and clone your fork
2. Run `composer setup` to install dependencies and prepare the database
3. Run `composer dev` to start the development server

## Making changes

- Keep changes focused and minimal — one concern per PR
- Follow PSR-12 code style; run `./vendor/bin/pint` before committing
- Write or update tests for any changed behaviour
- Run `composer test` to confirm the test suite passes

## Submitting a pull request

1. Create a branch from `main` with a descriptive name (e.g. `fix/cron-expression-validation`)
2. Make your changes and commit with a clear message
3. Open a PR against `main` and fill in the pull request template
4. A maintainer will review your PR; please respond to any feedback

## Reporting bugs

Use the **Bug report** issue template. Include steps to reproduce, expected behaviour, and actual behaviour.

## Suggesting features

Use the **Feature request** issue template. Describe the problem you're trying to solve, not just the solution.

## Code style

- PHP: PSR-12 via Laravel Pint (`./vendor/bin/pint`)
- Blade: standard Laravel conventions
- No external linting rules beyond Pint for PHP

## Running tests

```bash
composer test

# Single class or method
php artisan test --filter TestClassName
php artisan test --filter test_method_name
```
