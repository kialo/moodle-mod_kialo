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
 * This receives the LTI auth request from Kialo, and redirects to the Kialo activity.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT
 * @var stdClass $USER
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- We call require_login in lti_auth helper method below.

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once('vendor/autoload.php');

use mod_kialo\kialo_config;
use mod_kialo\lti_flow;
use mod_kialo\output\loading_page;

try {
    $form = lti_flow::lti_auth();

    $output = $PAGE->get_renderer('mod_kialo');
    echo $output->render(new loading_page(
            get_string("redirect_title", "mod_kialo"),
            get_string("redirect_loading", "mod_kialo"),
            $form
    ));
} catch (Throwable $e) {
    // Show Moodle's default error page including some debug info.
    throw new \moodle_exception('errors:ltiauth', 'kialo', '', null, $e->getMessage() . "\n" . $e->getTraceAsString());
}

