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
 * Mutations for event resources checklist reactive state.
 *
 * @module mod_bookit/event_resources_checklist/event_resources_checklist_mutations
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Event resources checklist mutations.
 *
 * Only mutation needed: update the status of one event-resource item.
 */
export default class EventResourcesChecklistMutations {
    /**
     * Update the status of an event resource item.
     *
     * @param {Object} stateManager - Moodle reactive state manager
     * @param {Object} args - Mutation arguments
     * @param {number} args.id - bookit_event_resource record ID
     * @param {string} args.status - New status value
     */
    updateStatus(stateManager, {id, status}) {
        const state = stateManager.state;
        const item = state.items.get(parseInt(id));
        if (!item) {
            return;
        }
        stateManager.setReadOnly(false);
        item.status = status;
        stateManager.setReadOnly(true);
    }
}
