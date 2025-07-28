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
 * Privacy provider tests.
 *
 * @package   mod_kialo
 * @copyright 2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kialo GmbH <support@kialo-edu.com>
 */

namespace mod_kialo\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy provider tests class.
 *
 * @package   mod_kialo
 * @copyright 2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kialo GmbH <support@kialo-edu.com>
 * @covers \mod_kialo\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata(): void {
        $this->resetAfterTest(true);

        $collection = new collection('mod_kialo');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(1, $itemcollection);

        $kialoserver = array_shift($itemcollection);
        $this->assertEquals('kialo', $kialoserver->get_name());

        $privacyfields = $kialoserver->get_privacy_fields();
        $this->assertCount(9, $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('username', $privacyfields);
        $this->assertArrayHasKey('fullname', $privacyfields);
        $this->assertArrayHasKey('language', $privacyfields);
        $this->assertArrayHasKey('timezone', $privacyfields);
        $this->assertArrayHasKey('picture', $privacyfields);
        $this->assertArrayHasKey('email', $privacyfields);
        $this->assertArrayHasKey('courseid', $privacyfields);
        $this->assertArrayHasKey('role', $privacyfields);
        $this->assertEquals('privacy:metadata:kialo', $kialoserver->get_summary());
    }
}
