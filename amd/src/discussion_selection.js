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
 * @module      mod_kialo/discussion_selection
 * @copyright   2025 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @param {string} deeplinkUrl - The url for discussion selection in Kialo
 */

import ModalFactory from 'core/modal_factory';
import {getString} from 'core/str';

// TODO: use Modal for newer versions of Moodle? ModalFactory will be removed in 4.7 and 5.2
// only support for moodle 4.0+
export const init = async(deeplinkUrl) => {
    let modal;
    document.getElementById('id_kialo_select_discussion').addEventListener('click', async() => {
        modal = await ModalFactory.create({
            title: await getString('select_discussion', 'mod_kialo'),
            body: `<iframe class="kialo-iframe" src="${deeplinkUrl}"></iframe>`,
            large: true,
        });
        modal.show();
    });

    window.addEventListener(
        'message',
        (event) => {
            if (event.data.type !== 'kialo_discussion_selected') {
                return;
            }

            // Fill in the deep-linked details.
            document.querySelector('input[name=discussion_url]').value = event.data.discussionurl;
            document.querySelector('input[name=discussion_title]').value = event.data.discussiontitle;

            // Prefill activity name based on discussion title if user hasn't entered one yet.
            const nameInput = document.querySelector('input[name=name]');
            if (!nameInput.value) {
                nameInput.value = event.data.discussiontitle;
            }

            if (modal) {
                modal.hide();
                modal.destroy();
            }
        }
    );
};
