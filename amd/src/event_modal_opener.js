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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Shared helper for opening the Bookit edit_event_form modal with the correct footer.
 *
 * @module mod_bookit/event_modal_opener
 * @copyright 2026 ssystems GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core_form/modalform',
    'core/notification',
    'core/str',
    'mod_bookit/possible_slots_refresh',
    'mod_bookit/booking_form_resources'
], function(ModalForm, Notification, Str, PossibleSlotsRefresh, BookingFormResources) {

    /**
     * Open the event detail modal.
     *
     * @param {Object} options
     * @param {number|string} options.cmid Course module id
     * @param {number|string} options.eventid Event id
     * @param {string} options.title Modal title
     * @param {string} [options.modalfootermode] view_only|editable
     * @param {Function} [options.onSubmitted] Callback after successful submit
     * @param {boolean} [options.reloadOnSubmit] Reload page on submit (overview default)
     * @returns {ModalForm}
     */
    const openEditEventModal = (options) => {
        const viewonly = options.modalfootermode === 'view_only';
        const modalconfig = {
            formClass: 'mod_bookit\\form\\edit_event_form',
            args: {
                cmid: options.cmid,
                id: options.eventid,
                readonly: 1,
            },
            modalConfig: {
                title: options.title,
            },
        };

        if (viewonly) {
            modalconfig.moduleName = 'core/modal_cancel';
        }

        const modal = new ModalForm(modalconfig);

        modal.addEventListener(modal.events.LOADED, function() {
            PossibleSlotsRefresh.initPossibleStarttimesRefresh(options.cmid, options.eventid);
            if (!modal.modal) {
                return;
            }
            BookingFormResources.init(modal.modal.getRoot()[0]);
            if (viewonly) {
                Str.get_string('ok', 'moodle').then(function(okstring) {
                    modal.modal.setButtonText('cancel', okstring);
                    return okstring;
                }).catch(Notification.exception);
            } else {
                Str.get_string('close_and_discard_changes', 'mod_bookit').then(function(cancelstring) {
                    modal.modal.setButtonText('cancel', cancelstring);
                    return cancelstring;
                }).catch(Notification.exception);
            }
        });

        if (typeof options.onSubmitted === 'function') {
            modal.addEventListener(modal.events.FORM_SUBMITTED, options.onSubmitted);
        } else if (options.reloadOnSubmit !== false) {
            modal.addEventListener(modal.events.FORM_SUBMITTED, function() {
                window.location.reload();
            });
        }

        modal.show();
        return modal;
    };

    return {
        openEditEventModal: openEditEventModal,
    };
});
