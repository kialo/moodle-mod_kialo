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
 * Web service function declarations
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_kialo_update_activity_url' => [
        'classname'   => 'mod_kialo_update_activity_url',
        'methodname'  => 'execute',
        'classpath'   => 'mod/kialo/classes/external/update_activity_url.php',
        'description' => 'Update activity URL from old URL to new URL',
        'type'        => 'write',
        'capabilities' => 'mod/kialo:addinstance',
        'services'    => [],
    ],
];

$services = [
    'Kialo activity service' => [
        'functions' => ['mod_kialo_update_activity_url'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
