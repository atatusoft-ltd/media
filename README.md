# Atatusoft Media

A reusable PHP library for cataloging, probing, storing, and delivering
video-on-demand content across Atatusoft applications.

The project is in early development. Catalog enums are defined; most classes are
currently placeholders. Media probing, storage operations, and playback behavior
are not implemented yet, and the public API is still taking shape.

## Requirements

- PHP 8.4.1 or newer within PHP 8.x for the current development dependencies.
- Composer 2.

Sources are written in ++PHP (`.ppphp`) and compiled to ordinary PHP targeting
PHP 8.4. The compiler and Pest are development dependencies managed by Composer.

## Development setup

From a checkout of this repository:

```sh
composer install
vendor/bin/ppphp build
composer dump-autoload
```

The compiler reads `ppphp.json` and writes generated PHP into `build/`.
Composer maps the `Atatusoft\Media\` namespace to `src/` and `build/`;
build the library before loading its classes through `vendor/autoload.php`.
Rebuild after changing `.ppphp` sources.

The current dual source/output autoload mapping produces compiler warning `P6008`
about `src/`. Builds can still succeed; runtime autoloading uses the generated
`.php` files in `build/` for the current `.ppphp` sources.

For example, after building, run this from a PHP file in the repository root:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Atatusoft\Media\Catalog\Genre;
use Atatusoft\Media\Catalog\TitleType;

echo TitleType::MOVIE->value; // movie
echo Genre::SCIFI->value;     // sci-fi
```

## Project structure

```text
src/
├── Catalog/    # Titles, title types, and genres
├── Media/      # Media assets, probing, and stream metadata
├── Playback/   # Playback sessions and modes
└── Storage/    # Storage providers and objects
```

- `ppphp.json`: compiler configuration, including the PHP target and stub path.
- `stubs/`: optional compiler stubs; currently empty.
- `build/`: generated PHP; ignored by Git.
- `.ppphp-cache/`: compiler cache; ignored by Git.

The compiler creates its output and cache directories as needed. Empty directories
such as `stubs/` may be absent from a fresh checkout.

## Validation

```sh
composer validate --strict
composer check-platform-reqs
vendor/bin/ppphp check
vendor/bin/ppphp build
vendor/bin/pest
```

Pest tests live in `tests/`, with configuration in `phpunit.xml`. The initial
example tests only verify the test harness; add behavior tests as the library is
implemented. Build the sources before running tests.

## Contributing

Edit source files in `src/` and regenerate `build/`; generated PHP should not be
edited or committed. Keep changes focused and document implemented behavior with
tests and examples. See [AGENTS.md](AGENTS.md) for repository conventions.

Keep `composer.lock` tracked so contributors use the same development toolchain.
Downstream applications resolve their own dependencies.

Release artifacts will need to include compiled PHP in `build/`, since consumers
do not install this library's development compiler. Release packaging is not yet
configured.

## License

Licensed under the [MIT License](LICENSE).
