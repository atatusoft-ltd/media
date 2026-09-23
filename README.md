# Atatusoft Media

A reusable PHP library for cataloging, probing, storing, and delivering
video-on-demand content across Atatusoft applications.

The project is in early development. Catalog enums and ffprobe-based media
probing are implemented. Storage operations and playback behavior are not
implemented yet, and the rest of the public API is still taking shape.

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

## Media probing

`MediaProbe` reads an existing media file through an `FfprobeRunnerInterface`
and returns a `MediaProbeResult`. The bundled `ProcessFfprobeRunner` executes
ffprobe with an argument array and returns its JSON stdout. `MediaProbe`
decodes that JSON and normalizes it into media-domain values such as format
names, duration, and video, audio, and subtitle streams. ffprobe field names
stay inside the probe.

Probing requires the `ffprobe` executable, or another binary passed to
`ProcessFfprobeRunner`. The default timeout is 30 seconds. Probe failures are
reported as `MediaProbeException`.

```php
use Atatusoft\Media\Media\MediaProbe;
use Atatusoft\Media\Media\Runners\ProcessFfprobeRunner;

$probe = new MediaProbe(new ProcessFfprobeRunner());
$result = $probe->probe('/path/to/movie.mp4');

$result->formatNames; // for example ['mov', 'mp4', 'm4a', '3gp', '3g2', 'mj2']
$result->duration;
$result->videoStreams[0]->width ?? null;
$result->audioStreams[0]->language ?? null;
$result->subtitleStreams[0]->forced ?? null;
```

Streams whose ffprobe `codec_type` is not video, audio, or subtitle are ignored.
Missing optional metadata becomes null, and missing subtitle disposition flags
are false.

## Project structure

```text
src/
├── Catalog/    # Titles, title types, and genres
├── Media/      # Media assets, probing, and stream metadata
├── Playback/   # Playback sessions and modes
└── Storage/    # Storage providers and objects
```

- `ppphp.json`: compiler configuration, including the PHP target and stub path.
- `stubs/`: compiler stubs. `Process.stub.php` corrects Symfony Process
  constructor analysis for this ++PHP version.
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

Pest tests live in `tests/`, with configuration in `phpunit.xml`. Build the
sources before running tests. Media probing tests use a fake ffprobe runner, and
`ProcessFfprobeRunner` tests use a local stub executable. Neither requires
ffprobe to be installed.

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
