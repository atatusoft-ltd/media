<?php

declare(strict_types=1);

namespace Tests\Support;

use Atatusoft\Media\Media\MediaProbe;
use Atatusoft\Media\Media\MediaProbeResult;

final class ProbeHarness
{
    public static function temporaryFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'atatusoft-media-');

        if ($path === false) {
            throw new \RuntimeException('Unable to create a temporary media file.');
        }

        return $path;
    }

    public static function probe(string $json): MediaProbeResult
    {
        $path = self::temporaryFile();

        try {
            return (new MediaProbe(new FakeFfprobeRunner($json)))->probe($path);
        } finally {
            @unlink($path);
        }
    }
}
