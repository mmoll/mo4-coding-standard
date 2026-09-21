# MO4 Coding Standard - Agent Guide

## Setup & Dependencies

This is a PHP coding standard library (PHPCS ruleset) that implements the **MO4** style. It extends **Symfony Coding Standard** with MO4-specific rules. It uses PHP_CodeSniffer 4.x.

```bash
composer install
```

Requires: PHP, Composer (strongly recommended)

## Core Commands

### Code Analysis (CLI)

```bash
# Run CodeSniffer (lint)
./vendor/bin/phpcs --standard=MO4 path/to/file.php

# Auto-fix violations
./vendor/bin/phpcbf --standard=MO4 path/to/file.php

# Verify MO4 is installed
./vendor/bin/phpcs -i
```

### Testing

Run tests in order:

```bash
# Unit tests
./vendor/bin/phpunit

# Integration tests (CI requires these to pass first)
./vendor/bin/phpcs --standard=MO4 integrationtests/testfile.php

# Static analysis (all must pass)
./vendor/bin/phpstan analyse --no-progress
./vendor/bin/psalm
./vendor/bin/phan -i

# Quality tools (CI)
./vendor/bin/qlty
```

## Project Structure

- **`MO4/Sniffs`**: Core ruleset classes (each is a separate rule)
- **`MO4/Tests/`**: Unit test cases for individual sniffs
  - `AbstractMo4SniffUnitTestCase.php`: Base test class for sniff tests
- **`integrationtests/`**: Integration tests for phpcs CLI usage
- **`.phan/`**: Phan static analysis config
- **`phpstan.neon`**: PHPStan config
- **`psalm.xml`**: Psalm config
- **`phpcs.mo4.xml`**: PHPCS standard file
- **`phpcs.xml.dist`**: Local phpcs ruleset for MO4 code

## Key Conventions

### Rule Organization

The MO4 rules are organized into subdirectories under `MO4/Sniffs/`:
- **Arrays**: Array formatting rules
- **Commenting**: Docblock and property comment rules
- **Formatting**: General formatting and code structure rules
- **Strings**: String and interpolation rules
- **WhiteSpace**: Spacing and indentation rules

### Sniff Naming Convention

- Sniff names must be PascalCase (e.g., `ArrayAlignment`)
- Class names must be PascalCase (e.g., `MO4.Arrays.ArrayAlignment`)

### Doc Blocks

- Class doc blocks are optional if they add no value
- Properties must have multiline doc blocks with exactly one `@var` annotation

### Auto-fixing

Most MO4 issues can be auto-fixed with `phpcbf`. Always prefer `phpcbf` before manual fixes.

### Testing

- Test case classes must extend `AbstractMo4SniffUnitTestCase`
- Unit tests for MO4 rules live under `MO4/Tests/`
- Integration tests verify phpcs rules apply correctly to sample files without MO4 class structure

### CI Checks (required before merge)

* `composer normalize --dry-run` - checks composer.json formatting
* XML schema validation for phpcs ruleset files
* `diff -B` consistency checks for formatted XML files
* Parallel analysis: `--parallel` usage in phpcs for speed

## CI Requirements

All tools must pass before merge. Tests run on PHP 8.1, 8.2, 8.3, 8.4, 8.5 (Windows 8.3 only). Coverage is uploaded to Codecov and Qlty.
