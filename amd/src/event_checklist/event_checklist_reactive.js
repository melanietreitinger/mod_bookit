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
 * Reactive store for event checklist.
 *
 * @module mod_bookit/event_checklist/event_checklist_reactive
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Reactive} from 'core/reactive';
import EventChecklistMutations from 'mod_bookit/event_checklist/event_checklist_mutations';

const EVENTNAME = 'mod_bookit:event_checklist_state_event';

let eventChecklistReactiveInstance = null;

/**
 * Get or create the event checklist reactive instance.
 *
 * @return {Reactive}
 */
export const getReactive = () => {
    if (!eventChecklistReactiveInstance) {
        eventChecklistReactiveInstance = new Reactive({
            name: 'Moodle Bookit Event Checklist',
            eventName: EVENTNAME,
            eventDispatch: (detail, target) => {
                (target || document).dispatchEvent(new CustomEvent(EVENTNAME, {bubbles: true, detail}));
            },
            mutations: new EventChecklistMutations(),
        });
    }
    return eventChecklistReactiveInstance;
};
