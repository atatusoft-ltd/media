<?php

declare(strict_types=1);

namespace Tests\Support;

use Atatusoft\Media\Media\Runners\Exceptions\FfprobeRunnerException;
use Atatusoft\Media\Media\Runners\Interfaces\FfprobeRunnerInterface;

final class FakeFfprobeRunner implements FfprobeRunnerInterface
{
    /** @var list<string> */
    public array $paths = [];

    public function __construct(
        private readonly string $output = '',
        private readonly ?FfprobeRunnerException $exception = null,
    ) {
    }

    public function run(string $path): string
    {
        $this->paths[] = $path;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->output;
    }
}
