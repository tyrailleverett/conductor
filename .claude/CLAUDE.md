# PHP Code Quality Standards

This project uses **Pint**, **Duster**, **Rector**, and **Larastan** to enforce strict code quality standards through automated formatting, linting, and static analysis.

## Quick Reference

- **Format code**: `./vendor/bin/pint`
- **Lint & fix**: `composer lint` (runs Duster + Rector)
- **Static analysis**: `./vendor/bin/phpstan analyse`
- **Run tests**: `composer test`

---

## Core Principles

Write code that is **type-safe, maintainable, and well-tested**. Focus on clarity and explicit intent over brevity.

### PHP Standards

- Always use `declare(strict_types=1)` in every PHP file
- Use PHP 8.4+ features: constructor property promotion, enums, named arguments, fibers
- Always use explicit return type declarations for methods and functions
- Use appropriate PHP type hints for method parameters
- Prefer `final` classes unless extension is explicitly needed
- Use strict comparison (`===`) over loose comparison (`==`)
- Always use curly braces for control structures, even for single-line bodies

### Class Organization

- Follow the ordered class elements convention:
  1. Traits, Cases, Constants
  2. Properties (public, protected, private)
  3. Constructor, Destructor, Magic methods
  4. Abstract methods
  5. Static methods (public, protected, private)
  6. Instance methods (public, protected, private)

### Error Handling

- Throw typed exceptions with descriptive messages
- Prefer early returns over nested conditionals for error cases
- Use `try-catch` blocks meaningfully

### Testing

- This project uses Pest for testing
- Write tests for every change
- Run tests with `composer test`
- Use factories for model creation in tests

---

Formatting and common issues are automatically fixed by Pint and Duster. Run `composer lint` before committing to ensure compliance.
