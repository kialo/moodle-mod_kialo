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
 * Helpers for Kialo views.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use context_module;

/**
 * Helpers for Kialo views.
 */
class kialo_view {
    /**
     * Returns information about the current group of the user in the activity, if groups are enabled.
     * If no group is active, the returned object will have groupid and groupname set to null.
     *
     * @param \stdClass $cm course module object
     * @param \stdClass $course course object
     * @return \stdClass group information (groupname and groupid)
     */
    public static function get_current_group_info(\stdClass $cm, \stdClass $course): \stdClass {
        $result = new \stdClass();
        $result->groupid = null;
        $result->groupname = null;
        $isteacheroradmin = has_capability('mod/kialo:addinstance', context_module::instance($cm->id));
        if ($isteacheroradmin) {
            // Teachers and admins always see all groups, and are therefore not part of one specific group.
            return $result;
        }

        if ($cm->groupingid) {
            $result->groupid = "grouping-{$cm->groupingid}";
            $result->groupname = groups_get_grouping_name($cm->groupingid);
            return $result;
        }

        $groupid = groups_get_activity_group($cm, $course); // Int or false. 0 means user has access to all groups (admin).
        if ($groupid === false || $groupid === 0) {
            return $result;
        }

        $result->groupid = $groupid;
        $result->groupname = groups_get_group_name($groupid);
        return $result;
    }
}
