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
 * Mutations for event checklist reactive state.
 *
 * @module mod_bookit/event_checklist/event_checklist_mutations
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Event checklist mutations.
 */
export default class EventChecklistMutations {
    /**
     * Toggle the done state of a checklist item.
     *
     * @param {Object} stateManager
     * @param {Object} args
     * @param {number} args.id - bookit_checklist_item ID
     * @param {boolean} args.done - New done state
     */
    toggleDone(stateManager, {id, done}) {
        const state = stateManager.state;
        const item = state.items.get(parseInt(id));
        if (!item) {
            return;
        }
        stateManager.setReadOnly(false);
        item.done = done;
        stateManager.setReadOnly(true);
    }
}
