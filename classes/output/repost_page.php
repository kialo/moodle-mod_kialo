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

namespace mod_kialo\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renders a page that immediately re-submits the current LTI authentication request to Moodle itself.
 *
 * Since MDL-83526 (Moodle 4.5.12 / 5.0.8 / 5.1.5 / 5.2.1) the MoodleSession cookie defaults to
 * SameSite=Lax. When a Kialo discussion is displayed embedded, Kialo redirects the browser back to
 * the plugin's LTI authentication endpoint (lti_auth.php) from within its own (cross-site) iframe.
 * Browsers withhold the SameSite=Lax session cookie on that cross-site request, so Moodle considers
 * the user logged out and authentication fails.
 *
 * Reposting the exact same request from a page served by Moodle itself turns it into a same-site
 * request, so the session cookie is sent and authentication can proceed. This mirrors the approach
 * used by Moodle core's own mod_lti (see mod/lti/auth.php and MDL-71887).
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repost_page implements renderable, templatable {
    /** @var string The (Moodle) URL the request should be reposted to. */
    private $url;

    /** @var array List of ["key" => ..., "value" => ...] entries to resubmit as hidden form fields. */
    private $params;

    /**
     * Creates a new repost page data container.
     *
     * Kialo sends the LTI authentication request as a GET, so the repost is a GET form whose fields land
     * back in the query string, which is where the LTI library reads the parameters from.
     *
     * @param string $url The URL to repost to. It must NOT contain a query string, since the parameters
     *                     are submitted as the GET form's fields.
     * @param array $params The original request parameters to resubmit as hidden fields.
     */
    public function __construct(string $url, array $params) {
        $this->url = $url;
        $this->params = array_map(function ($key) use ($params) {
            return ["key" => $key, "value" => $params[$key]];
        }, array_keys($params));
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->url = $this->url;
        $data->params = $this->params;
        $data->htmltitle = get_string('redirect_title', 'mod_kialo');
        $data->loadingtext = get_string('redirect_loading', 'mod_kialo');
        $data->continuetext = get_string('continue');
        return $data;
    }
}
