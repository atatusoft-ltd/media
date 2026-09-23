<?php

// Symfony declares Process::__construct()'s command as unqualified string[].
// This ++PHP version resolves that PHPDoc name inside Symfony\Component\Process.
// The stub keeps the native array parameter so argument lists typecheck.

namespace Symfony\Component\Process;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;

class Process
{
    /**
     * @throws LogicException
     */
    public function __construct(array $command, ?string $cwd = null, ?array $env = null, mixed $input = null, ?float $timeout = 60)
    {
    }

    /**
     * @throws ProcessStartFailedException
     * @throws RuntimeException
     * @throws ProcessTimedOutException
     * @throws ProcessSignaledException
     */
    public function run(?callable $callback = null, array $env = []): int
    {
    }

    public function isSuccessful(): bool
    {
    }

    public function getExitCode(): ?int
    {
    }

    /**
     * @throws LogicException
     */
    public function getOutput(): string
    {
    }

    /**
     * @throws LogicException
     */
    public function getErrorOutput(): string
    {
    }
}
