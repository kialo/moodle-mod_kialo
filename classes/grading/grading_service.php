<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.MemberNameUnderscore
// phpcs:disable moodle.Files.RequireLogin.Missing -- doesn't require user to be logged in, as it's an LTI service

namespace mod_kialo\grading;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../lib.php');
require_once(__DIR__ . '/../../constants.php');
require_once(__DIR__ . '/../../vendor/autoload.php');
require_once($CFG->libdir . '/gradelib.php');

use grade_item;
use moodle_url;
use OAT\Library\Lti1p3Core\Exception\LtiException;

/**
 * Service offering grading functionalities for LTI requests.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grading_service {
    /**
     * Returns the line_item describing the grading settings for the given course module.
     *
     * @param int $courseid
     * @param int $cmid
     * @param string $resourcelinkid
     * @return void
     * @throws LtiException
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function get_line_item(int $courseid, int $cmid, string $resourcelinkid): line_item {
        $module = get_coursemodule_from_id('kialo', $cmid, $courseid, false, MUST_EXIST);

        $gradeitem = grade_item::fetch(['iteminstance' => $module->instance, 'itemtype' => 'mod']);
        if (!$gradeitem) {
            $maxscore = 100;
        } else {
            $maxscore = $gradeitem->grademax;
        }

        $lineitem = new line_item();

        // Assuming this is called from /mod/kialo/lti_lineitem.php. The ID is the URL of the request.
        $lineitem->id = (new moodle_url($_SERVER['REQUEST_URI']))->out(false);
        $lineitem->label = $module->name;
        $lineitem->scoreMaximum = floatval($maxscore);
        $lineitem->resourceLinkId = $resourcelinkid;

        return $lineitem;
    }

    /**
     * Writes grade information. The expected data format is the one defined in the spec,
     * see https://www.imsglobal.org/spec/lti-ags/v2p0#example-posting-a-final-score-update.
     *
     * @param int $courseid
     * @param int $cmid
     * @param array $data array with required field userId
     * @return bool Returns true if the grade information could be persisted.
     * @throws LtiException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function update_grade(int $courseid, int $cmid, array $data): bool {
        global $DB;

        $module = get_coursemodule_from_id('kialo', $cmid, $courseid, false, MUST_EXIST);
        $moduleinstance = $DB->get_record('kialo', ['id' => $module->instance], '*', MUST_EXIST);

        // Validate that the userId exist in $data.
        if (!isset($data['userId'])) {
            throw new LtiException("Missing userId in the request body");
        }

        // Receive a score for the line item via JSON request body.
        $userid = $data['userId'];
        $scoregiven = isset($data['scoreGiven']) ? floatval($data['scoreGiven']) : null;
        $comment = $data['comment'] ?? '';
        $timestamp = isset($data['timestamp']) ? strtotime($data['timestamp']) : time();

        $grades = [
            'userid' => $userid,
            'feedback' => $comment,
            'dategraded' => $timestamp,
        ];
        if ($scoregiven !== null) {
            $grades['rawgrade'] = $scoregiven;
        } else {
            $grades['rawgrade'] = null;
        }

        $result = kialo_grade_item_update($moduleinstance, (object) $grades);
        return ($result === GRADE_UPDATE_OK || $result === GRADE_UPDATE_MULTIPLE);
    }
}
