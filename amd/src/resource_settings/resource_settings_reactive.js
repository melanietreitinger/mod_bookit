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
 * Reactive store for the resource checklist.
 *
 * @module mod_bookit/resource_settings/resource_settings_reactive
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Reactive} from 'core/reactive';
import ResourceSettingsMutations from './resource_settings_mutations';

export const EVENTNAME = 'mod_bookit:resource_settings_state_event';

/**
 * Dispatch a resource checklist state event.
 *
 * @param {Object} detail - Event detail payload
 * @param {HTMLElement} target - Dispatch target (defaults to document)
 */
export const dispatchChecklistStateEvent = (detail, target) => {
    (target || document).dispatchEvent(
        new CustomEvent(EVENTNAME, {bubbles: true, detail})
    );
};

let checklistReactiveInstance = null;

/**
 * Initialize the reactive store for the resource checklist.
 *
 * Creates the Reactive instance if it doesn't exist yet.
 *
 * @return {Reactive} The reactive instance
 */
export const initChecklistReactive = () => {
    if (!checklistReactiveInstance) {
        checklistReactiveInstance = new Reactive({
            name: 'Moodle Bookit Resource Checklist',
            eventName: EVENTNAME,
            eventDispatch: dispatchChecklistStateEvent,
            mutations: new ResourceSettingsMutations(),
        });
    }
    return checklistReactiveInstance;
};

/**
 * Get the reactive instance.
 *
 * Returns null if initChecklistReactive() has not been called yet.
 *
 * @return {Reactive|null} Reactive instance
 */
export const getChecklistReactive = () => checklistReactiveInstance;
