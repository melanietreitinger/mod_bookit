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
 * Event checklist container component.
 *
 * Initializes the reactive store and registers item + progress components.
 *
 * @module mod_bookit/event_checklist/event_checklist_container
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getReactive} from 'mod_bookit/event_checklist/event_checklist_reactive';
import EventChecklistItem from 'mod_bookit/event_checklist/event_checklist_item';
import EventChecklistProgress from 'mod_bookit/event_checklist/event_checklist_progress';

/**
 * Event checklist container component.
 */
export default class EventChecklistContainer extends BaseComponent {
    /**
     * Static factory: parse DOM, init reactive, create component.
     *
     * @param {string} target - CSS selector for container element
     * @return {EventChecklistContainer|null}
     */
    static init(target) {
        const element = document.querySelector(target);
        if (!element) {
            return null;
        }

        const cmid = parseInt(element.dataset.cmid);
        const eventid = parseInt(element.dataset.eventid);

        // Parse items from DOM data-attributes.
        const items = [];
        element.querySelectorAll('[data-region="event-checklist-item-row"]').forEach(row => {
            items.push({
                id: parseInt(row.dataset.itemid),
                done: row.dataset.itemdone === 'true' || row.dataset.itemdone === '1',
                cmid,
                eventid,
            });
        });

        const reactive = getReactive();

        const instance = new EventChecklistContainer({element, reactive});

        reactive.setInitialState({items});

        return instance;
    }

    /**
     * State ready: register item and progress components.
     */
    stateReady() {
        // Hide spinner and reveal content.
        const spinner = document.getElementById('mod-bookit-event-checklist-spinner');
        if (spinner) {
            spinner.classList.add('d-none');
        }
        this.element.classList.remove('d-none');

        this.element.querySelectorAll('[data-region="event-checklist-item-row"]').forEach(row => {
            new EventChecklistItem({element: row, reactive: this.reactive});
        });

        const progressContainer = this.element.querySelector(
            '[data-region="event-checklist-progress-container"]'
        );
        if (progressContainer) {
            new EventChecklistProgress({element: progressContainer, reactive: this.reactive});
        }
    }

    /**
     * @return {Array}
     */
    getWatchers() {
        return [];
    }
}
