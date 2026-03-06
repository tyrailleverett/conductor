# Copilot Code Review Instructions

## PHP & Code Standards

- [ ] Every PHP file has `declare(strict_types=1)`.
- [ ] Classes are `final` unless extension is explicitly needed.
- [ ] Use PHP 8.4 constructor property promotion. No empty constructors.
- [ ] All methods have explicit return type declarations and parameter type hints.
- [ ] Use strict comparison (`===`), never loose (`==`).
- [ ] Curly braces on all control structures, even single-line bodies.
- [ ] Enum keys are TitleCase.
- [ ] Prefer PHPDoc blocks over inline comments. Flag inline comments unless logic is exceptionally complex.
- [ ] Add array shape type definitions in PHPDoc when appropriate.
- [ ] Use descriptive method/variable names (e.g., `isRegisteredForDiscounts`, not `discount()`).
- [ ] No debug functions: `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`.

## Security

- [ ] No raw queries (`DB::raw`, `whereRaw`, `selectRaw`) without parameter binding.
- [ ] No `{!! !!}` unescaped Blade output without explicit justification.
- [ ] No `exec()`, `shell_exec()`, `system()`, `passthru()`, `proc_open()` with user input.
- [ ] No `unserialize()` on untrusted data.
- [ ] Eloquent models must define `$fillable` or `$guarded`. Flag `$guarded = []`.
- [ ] No hardcoded API keys, passwords, tokens, or credentials in source code.
- [ ] No `md5()` or `sha1()` for security purposes. Use `Hash` facade or `password_hash()`.
- [ ] Validate file types and paths for any file upload/download logic.
- [ ] Validate/allowlist URLs before HTTP requests with user-supplied URLs (SSRF).
- [ ] Check for ReDoS patterns in complex regular expressions.
- [ ] New `composer require` additions must be from trusted, maintained packages.

## Performance

- [ ] No N+1 queries: relationships in loops must use eager loading (`with()`).
- [ ] No database queries inside loops.
- [ ] Use query builder over Collection methods when possible (e.g., `->count()` not `->get()->count()`).
- [ ] Columns in `where`, `orderBy`, `join` clauses must have database indexes in migrations.

## Architecture

- [ ] Classes follow single responsibility principle.
- [ ] Depend on abstractions, not concretions. Flag tight coupling.
- [ ] Flag methods exceeding ~50 lines without clear justification.
- [ ] No new directories outside established structure: `src/`, `config/`, `database/`, `resources/`, `tests/`.
- [ ] Changes to `composer.json` dependencies require explicit approval.

## Testing

- [ ] Every behavior change includes corresponding Pest tests.
- [ ] Flag deleted tests. Test removal requires explicit justification.
- [ ] Test files must have `declare(strict_types=1)`.
- [ ] No placeholder assertions (`assertTrue(true)`). Tests must have meaningful assertions.
- [ ] Cover edge cases, error paths, and boundary conditions.

## Laravel Package Conventions

- [ ] Service providers extend `PackageServiceProvider` from Spatie Laravel Package Tools.
- [ ] Config files in `config/`, migrations in `database/`, views in `resources/`.
- [ ] Code must support both Laravel 11.x and 12.x. Flag version-specific assumptions.
