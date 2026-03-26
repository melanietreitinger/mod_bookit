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
 * Mutations for resource catalog reactive state.
 *
 * @module mod_bookit/resource_catalog/resource_mutations
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

export default class {

    _callDynamicForm(stateManager, formData) {
        formData['_qf__mod_bookit_local_form_resource_edit_resource_category_form'] = 1; // eslint-disable-line dot-notation
        const encoded = new URLSearchParams(formData).toString();
        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: encoded,
                form: 'mod_bookit\\local\\form\\resource\\edit_resource_category_form',
            },
        }])[0].catch(exception => {
            window.console.error('AJAX error in resource reorder:', exception);
        });
    }

    reOrderItems(stateManager, data) {
        const state = stateManager.state;

        const draggedId = parseInt(data.id);
        const targetId = data.targetId ? parseInt(data.targetId) : null;
        const sourceCategoryId = parseInt(data.parentId);
        const targetCategoryId = data.targetCategoryId ? parseInt(data.targetCategoryId) : sourceCategoryId;

        stateManager.setReadOnly(false);

        // If item moved to a different category, update its categoryid in state.
        if (sourceCategoryId !== targetCategoryId) {
            const draggedItem = state.items.get(draggedId);
            if (draggedItem) {
                draggedItem.categoryid = targetCategoryId;
            }
        }

        // Reorder items within the target category.
        const items = Array.from(state.items.values())
            .filter(item => item.categoryid === targetCategoryId)
            .sort((a, b) => a.sortorder - b.sortorder);

        const dragged = items.find(item => item.id === draggedId);
        const ordered = items.filter(item => item.id !== draggedId);

        if (targetId) {
            const targetIdx = ordered.findIndex(item => item.id === targetId);
            if (targetIdx !== -1) {
                // Respect dropBefore flag to match DOM position (before or after target).
                const insertAt = (data.dropBefore !== false) ? targetIdx : targetIdx + 1;
                ordered.splice(insertAt, 0, dragged);
            } else {
                ordered.push(dragged);
            }
        } else {
            // No target — append to end of category.
            ordered.push(dragged);
        }

        ordered.forEach((item, idx) => {
            const stateItem = state.items.get(item.id);
            if (stateItem) {
                stateItem.sortorder = idx + 1;
            }
        });

        stateManager.setReadOnly(true);

        this._callDynamicForm(stateManager, {
            id: 0,
            name: 'reorder',
            items: JSON.stringify(ordered.map(item => item.id)),
            targetcategoryid: targetCategoryId,
            itemid: draggedId,
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
            // Respect dropBefore flag to match DOM position (before or after target).
            const insertAt = (data.dropBefore !== false) ? targetIdx : targetIdx + 1;
            ordered.splice(insertAt, 0, dragged);
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

        this._callDynamicForm(stateManager, {
            id: 0,
            name: 'reorder',
            categoryorder: ordered.map(cat => cat.id).join(','),
        });
    }
    /**
     * Handle category updated (called by processUpdates for action: 'put').
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object
     */
    categoriesUpdated(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        state.categories.set(data.fields.id, {
            id: data.fields.id,
            name: data.fields.name,
            description: data.fields.description,
            sortorder: data.fields.sortorder,
        });

        stateManager.setReadOnly(true);
    }

    /**
     * Handle category deletion (called by processUpdates for action: 'delete').
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object containing id
     */
    categoriesDeleted(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        // Delete all items in this category first
        const itemsToDelete = [];
        state.items.forEach((item, id) => {
            if (item.categoryid === data.fields.id) {
                itemsToDelete.push(id);
            }
        });
        itemsToDelete.forEach(id => state.items.delete(id));

        // Delete the category
        state.categories.delete(data.fields.id);

        stateManager.setReadOnly(true);
    }

    /**
     * Handle item updated (called by processUpdates for action: 'put').
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object
     */
    itemsUpdated(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        state.items.set(data.fields.id, {
            id: data.fields.id,
            name: data.fields.name,
            description: data.fields.description,
            categoryid: data.fields.categoryid,
            amount: data.fields.amount,
            amountirrelevant: data.fields.amountirrelevant,
            sortorder: data.fields.sortorder,
            active: Boolean(data.fields.active),
            roomids: data.fields.roomids || [],
            roomnames: data.fields.roomnames || [],
        });

        stateManager.setReadOnly(true);
    }

    /**
     * Handle item deletion (called by processUpdates for action: 'delete').
     *
     * @param {Object} stateManager - The reactive state manager
     * @param {Object} data - Data with fields object containing id
     */
    itemsDeleted(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        state.items.delete(data.fields.id);

        stateManager.setReadOnly(true);
    }
}
