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
 * Javascript code for module instance settings form.
 *
 * @module      mod_kialo/discussion_selection_modal
 * @copyright   2025 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import {getString} from 'core/str';
import {setupModal} from './deeplink_setup_modal';

/**
 * Initialize the discussion selection modal
 *
 * @param {string} deeplinkUrl - The url for discussion selection in Kialo
 */
export const init = async(deeplinkUrl) => {
    await setupModal(
        Modal.create.bind(Modal),
        await getString('select_discussion', 'mod_kialo'),
        deeplinkUrl
    );
};
