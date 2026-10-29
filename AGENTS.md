# Working on Imbo

Imbo is a PHP REST API for storing original images, transforming them on demand, and managing image metadata. Read `CONTRIBUTING.md` and `docs/develop/contributing.rst` for contribution guidelines. Use `composer.json` and `.github/workflows/ci.yaml` as the source of truth for tooling and CI commands.

## Repository map

- `src/`: application code, autoloaded as `Imbo\`.
  - `Application.php` wires the application together; `Resource/`, `Router/`, and `Http/` implement the HTTP API.
  - `EventManager/` and `EventListener/` implement the event-driven request lifecycle.
  - `Image/` contains image loading, preparation, transformations, and output conversion. Individual transformations live in `Image/Transformation/`.
  - `Database/`, `Storage/`, and `Auth/` define adapters and their contracts. Database metadata and image binary storage are separate concerns.
- `adapter-sdk/`: reusable adapter test suites and fixtures, autoloaded as `ImboSDK\`. See `adapter-sdk/README.md` when changing adapters.
- `tests/`: PHPUnit tests, generally mirroring `src/`, using the same `Imbo\` namespaces. Shared fixtures are in `tests/Fixtures/`.
- `features/`: Behat API scenarios; contexts, test configuration, and the development router live in `features/bootstrap/`.
- `config/config.default.php`: default configuration; `public/` and `bin/imbo` are HTTP and CLI entry points.
- `scripts/`: database initialization scripts. `docker-compose.yaml` provides local backing services.
- `docs/`: Sphinx/reStructuredText documentation, including API usage and extension guides.

## Making changes

- Keep changes focused on the requested task. Check the working tree first and preserve unrelated edits and untracked files.
- Follow nearby code and existing interfaces rather than introducing new abstractions or dependencies unnecessarily.
- Preserve public API behavior, configuration keys, event names, and adapter contracts unless the task explicitly changes them. Consider HTTP status codes and error responses as part of the API.
- For bug fixes, add a regression test that demonstrates the bug. Add relevant tests for new behavior and update `docs/` for user-facing changes.
- For image transformations, consider both the resulting pixels and image model state (dimensions and transformation flags). Check input-size calculations when the transformation participates in image preparation.
- Do not edit `vendor/`, generated documentation, build output, or tool caches. Do not change `composer.lock` unless dependency changes are part of the task; use `composer install`, not an incidental `composer update`.
- Treat local configuration such as `config/config.php`, `behat.php`, and `phpunit.xml` as environment-specific. Do not commit credentials or overwrite local settings to make tests pass.

## PHP conventions

- The application targets PHP 8.3 and newer; do not introduce syntax requiring a newer PHP version. Check development-tool requirements separately when setting up the test environment.
- Match the existing `<?php declare(strict_types=1);` header, namespaces, imports, type declarations, and PHPDoc conventions.
- `.editorconfig` defines whitespace: four spaces for PHP, two for YAML/JSON/XML, LF endings, and a final newline.
- `.php-cs-fixer.dist.php` uses `imbo/imbo-coding-standard`; it is the authority for PHP formatting. Avoid unrelated repository-wide formatting changes.
- PHPUnit tests use attributes such as `#[CoversClass(...)]` and `#[DataProvider(...)]`. Coverage metadata is required by `phpunit.dist.xml`; follow neighboring tests and use static data providers.

## Setup and checks

Run commands from the repository root. Install dependencies with `composer install`. Imagick is required; database tests also need the relevant PHP extensions and services. Consult `composer.json`, `phpunit.dist.xml`, and `behat.dist.php` for requirements and connection settings.

Start with checks focused on the changed code:

```sh
vendor/bin/phpunit tests/Image/Transformation/ResizeTest.php
vendor/bin/phpunit --filter testMethodName
composer run cs
```

Use the matching PHPUnit file or filter rather than the example above. To fix formatting only in changed PHP files:

```sh
vendor/bin/php-cs-fixer fix --diff src/path/to/File.php
```

Broader checks used by CI:

```sh
composer run test:unit
composer run sa
composer run cs
```

PHPStan has separate scripts and levels: `sa:adapter-sdk`, `sa:features`, `sa:src`, and `sa:tests`. Use the relevant scripts during development; do not raise levels or suppress unrelated findings as part of a routine change.

Despite its name, `test:unit` includes tests that need real databases. For the full suite, provision the services and schemas as shown in CI; do not assume every PHPUnit test is isolated.

### API integration tests

Behat tests require a running Imbo instance and the backing services for the selected suite. Test setup can clear test data; use only dedicated test databases and storage.

1. Start the needed services with `docker compose up -d mongo mysql postgres` (or just the service needed). The Compose setup initializes MySQL/PostgreSQL schemas on first startup.
2. For SQLite, run `composer run dev:sqlite` to initialize its schema.
3. In a separate terminal, run `composer run dev` to serve the test application at `http://localhost:8080`.
4. Run a relevant suite or feature, for example:

   ```sh
   vendor/bin/behat --strict --suite=sqlite-filesystem features/image-transformations.feature
   ```

Available suites are listed in `behat.dist.php`. `composer run test:integration` runs all of them; `composer run test` runs integration tests followed by PHPUnit. Do not use either as a prerequisite-free smoke test.

### Documentation

Install the Python dependencies in `docs/requirements.txt` when needed. `composer run docs` runs Sphinx spelling checks and the HTML build. Add legitimate new terminology to `docs/spelling_wordlist.txt` rather than disabling spelling checks.

## Branches and commits

- Never create commits on `main`. Before committing, check the current branch and create or switch to a descriptive feature branch. Preserve any existing working-tree changes when switching branches.
- Use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages: `<type>[optional scope]: <description>`. For example, `fix(image): handle crop boundaries` or `docs: add agent guidelines`.
- Choose a type that describes the change, such as `feat`, `fix`, `docs`, `test`, `refactor`, or `chore`. Mark breaking changes with `!` after the type/scope or a `BREAKING CHANGE:` footer.
- Only create commits when requested, and include only changes belonging to the requested task.

## Finishing a task

Review the diff for unintended changes. Report what changed and which checks ran. If tests could not run because dependencies or services were unavailable, state that explicitly rather than implying they passed.
