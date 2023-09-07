<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_kialo;

use cache;
use Cache\Adapter\Common\AbstractCachePool;
use Cache\Adapter\Common\PhpCacheItem;

/**
 * Uses Moodle's Cache API to implement a PSR-6 compatible cache.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://docs.moodle.org/dev/Cache_API
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
 */
class moodle_cache extends AbstractCachePool {
    /**
     * @var cache|\cache_application|\cache_session|\cache_store Moodle cache instance.
     */
    private $moodlecache;

    /**
     * Session-based cache for nonces.
     * @return self
     */
    public static function nonce_cache() {
        return new self("nonces");
    }

    /**
     * Generic cache using the Moodle cache API.
     * @param string $name Name of a cache defined in `db/caches.php`.
     */
    protected function __construct(string $name) {
        $this->moodlecache = cache::make('mod_kialo', $name);
    }

    /**
     * Stores an item in the cache.
     * @param PhpCacheItem $item
     * @param int $ttl Ignored.
     * @return bool
     */
    protected function storeItemInCache(PhpCacheItem $item, $ttl): bool {
        // Moodle's Cache API does not support TTL, so we ignore it.
        return $this->moodlecache->set($item->getKey(), $item->get());
    }

    /**
     * Fetches an item from the cache.
     * @param string $key
     * @return array tuple of [cachehit: bool, value: mixed, metadata: array (empty), expiration: ?int (null)]
     * @throws \coding_exception
     */
    protected function fetchObjectFromCache($key): array {
        $value = $this->moodlecache->get($key);
        if (!$value) {
            return [false, null, [], null];
        }

        return [true, $value, [], null];
    }

    /**
     * Removes all items from the cache.
     * @return bool
     */
    protected function clearAllObjectsFromCache(): bool {
        return $this->moodlecache->purge();
    }

    /**
     * Removes an item from the cache.
     * @param string $key
     * @return bool
     */
    protected function clearOneObjectFromCache($key): bool {
        return $this->moodlecache->delete($key);
    }

    /**
     * Returns a list value from the cache.
     * @param string $name
     * @return array
     * @throws \coding_exception
     */
    protected function getList($name): array {
        return $this->moodlecache->get($name) ?? [];
    }

    /**
     * Remove a list from the cache.
     * @param string $name
     * @return bool
     */
    protected function removeList($name): bool {
        return $this->moodlecache->delete($name);
    }

    /**
     * Append a value to a list in the cache.
     * @param string $name
     * @param string $key
     * @return void
     * @throws \coding_exception
     */
    protected function appendListItem($name, $key) {
        $existing = $this->moodlecache->get($name) ?? [];
        assert(is_array($existing));
        $existing[] = $key;
        $this->moodlecache->set($name, $existing);
    }

    /**
     * Remove a value from a list in the cache.
     * @param string $name
     * @param string $key
     * @return void
     * @throws \coding_exception
     */
    protected function removeListItem($name, $key) {
        $existing = $this->moodlecache->get($name) ?? [];
        assert(is_array($existing));
        $existing = array_filter($existing, fn ($item) => $item !== $key);
        $this->moodlecache->set($name, $existing);
    }
}
