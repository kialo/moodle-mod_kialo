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
        $groupid = groups_get_activity_group($cm, $course); // Int or false. 0 means user has access to all groups (admin).
        if ($groupid === false || $groupid === 0) {
            $result = new \stdClass();
            $result->groupid = null;
            $result->groupname = null;
            return $result;
        }

        $result = new \stdClass();
        $result->groupid = $groupid;
        $result->groupname = groups_get_group_name($groupid);
        ;
        return $result;
    }
}
