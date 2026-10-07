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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */
$plugin->component = 'mod_kialo';

// See https://moodledev.io/docs/apis/commonfiles/version.php.
$plugin->version = 2026100701;  // Must be incremented for each new release!
$plugin->release = '1.7.0';  // Semantic version.

// Officially we require PHP 8.1. The first Moodle version that requires this as a minimum is Moodle 4.4.
// But technically this plugin also runs on older Moodle versions, as long as they run on PHP 8.1,
// which Moodle 4.1-4.3 also support.
$plugin->requires = 2022112800; // 4.1 and later.

$plugin->maturity = MATURITY_STABLE;
