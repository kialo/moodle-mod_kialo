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
 * @module      mod_kialo/deeplink_setup_modal
 * @copyright   2025 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Given a modal creation function and a deeplink URL, set up the modal
 *
 * @param {function} modalFn - The modal creation function
 * @param {string} modalTitle - The title for the discussion selection modal
 * @param {string} deeplinkUrl - The URL for discussion selection in Kialo
 */
export const setupModal = async(modalFn, modalTitle, deeplinkUrl) => {
    let modal;
    document.getElementById('id_kialo_select_discussion').addEventListener('click', async() => {
        modal = await modalFn({
            title: modalTitle,
            body: `<iframe class="kialo-iframe" src="${deeplinkUrl}"></iframe>`,
            large: true,
            removeOnClose: true,
        });
        modal.setScrollable(false);
        modal.getModal().addClass('kialo-modal');

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

            modal.destroy();
        }
    );
};
