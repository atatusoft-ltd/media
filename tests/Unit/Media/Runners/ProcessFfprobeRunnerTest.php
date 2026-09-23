<?php

declare(strict_types=1);

use Atatusoft\Media\Media\Runners\Exceptions\FfprobeRunnerException;
use Atatusoft\Media\Media\Runners\ProcessFfprobeRunner;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

beforeEach(function () {
    $stub = ffprobeStub();

    if (!is_file($stub)) {
        throw new RuntimeException('The ffprobe stub is missing.');
    }

    chmod($stub, 0755);
});

test('uses ffprobe and a 30 second timeout by default', function () {
    $runner = new ProcessFfprobeRunner();
    $reflection = new ReflectionClass($runner);

    expect($reflection->getProperty('binary')->getValue($runner))->toBe('ffprobe')
        ->and($reflection->getProperty('timeout')->getValue($runner))->toBe(30.0);
});

test('rejects an empty ffprobe binary and a non positive timeout', function (string $binary, float $timeout) {
    expect(fn () => new ProcessFfprobeRunner($binary, $timeout))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['', 30.0],
    ['   ', 30.0],
    ['ffprobe', 0.0],
    ['ffprobe', -5.0],
]);

test('invokes ffprobe with an argument array', function () {
    $path = sys_get_temp_dir() . '/ffprobe-stub-args movie;rm -rf "quoted".mp4';
    $output = (new ProcessFfprobeRunner(ffprobeStub(), 5.0))->run($path);
    $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

    expect($decoded['argv'])->toBe([
        ffprobeStub(),
        '-v',
        'error',
        '-print_format',
        'json',
        '-show_format',
        '-show_streams',
        $path,
    ]);
});

test('returns ffprobe stdout on success', function () {
    expect((new ProcessFfprobeRunner(ffprobeStub(), 5.0))->run('movie.mp4'))
        ->toBe("{\"ok\":true}\n");
});

test('fails when ffprobe returns empty or whitespace-only output', function (string $path) {
    expect(fn () => (new ProcessFfprobeRunner(ffprobeStub(), 5.0))->run($path))
        ->toThrow(FfprobeRunnerException::class, 'empty output');
})->with([
    'ffprobe-stub-empty.mp4',
    'ffprobe-stub-blank.mp4',
]);

test('reports a non-zero ffprobe exit status with stderr and the media path', function () {
    $path = '/library/ffprobe-stub-fail.mp4';

    try {
        (new ProcessFfprobeRunner(ffprobeStub(), 5.0))->run($path);
        expect(false)->toBeTrue();
    } catch (FfprobeRunnerException $exception) {
        expect($exception->getMessage())->toContain('status 7')
            ->and($exception->getMessage())->toContain($path)
            ->and($exception->getMessage())->toContain('ffprobe stub failed');
    }
});

test('reports ffprobe timeouts', function () {
    $path = '/library/ffprobe-stub-sleep.mp4';

    try {
        (new ProcessFfprobeRunner(ffprobeStub(), 0.4))->run($path);
        expect(false)->toBeTrue();
    } catch (FfprobeRunnerException $exception) {
        expect($exception->getPrevious())->toBeInstanceOf(ProcessTimedOutException::class)
            ->and($exception->getMessage())->toContain($path)
            ->and($exception->getMessage())->toContain('timed out');
    }
});

test('reports ffprobe startup failures', function () {
    $binary = sys_get_temp_dir() . '/atatusoft-missing-ffprobe-' . bin2hex(random_bytes(8));
    $path = '/library/movie.mp4';

    try {
        (new ProcessFfprobeRunner($binary, 5.0))->run($path);
        expect(false)->toBeTrue();
    } catch (FfprobeRunnerException $exception) {
        expect($exception->getMessage())->toContain('Unable to start')
            ->and($exception->getMessage())->toContain($path)
            ->and($exception->getMessage())->toContain($binary);
    }
});

function ffprobeStub(): string
{
    return dirname(__DIR__, 3) . '/Fixtures/ffprobe-stub.php';
}
