<?php

declare(strict_types=1);

use Atatusoft\Media\Media\Exceptions\MediaProbeException;
use Atatusoft\Media\Media\MediaProbe;
use Atatusoft\Media\Media\Runners\Exceptions\FfprobeRunnerException;
use Tests\Support\FakeFfprobeRunner;
use Tests\Support\ProbeHarness;

test('probes a representative 1080p h264 and aac mp4 response', function () {
    $path = ProbeHarness::temporaryFile();
    $runner = new FakeFfprobeRunner(representativeMp4Response());

    try {
        $result = (new MediaProbe($runner))->probe($path);
    } finally {
        @unlink($path);
    }

    expect($runner->paths)->toBe([$path])
        ->and($result->path)->toBe($path)
        ->and($result->formatNames)->toBe(['mov', 'mp4', 'm4a', '3gp', '3g2', 'mj2'])
        ->and($result->formatLongName)->toBe('QuickTime / MOV')
        ->and($result->duration)->toBe(120.5)
        ->and($result->size)->toBe(10485760)
        ->and($result->bitRate)->toBe(696254)
        ->and($result->videoStreams)->toHaveCount(1)
        ->and($result->audioStreams)->toHaveCount(1)
        ->and($result->subtitleStreams)->toHaveCount(1);

    $video = $result->videoStreams[0];
    expect($video->index)->toBe(0)
        ->and($video->codec)->toBe('h264')
        ->and($video->codecLongName)->toBe('H.264 / AVC / MPEG-4 AVC / MPEG-4 part 10')
        ->and($video->profile)->toBe('High')
        ->and($video->width)->toBe(1920)
        ->and($video->height)->toBe(1080)
        ->and($video->pixelFormat)->toBe('yuv420p')
        ->and($video->frameRate)->toEqualWithDelta(24000 / 1001, 0.0000001)
        ->and($video->bitRate)->toBe(4500000);

    $audio = $result->audioStreams[0];
    expect($audio->index)->toBe(1)
        ->and($audio->codec)->toBe('aac')
        ->and($audio->codecLongName)->toBe('AAC (Advanced Audio Coding)')
        ->and($audio->profile)->toBe('LC')
        ->and($audio->sampleRate)->toBe(48000)
        ->and($audio->channels)->toBe(2)
        ->and($audio->channelLayout)->toBe('stereo')
        ->and($audio->bitRate)->toBe(192000)
        ->and($audio->language)->toBe('eng');

    $subtitle = $result->subtitleStreams[0];
    expect($subtitle->index)->toBe(2)
        ->and($subtitle->codec)->toBe('mov_text')
        ->and($subtitle->codecLongName)->toBe('MOV text')
        ->and($subtitle->language)->toBe('spa')
        ->and($subtitle->title)->toBe('Spanish')
        ->and($subtitle->default)->toBeTrue()
        ->and($subtitle->forced)->toBeTrue();
});

test('splits ffprobe format names into trimmed non-empty values', function () {
    $result = ProbeHarness::probe(responseWith([
        'format_name' => ' mov, mp4,, m4a , ,3gp ',
    ]));

    expect($result->formatNames)->toBe(['mov', 'mp4', 'm4a', '3gp']);
});

test('normalizes ffprobe string numeric fields', function () {
    $result = ProbeHarness::probe(responseWith([
        'duration' => '120.500000',
        'size' => '10485760',
        'bit_rate' => '696254',
    ], [
        stream('video', [
            'width' => '1920',
            'height' => '1080',
            'bit_rate' => '4500000',
        ]),
        stream('audio', [
            'sample_rate' => '48000',
            'channels' => '6',
            'bit_rate' => '192000.0',
        ]),
    ]));

    expect($result->duration)->toBe(120.5)
        ->and($result->size)->toBe(10485760)
        ->and($result->bitRate)->toBe(696254)
        ->and($result->videoStreams[0]->width)->toBe(1920)
        ->and($result->videoStreams[0]->height)->toBe(1080)
        ->and($result->videoStreams[0]->bitRate)->toBe(4500000)
        ->and($result->audioStreams[0]->sampleRate)->toBe(48000)
        ->and($result->audioStreams[0]->channels)->toBe(6)
        ->and($result->audioStreams[0]->bitRate)->toBe(192000);
});

test('normalizes rational frame rates and treats undefined rates as null', function () {
    $result = ProbeHarness::probe(responseWith(streams: [
        stream('video', [
            'avg_frame_rate' => '24000/1001',
            'r_frame_rate' => '24/1',
        ]),
        stream('video', [
            'avg_frame_rate' => '0/0',
            'r_frame_rate' => '0/0',
        ]),
        stream('video', [
            'avg_frame_rate' => '0/0',
            'r_frame_rate' => '30000/1001',
        ]),
        stream('video', [
            'avg_frame_rate' => '25/1',
            'r_frame_rate' => '24/1',
        ]),
        stream('video', [
            'avg_frame_rate' => 'N/A',
            'r_frame_rate' => '',
        ]),
    ]));

    expect($result->videoStreams[0]->frameRate)->toEqualWithDelta(24000 / 1001, 0.0000001)
        ->and($result->videoStreams[1]->frameRate)->toBeNull()
        ->and($result->videoStreams[2]->frameRate)->toEqualWithDelta(30000 / 1001, 0.0000001)
        ->and($result->videoStreams[3]->frameRate)->toBe(25.0)
        ->and($result->videoStreams[4]->frameRate)->toBeNull();
});

test('reads audio language from stream tags', function () {
    $result = ProbeHarness::probe(responseWith(streams: [
        stream('audio', [
            'tags' => ['language' => 'eng'],
        ]),
        stream('audio', [
            'tags' => ['Language' => 'fra'],
        ]),
        stream('audio', [
            'tags' => ['language' => ' N/A '],
        ]),
        stream('audio'),
    ]));

    expect($result->audioStreams[0]->language)->toBe('eng')
        ->and($result->audioStreams[1]->language)->toBe('fra')
        ->and($result->audioStreams[2]->language)->toBeNull()
        ->and($result->audioStreams[3]->language)->toBeNull();
});

test('reads subtitle language and title from stream tags', function () {
    $result = ProbeHarness::probe(responseWith(streams: [
        stream('subtitle', [
            'tags' => [
                'language' => 'spa',
                'title' => 'Spanish',
            ],
        ]),
        stream('subtitle', [
            'tags' => [
                'Title' => ' Commentary ',
            ],
        ]),
    ]));

    expect($result->subtitleStreams[0]->language)->toBe('spa')
        ->and($result->subtitleStreams[0]->title)->toBe('Spanish')
        ->and($result->subtitleStreams[1]->language)->toBeNull()
        ->and($result->subtitleStreams[1]->title)->toBe('Commentary');
});

test('reads subtitle default and forced dispositions', function () {
    $result = ProbeHarness::probe(responseWith(streams: [
        stream('subtitle', [
            'disposition' => ['default' => 1, 'forced' => 0],
        ]),
        stream('subtitle', [
            'disposition' => ['default' => 0, 'forced' => 1],
        ]),
        stream('subtitle', [
            'disposition' => ['default' => '1', 'forced' => '0'],
        ]),
        stream('subtitle'),
    ]));

    expect($result->subtitleStreams[0]->default)->toBeTrue()
        ->and($result->subtitleStreams[0]->forced)->toBeFalse()
        ->and($result->subtitleStreams[1]->default)->toBeFalse()
        ->and($result->subtitleStreams[1]->forced)->toBeTrue()
        ->and($result->subtitleStreams[2]->default)->toBeTrue()
        ->and($result->subtitleStreams[2]->forced)->toBeFalse()
        ->and($result->subtitleStreams[3]->default)->toBeFalse()
        ->and($result->subtitleStreams[3]->forced)->toBeFalse();
});

test('ignores unsupported stream types', function () {
    $result = ProbeHarness::probe(responseWith(streams: [
        stream('video'),
        stream('data', ['codec_name' => 'bin_data']),
        'not-a-stream',
        stream('attachment', ['codec_name' => 'ttf']),
        ['codec_name' => 'unknown'],
        stream('audio'),
        stream('subtitle'),
    ]));

    expect($result->videoStreams)->toHaveCount(1)
        ->and($result->audioStreams)->toHaveCount(1)
        ->and($result->subtitleStreams)->toHaveCount(1)
        ->and($result->videoStreams[0]->index)->toBe(0)
        ->and($result->audioStreams[0]->index)->toBe(5)
        ->and($result->subtitleStreams[0]->index)->toBe(6);
});

test('uses null and false defaults when optional ffprobe metadata is missing', function () {
    $result = ProbeHarness::probe(responseWith([
        'format_name' => 'N/A',
        'format_long_name' => '',
        'duration' => 'N/A',
        'size' => '',
        'bit_rate' => 'not-a-number',
    ], [
        [
            'codec_type' => 'video',
            'codec_name' => 'N/A',
            'codec_long_name' => ' ',
            'profile' => '',
            'width' => 'N/A',
            'height' => '-1',
            'pix_fmt' => 'N/A',
            'avg_frame_rate' => '0/0',
            'bit_rate' => 'N/A',
        ],
        [
            'index' => '3',
            'codec_type' => 'audio',
            'sample_rate' => '',
            'channels' => 'nope',
            'channel_layout' => 'N/A',
        ],
        [
            'codec_type' => 'subtitle',
            'tags' => [
                'language' => '',
                'title' => 'N/A',
            ],
        ],
    ]));

    expect($result->formatNames)->toBe([])
        ->and($result->formatLongName)->toBeNull()
        ->and($result->duration)->toBeNull()
        ->and($result->size)->toBeNull()
        ->and($result->bitRate)->toBeNull();

    $video = $result->videoStreams[0];
    expect($video->index)->toBe(0)
        ->and($video->codec)->toBeNull()
        ->and($video->codecLongName)->toBeNull()
        ->and($video->profile)->toBeNull()
        ->and($video->width)->toBeNull()
        ->and($video->height)->toBeNull()
        ->and($video->pixelFormat)->toBeNull()
        ->and($video->frameRate)->toBeNull()
        ->and($video->bitRate)->toBeNull();

    $audio = $result->audioStreams[0];
    expect($audio->index)->toBe(3)
        ->and($audio->sampleRate)->toBeNull()
        ->and($audio->channels)->toBeNull()
        ->and($audio->channelLayout)->toBeNull()
        ->and($audio->language)->toBeNull();

    expect($result->subtitleStreams[0]->index)->toBe(2)
        ->and($result->subtitleStreams[0]->language)->toBeNull()
        ->and($result->subtitleStreams[0]->title)->toBeNull()
        ->and($result->subtitleStreams[0]->default)->toBeFalse()
        ->and($result->subtitleStreams[0]->forced)->toBeFalse();
});

test('accepts a response that omits the streams list', function () {
    $result = ProbeHarness::probe(json_encode([
        'format' => [
            'format_name' => 'mp4',
            'duration' => '1.5',
        ],
    ], JSON_THROW_ON_ERROR));

    expect($result->formatNames)->toBe(['mp4'])
        ->and($result->duration)->toBe(1.5)
        ->and($result->videoStreams)->toBe([])
        ->and($result->audioStreams)->toBe([])
        ->and($result->subtitleStreams)->toBe([]);
});

test('rejects malformed json as a media probe exception', function () {
    $path = ProbeHarness::temporaryFile();

    try {
        (new MediaProbe(new FakeFfprobeRunner('{"streams":')))->probe($path);
        expect(false)->toBeTrue();
    } catch (MediaProbeException $exception) {
        expect($exception->getPrevious())->toBeInstanceOf(JsonException::class)
            ->and($exception->getMessage())->toContain($path);
    } finally {
        @unlink($path);
    }
});

test('rejects unusable ffprobe responses', function (string $json) {
    expect(fn () => ProbeHarness::probe($json))->toThrow(MediaProbeException::class);
})->with([
    'null',
    '[]',
    '{}',
    '{"format":"mp4"}',
    '{"format":null}',
    '{"format":{"format_name":"mp4"},"streams":"nope"}',
    '"mp4"',
]);

test('throws when the media path is not an existing file', function () {
    $runner = new FakeFfprobeRunner('{}');
    $missing = sys_get_temp_dir() . '/atatusoft-media-missing-' . bin2hex(random_bytes(8)) . '.mp4';

    expect(fn () => (new MediaProbe($runner))->probe($missing))
        ->toThrow(MediaProbeException::class, $missing);

    expect($runner->paths)->toBe([]);
});

test('throws when the media path is a directory', function () {
    expect(fn () => (new MediaProbe(new FakeFfprobeRunner('{}')))->probe(sys_get_temp_dir()))
        ->toThrow(MediaProbeException::class);
});

test('throws when the media file is not readable', function () {
    $path = ProbeHarness::temporaryFile();
    chmod($path, 0000);

    try {
        expect(fn () => (new MediaProbe(new FakeFfprobeRunner('{}')))->probe($path))
            ->toThrow(MediaProbeException::class, 'not readable');
    } finally {
        chmod($path, 0644);
        @unlink($path);
    }
})->skip(posix_geteuid() === 0, 'the root user can read mode 0000 files');

test('wraps runner failures as media probe exceptions and preserves the previous exception', function () {
    $path = ProbeHarness::temporaryFile();
    $runnerException = new FfprobeRunnerException('ffprobe exited with status 7 while probing "' . $path . '".');

    try {
        (new MediaProbe(new FakeFfprobeRunner(exception: $runnerException)))->probe($path);
        expect(false)->toBeTrue();
    } catch (MediaProbeException $exception) {
        expect($exception->getPrevious())->toBe($runnerException)
            ->and($exception->getMessage())->toContain($path)
            ->and($exception->getMessage())->toContain('status 7');
    } finally {
        @unlink($path);
    }
});

function representativeMp4Response(): string
{
    return responseWith(
        [
            'filename' => '/path/to/movie.mp4',
            'nb_streams' => 4,
            'format_name' => 'mov,mp4,m4a,3gp,3g2,mj2',
            'format_long_name' => 'QuickTime / MOV',
            'duration' => '120.500000',
            'size' => '10485760',
            'bit_rate' => '696254',
        ],
        [
            stream('video', [
                'index' => '0',
                'codec_name' => 'h264',
                'codec_long_name' => 'H.264 / AVC / MPEG-4 AVC / MPEG-4 part 10',
                'profile' => 'High',
                'width' => '1920',
                'height' => '1080',
                'pix_fmt' => 'yuv420p',
                'avg_frame_rate' => '24000/1001',
                'r_frame_rate' => '24000/1001',
                'bit_rate' => '4500000',
                'codec_tag_string' => 'avc1',
            ]),
            stream('audio', [
                'index' => '1',
                'codec_name' => 'aac',
                'codec_long_name' => 'AAC (Advanced Audio Coding)',
                'profile' => 'LC',
                'sample_rate' => '48000',
                'channels' => '2',
                'channel_layout' => 'stereo',
                'bit_rate' => '192000',
                'tags' => ['language' => 'eng'],
            ]),
            stream('subtitle', [
                'index' => '2',
                'codec_name' => 'mov_text',
                'codec_long_name' => 'MOV text',
                'tags' => [
                    'language' => 'spa',
                    'title' => 'Spanish',
                ],
                'disposition' => [
                    'default' => 1,
                    'forced' => 1,
                ],
            ]),
            stream('data', [
                'codec_name' => 'bin_data',
            ]),
        ],
    );
}

/**
 * @param array<string, mixed> $format
 * @param list<mixed> $streams
 */
function responseWith(array $format = [], array $streams = []): string
{
    return json_encode([
        'streams' => $streams,
        'format' => $format === [] ? ['format_name' => 'mp4'] : $format,
    ], JSON_THROW_ON_ERROR);
}

/**
 * @param array<string, mixed> $fields
 * @return array<string, mixed>
 */
function stream(string $codecType, array $fields = []): array
{
    return ['codec_type' => $codecType] + $fields;
}
