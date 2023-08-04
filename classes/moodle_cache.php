<?php

namespace mod_kialo;

use cache;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;

/**
 * Uses Moodle's Cache API to implement a PSR-6 compatible cache.
 * @see https://docs.moodle.org/dev/Cache_API
 */
class moodle_cache extends AbstractCachePool {
    private cache $moodlecache;

    /**
     * Session-based cache for nonces.
     * @return self
     */
    public static function nonce_cache() {
        return new self("nonces");
    }

    /**
     * @param string $name Name of a cache defined in `db/caches.php`.
     */
    protected function __construct(string $name) {
        $this->moodlecache = cache::make('mod_kialo', $name);
    }

    protected function storeItemInCache(PhpCacheItem $item, $ttl): bool {
        // Moodle's Cache API does not support TTL, so we ignore it
        return $this->moodlecache->set($item->getKey(), $item->get());
    }

    protected function fetchObjectFromCache($key): array {
        $value = $this->moodlecache->get($key);
        if (!$value) {
            return [false, null, [], null];
        }

        return [true, $value, [], null];
    }

    protected function clearAllObjectsFromCache(): bool {
        return $this->moodlecache->purge();
    }

    protected function clearOneObjectFromCache($key): bool {
        return $this->moodlecache->delete($key);
    }

    protected function getList($name): array {
        return $this->moodlecache->get($name) ?? [];
    }

    protected function removeList($name): bool {
        return $this->moodlecache->delete($name);
    }

    protected function appendListItem($name, $key) {
        $existing = $this->moodlecache->get($name) ?? [];
        assert(is_array($existing));
        $existing[] = $key;
        $this->moodlecache->set($name, $existing);
    }

    protected function removeListItem($name, $key) {
        $existing = $this->moodlecache->get($name) ?? [];
        assert(is_array($existing));
        $existing = array_filter($existing, fn($item) => $item !== $key);
        $this->moodlecache->set($name, $existing);
    }
}
