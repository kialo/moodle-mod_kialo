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

/**
 * Data container used for the LTI implementation.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deep_linking_result {
    /**
     * Unique string identifier that was sent by our Moodle plugin when the activity was created.
     * When the user selects a discussion on Kialo, this identifier is used to store the selected
     * discussion, and to associate the correct discussion later when students open the activity
     * with this deployment id.
     *
     * @var string
     */
    public $deploymentid;

    /**
     * @var string URL of the selected discussion.
     */
    public $discussionurl;

    /**
     * @var string Used to display to the user what they selected.
     */
    public $discussiontitle;

    /**
     * Creates a new deep linking result.
     * @param string $deploymentid
     * @param string $discussionurl
     * @param string $discussiontitle
     */
    public function __construct(string $deploymentid, string $discussionurl, string $discussiontitle) {
        $this->deploymentid = $deploymentid;
        $this->discussionurl = $discussionurl;
        $this->discussiontitle = $discussiontitle;
    }
}
