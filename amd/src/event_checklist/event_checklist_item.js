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

/**
 * Event checklist item component.
 *
 * Handles checkbox toggling: calls AJAX to persist state and fires
 * reactive mutation to update local state (for progress bar).
 *
 * @module mod_bookit/event_checklist/event_checklist_item
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import Ajax from 'core/ajax';

const SELECTORS = {
    CHECKBOX: '[data-region="event-checklist-item-checkbox"]',
    LABEL:    '[data-region="event-checklist-item-label"]',
};

/**
 * Event checklist item component.
 */
export default class EventChecklistItem extends BaseComponent {

    /**
     * State ready: register checkbox change listener.
     */
    stateReady() {
        this.addEventListener(
            this.getElement(SELECTORS.CHECKBOX),
            'change',
            this._onCheckboxChange.bind(this)
        );
    }

    /**
     * Handle checkbox change: persist via AJAX and update reactive state.
     *
     * @param {Event} event
     */
    async _onCheckboxChange(event) {
        const checkbox = event.currentTarget;
        const done = checkbox.checked;
        const id = parseInt(this.element.dataset.itemid);
        const state = this.reactive.stateManager.state;
        const item = state.items.get(id);
        const cmid = item ? item.cmid : parseInt(this.element.closest('[data-cmid]')?.dataset.cmid ?? 0);
        const eventid = item ? item.eventid : parseInt(this.element.closest('[data-eventid]')?.dataset.eventid ?? 0);

        // Update reactive state immediately (optimistic update).
        this.reactive.dispatch('toggleDone', {id, done});

        // Update label style.
        const label = this.getElement(SELECTORS.LABEL);
        if (label) {
            if (done) {
                label.classList.add('text-decoration-line-through', 'text-muted');
            } else {
                label.classList.remove('text-decoration-line-through', 'text-muted');
            }
        }

        // Persist via AJAX.
        try {
            await Ajax.call([{
                methodname: 'mod_bookit_toggle_event_checklist_item',
                args: {
                    cmid,
                    eventid,
                    checklistitemid: id,
                    done,
                },
            }])[0];
        } catch (e) {
            // Revert on failure.
            this.reactive.dispatch('toggleDone', {id, done: !done});
            checkbox.checked = !done;
            if (label) {
                if (!done) {
                    label.classList.add('text-decoration-line-through', 'text-muted');
                } else {
                    label.classList.remove('text-decoration-line-through', 'text-muted');
                }
            }
        }
    }

    /**
     * @return {Array}
     */
    getWatchers() {
        return [];
    }
}
