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

/**
 * Moodle cache test.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use Cache\Adapter\Common\CacheItem;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the moodle cache implementation.
 *
 * @covers \mod_kialo\static_nonce_generator
 */
class moodle_cache_test extends \basic_testcase {
    /**
     * @var moodle_cache $cache
     */
    private $cache;

    /**
     * Creates a new cache instance for each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->cache = moodle_cache::nonce_cache();
    }

    /**
     * Tests that setting and getting of individual cache items works.
     *
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     * @covers \mod_kialo\moodle_cache::storeItemInCache
     * @covers \mod_kialo\moodle_cache::fetchObjectFromCache
     */
    public function test_cache_gets_and_sets() {
        $this->assertFalse($this->cache->hasItem('key'));
        $item = new CacheItem('key', true, 'value');
        $this->assertTrue($this->cache->save($item));

        $this->assertTrue($this->cache->hasItem('key'));
        $cached = $this->cache->getItem('key');
        $this->assertEquals('value', $cached->get());
    }

    /**
     * Tests clearing of the cache.
     *
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function test_clear_all_objects() {
        $this->cache->save(new CacheItem('item1', true, 1));
        $this->cache->save(new CacheItem('item2', true, 2));
        $this->assertTrue($this->cache->hasItem('item1'));
        $this->assertTrue($this->cache->hasItem('item2'));

        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->hasItem('item1'));
        $this->assertFalse($this->cache->hasItem('item2'));
    }
}
