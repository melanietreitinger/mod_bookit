// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Booking and resource status controls.
 *
 * @module     mod_bookit/overview/booking_status_dropdown
 * @copyright  2026 ssystems GmbH <oss@ssystems.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {saveCancelPromise, exception} from 'core/notification';
import {get_strings as getStrings} from 'core/str';

const BOOKING_STATUS_SELECTOR = 'select[data-action="update-booking-status"]';
const RESOURCE_STATUS_SELECTOR = 'select[data-action="update-status"]';
const CANCEL_OVERVIEW_SELECTOR = 'button[data-action="cancel-booking-from-overview"]';
const REACTIVATE_OVERVIEW_SELECTOR = 'button[data-action="reactivate-booking-from-overview"]';

/**
 * Apply configured status colours.
 *
 * @param {HTMLSelectElement} select
 */
const applyColor = (select) => {
    const option = select.options[select.selectedIndex];
    const background = option?.dataset.bg || '#ffffff';
    const foreground = option?.dataset.fg || '#000000';
    select.style.backgroundColor = background;
    select.style.color = foreground;
    const cell = select.closest('td');
    if (cell) {
        cell.style.backgroundColor = background;
    }
};

/**
 * Resolve the canonical overview tab for an action.
 *
 * @param {HTMLElement} element
 * @returns {string}
 */
const resolveActiveTab = (element) => {
    if (element.dataset.tab) {
        return element.dataset.tab;
    }
    return new URL(window.location.href).searchParams.get('tab') || 'myevents';
};

/**
 * Write a booking status and navigate to the canonical server-rendered table.
 *
 * @param {HTMLElement} control
 * @param {number} status
 * @returns {Promise<void>}
 */
const updateBookingStatus = (control, status) => {
    control.disabled = true;
    return Ajax.call([{
        methodname: 'mod_bookit_update_event_booking_status',
        args: {
            cmid: Number(control.dataset.cmid || 0),
            eventid: Number(control.dataset.eventid || 0),
            status,
            tab: resolveActiveTab(control),
        },
    }])[0].then((response) => {
        window.location.assign(response.redirecturl);
        return null;
    }).catch((error) => {
        control.disabled = false;
        exception(error);
    });
};

/**
 * Persist a resource status change.
 *
 * @param {HTMLSelectElement} select
 */
const updateResourceStatus = (select) => {
    const row = select.closest('[data-region="event-resources-checklist-item-row"]');
    const previous = row?.dataset.itemStatus || select.value;
    select.disabled = true;
    Ajax.call([{
        methodname: 'mod_bookit_update_event_resource_status',
        args: {
            cmid: Number(select.dataset.cmid || 0),
            eventid: Number(select.dataset.eventid || 0),
            resourceid: Number(row?.dataset.itemResourceid || 0),
            status: select.value,
        },
    }])[0].then(() => {
        applyColor(select);
        select.disabled = false;
        if (row) {
            row.dataset.itemStatus = select.value;
            row.dispatchEvent(new CustomEvent('bookit-resource-status-saved', {
                bubbles: true,
                detail: {status: select.value, itemid: Number(row.dataset.itemid || 0)},
            }));
        }
        return null;
    }).catch((error) => {
        select.value = previous;
        applyColor(select);
        select.disabled = false;
        exception(error);
    });
};

/**
 * Initialise status controls.
 */
export const init = () => {
    document.querySelectorAll(`${BOOKING_STATUS_SELECTOR}, ${RESOURCE_STATUS_SELECTOR}`).forEach(applyColor);

    document.addEventListener('change', (event) => {
        const resource = event.target.closest(RESOURCE_STATUS_SELECTOR);
        if (resource) {
            updateResourceStatus(resource);
            return;
        }
        const booking = event.target.closest(BOOKING_STATUS_SELECTOR);
        if (booking) {
            updateBookingStatus(booking, Number(booking.value));
        }
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest(`${CANCEL_OVERVIEW_SELECTOR}, ${REACTIVATE_OVERVIEW_SELECTOR}`);
        if (!button) {
            return;
        }
        event.preventDefault();
        const reactivate = button.matches(REACTIVATE_OVERVIEW_SELECTOR);
        const strings = reactivate
            ? [
                {key: 'overview_reactivate_booking_confirm', component: 'mod_bookit'},
                {key: 'overview_reactivate_booking_confirm_body', component: 'mod_bookit'},
                {key: 'bookingstatus_action_reactivate', component: 'mod_bookit'},
            ]
            : [
                {key: 'overview_cancel_booking_confirm', component: 'mod_bookit'},
                {key: 'overview_cancel_booking_confirm_body', component: 'mod_bookit'},
                {key: 'overview_cancel_booking', component: 'mod_bookit'},
            ];
        getStrings(strings)
            .then((labels) => saveCancelPromise(labels[0], labels[1], labels[2], {triggerElement: button}))
            .then(() => updateBookingStatus(button, Number(button.dataset.status || 0)))
            .catch((error) => {
                if (!error || error.type === 'modal-save-cancel:cancel') {
                    return null;
                }
                exception(error);
                return null;
            });
    });
};
