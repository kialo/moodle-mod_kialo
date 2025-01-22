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
 * Global constants for the Kialo plugin.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

use Packback\Lti1p3\LtiConstants;

define("MOD_KIALO_TERMS_LINK", "https://www.kialo-edu.com/terms");
define("MOD_KIALO_PRIVACY_LINK", "https://www.kialo-edu.com/privacy");
define("MOD_KIALO_DATA_SECURITY_LINK", "https://support.kialo-edu.com/en/hc/kialo-edu-data-security-and-privacy-plan/");

/**
 * Scopes required for the Kialo LTI 1.3 assignment and grading service.
 */
const MOD_KIALO_LTI_AGS_SCOPES = [
    LtiConstants::AGS_SCOPE_LINEITEM_READONLY,
    LtiConstants::AGS_SCOPE_RESULT_READONLY,
    LtiConstants::AGS_SCOPE_SCORE,
];

/**
 * In LTI the deployment ID identifies an LTI tool definition or installation. Moodle sends
 * the same ID for all activities that are based on the same LTI external tool definition.
 * We always send 1 for the plugin because there can only be one Kialo plugin installed which
 * always has the same configuration.
 */
const KIALO_LTI_DEPLOYMENT_ID = "1";
