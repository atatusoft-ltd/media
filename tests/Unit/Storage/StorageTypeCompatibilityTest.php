<?php

declare(strict_types=1);

use Atatusoft\Media\Storage\LocalStorageProvider;
use Atatusoft\Media\Storage\Providers\Interfaces\StorageProviderInterface;
use Atatusoft\Media\Storage\Providers\LocalStorageProvider as CurrentLocalStorageProvider;
use Atatusoft\Media\Storage\StorageProvider;

test('previous storage type names autoload as the current classes', function () {
    $provider = new ReflectionClass(LocalStorageProvider::class);
    $currentProvider = new ReflectionClass(CurrentLocalStorageProvider::class);
    $contract = new ReflectionClass(StorageProvider::class);
    $currentContract = new ReflectionClass(StorageProviderInterface::class);

    expect($provider->getName())->toBe($currentProvider->getName())
        ->and($provider->isInterface())->toBeFalse()
        ->and($contract->getName())->toBe($currentContract->getName())
        ->and($contract->isInterface())->toBeTrue()
        ->and(is_a(LocalStorageProvider::class, CurrentLocalStorageProvider::class, true))->toBeTrue()
        ->and(is_a(CurrentLocalStorageProvider::class, LocalStorageProvider::class, true))->toBeTrue()
        ->and(is_a(StorageProvider::class, StorageProviderInterface::class, true))->toBeTrue()
        ->and(is_a(StorageProviderInterface::class, StorageProvider::class, true))->toBeTrue();
});
