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
 * Event resources checklist container component.
 *
 * Initializes the reactive store from DOM data and registers
 * one EventResourcesChecklistItem component per item row.
 *
 * @module mod_bookit/event_resources_checklist/event_resources_checklist_container
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getReactive} from 'mod_bookit/event_resources_checklist/event_resources_checklist_reactive';
import EventResourcesChecklistItem from 'mod_bookit/event_resources_checklist/event_resources_checklist_item';
import EventResourcesChecklistProgress from 'mod_bookit/event_resources_checklist/event_resources_checklist_progress';

/**
 * Event resources checklist container component.
 */
export default class EventResourcesChecklistContainer extends BaseComponent {
    /**
     * Static factory: parse DOM, init reactive, create component.
     *
     * @param {string} target - CSS selector for the container element
     * @return {EventResourcesChecklistContainer|null}
     */
    static init(target) {
        const element = document.querySelector(target);
        if (!element) {
            return null;
        }

        // Parse items from DOM data-attributes.
        const items = [];
        element.querySelectorAll('[data-region="event-resources-checklist-item-row"]').forEach(row => {
            items.push({
                id:         parseInt(row.dataset.itemid),
                resourceid: parseInt(row.dataset.itemResourceid),
                status:     row.dataset.itemStatus || 'requested',
            });
        });

        const reactive = getReactive();

        // Create component BEFORE setInitialState so it registers for stateReady.
        const instance = new EventResourcesChecklistContainer({
            element,
            reactive,
        });

        // Moodle reactive converts the array to a Map keyed by item.id.
        reactive.setInitialState({items});

        return instance;
    }

    /**
     * State ready: register all item components and progress bar.
     */
    stateReady() {
        // Hide spinner and reveal content.
        const spinner = document.getElementById('mod-bookit-event-resources-checklist-spinner');
        if (spinner) {
            spinner.classList.add('d-none');
        }
        this.element.classList.remove('d-none');

        this.element.querySelectorAll('[data-region="event-resources-checklist-item-row"]').forEach(row => {
            new EventResourcesChecklistItem({
                element: row,
                reactive: this.reactive,
            });
        });

        // Register the progress bar component if present.
        const progressContainer = this.element.querySelector('[data-region="event-resources-checklist-progress-container"]');
        if (progressContainer) {
            new EventResourcesChecklistProgress({
                element: progressContainer,
                reactive: this.reactive,
            });
        }
    }

    /**
     * No container-level watchers needed (items handle their own status).
     *
     * @return {Array}
     */
    getWatchers() {
        return [];
    }
}
