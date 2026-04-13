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
 * Event resources checklist item component.
 *
 * Handles status dropdown changes via AJAX and updates the
 * reactive state and DOM status badge accordingly.
 *
 * @module mod_bookit/event_resources_checklist/event_resources_checklist_item
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';

/** CSS badge classes per status */
const STATUS_BADGE_CLASSES = {
    requested:  'badge badge-secondary',
    confirmed:  'badge badge-success',
    inprogress: 'badge badge-primary',
    rejected:   'badge badge-danger',
};

/**
 * Event resources checklist item component.
 */
export default class EventResourcesChecklistItem extends BaseComponent {
    /**
     * Initialize component properties.
     */
    create() {
        this.itemId = parseInt(this.element.dataset.itemid);
        const container = this.element.closest('[data-region="event-resources-checklist-container"]');
        this.canmanage = container ? container.dataset.canmanage === '1' : false;
        this.cmid = container ? parseInt(container.dataset.cmid) : 0;
        this.eventid = container ? parseInt(container.dataset.eventid) : 0;
        this.strings = {};
    }

    /**
     * Watch item status in reactive state.
     *
     * @return {Array}
     */
    getWatchers() {
        return [
            {watch: `items.status:updated`, handler: this._onStatusUpdated.bind(this)},
        ];
    }

    /**
     * Attach event listeners after state is ready.
     */
    stateReady() {
        if (this.canmanage) {
            const select = this.getElement('[data-action="update-status"]');
            if (select) {
                this.addEventListener(select, 'change', this._onDropdownChange.bind(this));
            }
        }
        this._loadStrings();
    }

    /**
     * Handle status dropdown change: call AJAX, then update state.
     *
     * @param {Event} event
     */
    _onDropdownChange(event) {
        const select = event.currentTarget;
        const newStatus = select.value;
        const previousStatus = this.element.dataset.itemStatus;

        // Optimistic update.
        this.element.dataset.itemStatus = newStatus;
        select.disabled = true;

        Ajax.call([{
            methodname: 'mod_bookit_update_event_resource_status',
            args: {
                cmid:       this.cmid,
                eventid:    this.eventid,
                resourceid: parseInt(this.element.dataset.itemResourceid),
                status:     newStatus,
            },
        }])[0]
        .then(() => {
            // Update reactive state on success.
            this.reactive.dispatch('updateStatus', {id: this.itemId, status: newStatus});
            select.disabled = false;
            return true;
        })
        .catch(e => {
            // Revert on error and log for debugging.
            select.value = previousStatus;
            this.element.dataset.itemStatus = previousStatus;
            window.console.error('Event resource status update failed:', e);
            select.disabled = false;
        });
    }

    /**
     * React to status update in state (for read-only badge re-render).
     *
     * @param {Object} args - Watcher args from Moodle reactive
     * @param {Object} args.element - Updated item from state
     */
    _onStatusUpdated({element}) {
        // Only handle our own item.
        if (!element || element.id !== this.itemId) {
            return;
        }
        if (!this.canmanage) {
            this._updateStatusBadge(element.status);
        }
        this.element.dataset.itemStatus = element.status;
    }

    /**
     * Update the read-only status badge text and class.
     *
     * @param {string} status
     */
    _updateStatusBadge(status) {
        const badge = this.getElement('[data-field="status-badge"]');
        if (!badge) {
            return;
        }
        badge.className = STATUS_BADGE_CLASSES[status] || 'badge badge-secondary';
        const label = this.strings[status] || status;
        badge.textContent = label;
    }

    /**
     * Pre-load status label strings for badge updates.
     */
    async _loadStrings() {
        try {
            const strs = await getStrings([
                {key: 'resources:status_requested', component: 'mod_bookit'},
                {key: 'resources:status_confirmed', component: 'mod_bookit'},
                {key: 'resources:status_inprogress', component: 'mod_bookit'},
                {key: 'resources:status_rejected', component: 'mod_bookit'},
            ]);
            this.strings = {
                requested:  strs[0],
                confirmed:  strs[1],
                inprogress: strs[2],
                rejected:   strs[3],
            };
        } catch (_) {
            // Strings stay empty; badge will show raw key as fallback.
        }
    }
}
