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

namespace mod_kialo\admin;

use admin_setting_configcheckbox;

/**
 * Extends moodle's built-in admin checkbox, allowing us to make it read-only.
 *
 * @package    mod_kialo
 * @copyright  2023 Kialo GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kialo_configcheckbox extends admin_setting_configcheckbox {
    /**
     * If set to true, the checkbox will be rendered as read-only.
     * @var bool
     */
    protected $forcereadonly = false;

    /**
     * Set the forcereadonly setting - if true, the checkbox will be rendered as read-only.
     * @param bool $forcereadonly
     * @return void
     */
    public function force_readonly(bool $forcereadonly) {
        $this->forcereadonly = $forcereadonly;

        // If the checkbox is read-only, it should not be saved. It would be reset to its default otherwise when saving.
        $this->nosave = $forcereadonly;
    }

    /**
     * Usually a checkbox is only rendered as read-only if the plugin setting is overriden in config.php.
     * This class allows controlling this independently.
     * @return bool
     */
    public function is_readonly(): bool {
        if ($this->forcereadonly) {
            return true;
        }

        return parent::is_readonly();
    }
}
