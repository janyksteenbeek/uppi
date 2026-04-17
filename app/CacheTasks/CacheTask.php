<?php

namespace App\CacheTasks;

use App\Jobs\RefreshCacheTaskJob;
use RuntimeException;
use Illuminate\Support\Facades\Cache;

abstract class CacheTask
{
    protected ?string $userId = null;

    protected ?string $id = null;

    abstract public function key(): string;

    abstract public function execute(): mixed;

    abstract public static function getTtl(): int;

    /**
     * Dispatch refresh jobs for this task
     */
    public static function refreshForUser(string $userId): void
    {
        dispatch(new RefreshCacheTaskJob(static::class, $userId))->onQueue('cache');
    }

    public function forUser(string $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function forId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    protected function getCacheKey(): string
    {
        if ($this->userId === null) {
            throw new RuntimeException('Cache task must be scoped to a user');
        }

        return "user_{$this->userId}_{$this->key()}";
    }

    public function get(): mixed
    {
        $data = Cache::remember(
            $this->getCacheKey(),
            static::getTtl() * 60,
            fn () => $this->execute()
        );

        if ($this->id !== null && is_array($data)) {
            return $data[$this->id] ?? null;
        }

        return $data;
    }

    public function refresh(): void
    {
        Cache::put(
            $this->getCacheKey(),
            $this->execute(),
            static::getTtl() * 60
        );
    }
}
