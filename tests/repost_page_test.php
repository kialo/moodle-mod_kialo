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
 * Tests the cross-site repost page used to work around the SameSite=Lax session cookie (MDL-83526).
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use mod_kialo\output\repost_page;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Tests the cross-site repost page.
 *
 * @covers \mod_kialo\output\repost_page
 */
final class repost_page_test extends \advanced_testcase {
    /**
     * The exported template data should contain the repost URL and the POST params as key/value pairs.
     *
     * @return void
     * @covers \mod_kialo\output\repost_page::export_for_template
     */
    public function test_export_for_template(): void {
        $this->resetAfterTest();
        global $PAGE;
        $renderer = $PAGE->get_renderer('mod_kialo');

        $url = 'https://moodle.example.com/mod/kialo/lti_auth.php';
        $page = new repost_page($url, [
            'nonce' => 'abc',
            'response_type' => 'id_token',
        ]);
        $data = $page->export_for_template($renderer);

        $this->assertEquals($url, $data->url);
        $this->assertEquals([
            ['key' => 'nonce', 'value' => 'abc'],
            ['key' => 'response_type', 'value' => 'id_token'],
        ], $data->params);
        $this->assertNotEmpty($data->continuetext);
    }

    /**
     * A GET launch must be reposted as a GET (the LTI library reads parameters from the query string
     * for GET requests). The form must carry the original parameters as fields plus the repost marker,
     * and auto-submit.
     *
     * @return void
     * @covers \mod_kialo\output\repost_page
     */
    public function test_renders_autosubmitting_get_form(): void {
        $this->resetAfterTest();
        global $PAGE;
        $renderer = $PAGE->get_renderer('mod_kialo');

        $html = $renderer->render(new repost_page(
            'https://moodle.example.com/mod/kialo/lti_auth.php',
            ['nonce' => 'abc', 'lti_message_hint' => 'JWT123']
        ));

        // GET form posting to the bare endpoint, so the parameters end up in the query string.
        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="https://moodle.example.com/mod/kialo/lti_auth.php"', $html);

        // The original parameters are resubmitted as hidden fields (incl. the crucial lti_message_hint).
        $this->assertStringContainsString('name="nonce"', $html);
        $this->assertStringContainsString('value="abc"', $html);
        $this->assertStringContainsString('name="lti_message_hint"', $html);

        // The repost marker prevents an infinite repost loop on the second request.
        $this->assertStringContainsString('name="repost"', $html);

        // It auto-submits via JavaScript, deferred to the load event so the spinner paints first.
        $this->assertStringContainsString('kialo-repost-form', $html);
        $this->assertStringContainsString('.submit()', $html);
        $this->assertStringContainsString('addEventListener("load"', $html);
    }
}
