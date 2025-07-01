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

/**
 * External API function to update activity URL
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class mod_kialo_update_activity_url extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'old_discussion_id' => new external_value(PARAM_INT, 'The old discussion ID'),
            'new_discussion_id' => new external_value(PARAM_INT, 'The new discussion ID'),
        ]);
    }

    public static function execute($olddiscussionid, $newdiscussionid) {
        global $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'old_discussion_id' => $olddiscussionid,
            'new_discussion_id' => $newdiscussionid,
        ]);

        debugging("Kialo: update_activity_url called with old discussion ID: {$params['old_discussion_id']}, new discussion ID: {$params['new_discussion_id']}");

        // To be implemented: Logic to update the activity URL in the database.

        return [
            'success' => true,
            'message' => 'Activity URL update logged successfully',
            'old_discussion_id' => $params['old_discussion_id'],
            'new_discussion_id' => $params['new_discussion_id'],
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'message' => new external_value(PARAM_TEXT, 'Response message'),
            'old_discussion_id' => new external_value(PARAM_INT, 'The old discussion ID that was provided'),
            'new_discussion_id' => new external_value(PARAM_INT, 'The new discussion ID that was provided'),
        ]);
    }
}
