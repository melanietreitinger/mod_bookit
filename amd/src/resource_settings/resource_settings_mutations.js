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
 * Mutations for resource checklist reactive state.
 *
 * Standalone mutations class for the resource checklist reactive store.
 * Handles CRUD operations on categories and checklist items.
 *
 * @module mod_bookit/resource_settings/resource_settings_mutations
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Mutations for resource checklist reactive state.
 */
export default class ResourceSettingsMutations {

    _callCategoryForm(formData) {
        formData['_qf__mod_bookit_local_form_resource_edit_resource_category_form'] = 1; // eslint-disable-line dot-notation
        const encoded = new URLSearchParams(formData).toString();
        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: encoded,
                form: 'mod_bookit\\local\\form\\resource\\edit_resource_category_form',
            },
        }])[0].catch(exception => {
            window.console.error('AJAX error in checklist category reorder:', exception);
        });
    }

    _callItemForm(formData) {
        formData['_qf__mod_bookit_local_form_resource_edit_resource_settings_item_form'] = 1; // eslint-disable-line dot-notation
        const encoded = new URLSearchParams(formData).toString();
        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: encoded,
                form: 'mod_bookit\\local\\form\\resource\\edit_resource_settings_item_form',
            },
        }])[0].catch(exception => {
            window.console.error('AJAX error in checklist item reorder:', exception);
        });
    }

    reOrderItems(stateManager, data) {
        const state = stateManager.state;

        const items = Array.from(state.checklistitems.values())
            .filter(item => item.categoryid === data.parentId)
            .sort((a, b) => a.sortorder - b.sortorder);

        const draggedId = parseInt(data.id);
        const targetId = parseInt(data.targetId);
        const dragged = items.find(item => item.id === draggedId);
        const ordered = items.filter(item => item.id !== draggedId);
        const targetIdx = ordered.findIndex(item => item.id === targetId);

        if (targetIdx !== -1) {
            ordered.splice(targetIdx, 0, dragged);
        } else {
            ordered.push(dragged);
        }

        stateManager.setReadOnly(false);
        ordered.forEach((item, idx) => {
            const stateItem = state.checklistitems.get(item.id);
            if (stateItem) {
                stateItem.sortorder = idx + 1;
            }
        });
        stateManager.setReadOnly(true);

        this._callItemForm({
            id: 0,
            items: JSON.stringify(ordered.map(item => item.id)),
        });
    }

    reOrderCategories(stateManager, data) {
        const state = stateManager.state;

        const categories = Array.from(state.categories.values())
            .sort((a, b) => a.sortorder - b.sortorder);

        const draggedId = parseInt(data.id);
        const targetId = parseInt(data.targetId);
        const dragged = categories.find(cat => cat.id === draggedId);
        const ordered = categories.filter(cat => cat.id !== draggedId);
        const targetIdx = ordered.findIndex(cat => cat.id === targetId);

        if (targetIdx !== -1) {
            ordered.splice(targetIdx, 0, dragged);
        } else {
            ordered.push(dragged);
        }

        stateManager.setReadOnly(false);
        ordered.forEach((cat, idx) => {
            const stateCat = state.categories.get(cat.id);
            if (stateCat) {
                stateCat.sortorder = idx + 1;
            }
        });
        stateManager.setReadOnly(true);

        this._callCategoryForm({
            id: 0,
            name: 'reorder',
            categoryorder: ordered.map(cat => cat.id).join(','),
        });
    }

    /**
     * Placeholder for state event dispatching.
     */
    checklistStateEvent() {
        // Placeholder — state events are dispatched via dispatchResourceSettingsStateEvent.
    }

    // -------------------------------------------------------------------------
    // Category mutations
    // -------------------------------------------------------------------------

    /**
     * Handle category created.
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object
     */
    categoriesCreated(stateManager, data) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        state.categories.set(data.fields.id, {
            id: data.fields.id,
            name: data.fields.name,
            description: data.fields.description || '',
            sortorder: data.fields.sortorder || 0,
        });
        stateManager.setReadOnly(true);
    }

    /**
     * Handle category updated.
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object
     */
    categoriesUpdated(stateManager, data) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        const existing = state.categories.get(data.fields.id) || {};
        state.categories.set(data.fields.id, {
            ...existing,
            id: data.fields.id,
            name: data.fields.name,
            description: data.fields.description || '',
            sortorder: data.fields.sortorder || existing.sortorder || 0,
        });
        stateManager.setReadOnly(true);
    }

    /**
     * Handle category deleted.
     *
     * Also removes all checklist items belonging to the deleted category.
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object containing id
     */
    categoriesDeleted(stateManager, data) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);

        const itemsToDelete = [];
        state.checklistitems.forEach((item, id) => {
            if (item.categoryid === data.fields.id) {
                itemsToDelete.push(id);
            }
        });
        itemsToDelete.forEach(id => state.checklistitems.delete(id));

        state.categories.delete(data.fields.id);
        stateManager.setReadOnly(true);
    }

    // -------------------------------------------------------------------------
    // Checklist item mutations
    // -------------------------------------------------------------------------

    /**
     * Handle checklist item created.
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object
     */
    itemsCreated(stateManager, data) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        state.checklistitems.set(data.fields.id, this._buildItemRecord(data.fields, {}));
        stateManager.setReadOnly(true);
    }

    /**
     * Handle checklist item updated.
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object
     */
    itemsUpdated(stateManager, data) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        const existing = state.checklistitems.get(data.fields.id) || {};
        state.checklistitems.set(data.fields.id, this._buildItemRecord(data.fields, existing));
        stateManager.setReadOnly(true);
    }

    /**
     * Handle checklist item deleted.
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object containing id
     */
    itemsDeleted(stateManager, data) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);
        state.checklistitems.delete(data.fields.id);
        stateManager.setReadOnly(true);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Build a checklist item record from field data.
     *
     * @param {Object} fields - Field data from server response
     * @param {Object} existing - Existing record for fallback values
     * @return {Object} Complete item record
     */
    _buildItemRecord(fields, existing) {
        return {
            ...existing,
            id: fields.id,
            name: fields.name,
            description: fields.description || '',
            categoryid: fields.categoryid,
            sortorder: fields.sortorder || existing.sortorder || 0,
            resourceid: fields.resourceid || 0,
            duedate: fields.duedate || null,
            duedatetype: fields.duedatetype || null,
            duedatedisplay: fields.duedatedisplay || null,
            active: Boolean(fields.active ?? true),
            beforedueid: fields.beforedueid || null,
            whendueid: fields.whendueid || null,
            overdueid: fields.overdueid || null,
            whendoneid: fields.whendoneid || null,
        };
    }
}
