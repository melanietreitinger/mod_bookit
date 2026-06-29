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
 * Syncs reactive progress state when resource status is saved via the shared
 * booking_status_dropdown handler.
 *
 * @module mod_bookit/event_resources_checklist/event_resources_checklist_item
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';

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
    }

    /**
     * No reactive watchers — status UI is handled by booking_status_dropdown.
     *
     * @return {Array}
     */
    getWatchers() {
        return [];
    }

    /**
     * Listen for successful status saves to update progress state.
     */
    stateReady() {
        if (this.canmanage) {
            this.addEventListener(this.element, 'bookit-resource-status-saved', this._onResourceStatusSaved.bind(this));
        }
    }

    /**
     * Update reactive store after a resource status change.
     *
     * @param {CustomEvent} event
     */
    _onResourceStatusSaved(event) {
        const detail = event.detail || {};
        if (!detail.status) {
            return;
        }
        this.reactive.dispatch('updateStatus', {id: this.itemId, status: detail.status});
        this.element.dataset.itemStatus = detail.status;
    }
}
