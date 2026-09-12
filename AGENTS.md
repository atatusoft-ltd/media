# Repository guidance

## Project context

`atatusoft/media` is a reusable media library for Atatusoft applications. It is
an early scaffold: catalog enums exist, while most classes have no behavior yet.
Keep documentation and examples aligned with implemented capabilities.

## Sources and architecture

- Maintain library sources in `src/`, primarily as `.ppphp` files.
- Use the `Atatusoft\Media\` namespace and match namespaces to directories.
- Keep catalog concepts in `Catalog/`, media metadata and probing in `Media/`,
  playback concepts in `Playback/`, and persistence adapters in `Storage/`.
- Keep the library independent of application frameworks and application-specific
  configuration. Pass external services and configuration explicitly.
- Follow nearby source conventions: four-space indentation, braces on their own
  lines, PascalCase types, and uppercase enum case names.
- Give new public APIs explicit parameter and return types. Add meaningful tests
  for new behavior and bug fixes.

## Compiler workflow

`ppphp.json` is the compiler configuration. It reads `src/`, uses `stubs/` for
optional stubs, targets PHP 8.4, and writes generated PHP to `build/`.

- Edit source files and rebuild; never hand-edit generated output.
- Do not commit `vendor/`, `build/`, `.ppphp-cache/`, or `.ppphp-operation.lock`.
- Do not execute `.ppphp` sources directly with PHP.
- Build before running examples or tests that use Composer autoloading.
- Keep generated code compatible with the configured PHP 8.4 target, even when
  the local PHP interpreter is newer.
- Preserve the `src/` and `build/` Composer namespace mappings unless deliberately
  changing the compilation and autoloading workflow together.

## Setup and verification

Install dependencies with `composer install`. Keep `composer.lock` tracked;
update dependencies only when the task calls for a toolchain change.

Run checks relevant to the change:

```sh
composer validate --strict
composer check-platform-reqs
vendor/bin/ppphp check
vendor/bin/ppphp build
vendor/bin/pest
```

For documentation-only changes, verify referenced paths and commands; no new
tests are required. For source changes, check and build the project and run
relevant tests. Pest tests live in `tests/`, use the `Tests\` development
namespace, and load `vendor/autoload.php` through `phpunit.xml`. Run
`composer dump-autoload` after changing namespace mappings. The initial example
tests only verify the harness; they do not cover library behavior. Do not report
tests as passing when no tests ran.

## Scope and delivery

- Preserve unrelated work and avoid implementing placeholder classes unless the
  task requests that behavior.
- Update the README when setup, supported behavior, or validation commands change.
- Keep the MIT license declaration in Composer consistent with `LICENSE`.
- Release packages must include generated PHP in `build/`; Git ignores that
  directory, and consumers do not receive development dependencies. Packaging
  automation still needs to be designed before publishing.
- Summarize changes, checks actually run, and any remaining limitations.
