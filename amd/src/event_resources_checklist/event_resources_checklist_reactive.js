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
 * Reactive store for event resources checklist.
 *
 * @module mod_bookit/event_resources_checklist/event_resources_checklist_reactive
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Reactive} from 'core/reactive';
import EventResourcesChecklistMutations from 'mod_bookit/event_resources_checklist/event_resources_checklist_mutations';

const EVENTNAME = 'mod_bookit:event_resources_checklist_state_event';

let eventResourcesChecklistReactiveInstance = null;

/**
 * Dispatch the event resources checklist state event.
 *
 * @param {Object} detail - Event detail
 * @param {HTMLElement} target - Target element
 */
function dispatchEventResourcesChecklistStateEvent(detail, target) {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(
        new CustomEvent(EVENTNAME, {bubbles: true, detail})
    );
}

/**
 * Get or create the event resources checklist reactive instance.
 *
 * @return {Reactive} Reactive instance
 */
export const getReactive = () => {
    if (!eventResourcesChecklistReactiveInstance) {
        eventResourcesChecklistReactiveInstance = new Reactive({
            name: 'Moodle Bookit Event Resources Checklist',
            eventName: EVENTNAME,
            eventDispatch: dispatchEventResourcesChecklistStateEvent,
            mutations: new EventResourcesChecklistMutations(),
        });
    }
    return eventResourcesChecklistReactiveInstance;
};
