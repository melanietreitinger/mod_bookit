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
 * Resource checklist category component.
 *
 * Standalone component managing a category row and all its checklist items.
 * Handles add/edit/delete category and item operations via modal forms.
 *
 * @module mod_bookit/resource_settings/resource_settings_category
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop} from 'core/reactive';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Notification from 'core/notification';
import ResourceSettingsItem from './resource_settings_item';

const CATEGORY_REGION = 'resource-checklist-category';
const ITEM_REGION = 'resource-checklist-item-row';
const CATEGORY_MODAL_FORM = 'mod_bookit\\form\\edit_category_form';
const ITEM_MODAL_FORM = 'mod_bookit\\local\\form\\resource\\edit_resource_settings_item_form';

/**
 * Resource checklist category component.
 */
export default class ResourceSettingsCategory extends BaseComponent {

    /**
     * Component descriptor for debugging.
     *
     * @return {string} Component name
     */
    static get componentName() {
        return 'mod_bookit/resource_settings/resource_settings_category';
    }

    /**
     * Create component.
     *
     * @param {Object} options - Component options
     * @param {HTMLElement} options.element - Category container element (tbody)
     */
    create({element}) {
        this.categoryId = parseInt(element.dataset.categoryid);
        this.categoryData = null;
        this.itemComponents = new Map();
        this._categoryDragDrop = null;
    }

    destroy() {
        if (this._categoryDragDrop) {
            this._categoryDragDrop.unregister();
        }
    }

    /**
     * Get state watchers.
     *
     * @return {Array} Watcher definitions for item lifecycle and item updates.
     */
    getWatchers() {
        return [
            {watch: 'checklistitems:created', handler: this._handleItemCreated},
            {watch: 'checklistitems.categoryid:updated', handler: this._handleItemCategoryChanged},
            {watch: 'checklistitems:deleted', handler: this._handleItemDeleted},
            {watch: 'checklistitems:updated', handler: this._replaceRenderedItem},
        ];
    }

    /**
     * Initialize category once state is ready.
     */
    stateReady() {
        this.categoryData = this.reactive.state.categories.get(this.categoryId);
        this._initItemsFromDOM();
        this._attachEventListeners();
    }

    /**
     * Update the category header row DOM.
     *
     * @param {Object} categoryData - Updated category data from state
     */
    _updateCategoryRow(categoryData) {
        this.categoryData = categoryData;

        this.element.dataset.categoryName = categoryData.name;
        this.element.dataset.categoryDescription = categoryData.description || '';
        this.element.dataset.categorySortorder = categoryData.sortorder || 0;

        const categoryRow = this.element.querySelector('[data-region="checklist-category-row"]');
        if (categoryRow) {
            const nameCell = categoryRow.querySelector('[data-field="category-name"]');
            if (nameCell) {
                nameCell.textContent = categoryData.name;
            }
        }
    }

    /**
     * Initialize item components from existing DOM elements.
     */
    _initItemsFromDOM() {
        const itemElements = this.element.querySelectorAll(`[data-region="${ITEM_REGION}"]`);

        itemElements.forEach(itemElement => {
            const itemId = parseInt(itemElement.dataset.itemid);
            const itemData = this.reactive.state.checklistitems.get(itemId);

            if (itemData && itemData.categoryid === this.categoryId) {
                const itemComponent = new ResourceSettingsItem({
                    element: itemElement,
                    reactive: this.reactive,
                });
                this.itemComponents.set(itemId, itemComponent);
            }
        });
    }

    /**
     * Re-render all item rows for this category from state.
     */
    async _renderItems() {
        // Preserve the category header row, remove all item rows.
        const categoryHeaderRow = this.element.querySelector('[data-region="checklist-category-row"]');
        this.element.innerHTML = '';
        if (categoryHeaderRow) {
            this.element.appendChild(categoryHeaderRow);
        }
        this.itemComponents.clear();

        const items = Array.from(this.reactive.state.checklistitems.values())
            .filter(item => item.categoryid === this.categoryId)
            .sort((a, b) => a.sortorder - b.sortorder);

        for (const itemData of items) {
            const itemComponent = new ResourceSettingsItem({
                element: this.element,
                reactive: this.reactive,
                itemData: itemData,
            });
            await itemComponent._render();
            this.itemComponents.set(itemData.id, itemComponent);
        }
    }

    // -------------------------------------------------------------------------
    // Item event handlers
    // -------------------------------------------------------------------------

    /**
     * Handle new item created in state.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - New item data
     */
    async _handleItemCreated({element}) {
        if (element.categoryid === this.categoryId) {
            await this._renderItems();
        }
    }

    /**
     * Handle item moved to a different category.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - Updated item data
     */
    async _handleItemCategoryChanged({element}) {
        const hadItem = this.itemComponents.has(element.id);
        const shouldHaveItem = element.categoryid === this.categoryId;
        if (hadItem !== shouldHaveItem) {
            await this._renderItems();
        }
    }

    /**
     * Handle item deleted from state.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - Deleted item data
     */
    _handleItemDeleted({element}) {
        const component = this.itemComponents.get(element.id);
        if (component) {
            if (component.unregister) {
                component.unregister();
            }
            this.itemComponents.delete(element.id);
        }

        const itemElement = this.element.querySelector(
            `[data-region="${ITEM_REGION}"][data-itemid="${element.id}"]`
        );
        if (itemElement) {
            itemElement.remove();
        }
    }

    /**
     * Handle item updated in state — refresh the item row DOM.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - Updated item data
     */
    _replaceRenderedItem({element}) {
        if (element.categoryid !== this.categoryId) {
            return;
        }
        const component = this.itemComponents.get(element.id);
        if (component && component._updateRow) {
            component._updateRow(element);
        }
    }

    // -------------------------------------------------------------------------
    // Button event handlers
    // -------------------------------------------------------------------------

    /**
     * Attach category and item button event listeners.
     */
    _attachEventListeners() {
        const categoryRowEl = this.element.querySelector('[data-region="resource-checklist-category-row"]');
        if (categoryRowEl) {
            if (this._categoryDragDrop) {
                this._categoryDragDrop.unregister();
            }
            const self = this;
            this._categoryDragDrop = new DragDrop({
                element: categoryRowEl,
                getDraggableData() {
                    return {
                        type: 'checklist-category',
                        id: self.categoryId,
                    };
                },
                validateDropData(dropdata) {
                    return dropdata?.type === 'checklist-category';
                },
                showDropZone() {
                    const primary = getComputedStyle(document.documentElement)
                        .getPropertyValue('--primary').trim() || '#0f6cbf';
                    categoryRowEl.style.boxShadow = `0px -5px 0px 0px ${primary} inset`;
                    categoryRowEl.style.transition = 'box-shadow 0.1s ease';
                },
                hideDropZone() {
                    categoryRowEl.style.boxShadow = '';
                    categoryRowEl.style.transition = '';
                },
                drop(dropdata) {
                    dropdata.targetId = self.categoryId;

                    const draggedEl = self.element.parentNode
                        .querySelector(`[data-region="resource-checklist-category"][data-categoryid="${dropdata.id}"]`);
                    if (draggedEl && draggedEl !== self.element) {
                        self.element.parentNode.insertBefore(draggedEl, self.element);
                    }

                    self.reactive.dispatch('reOrderCategories', dropdata);
                },
            });
        }

        const addBtn = this.element.querySelector('[data-action="add-item"]');
        if (addBtn) {
            this.addEventListener(addBtn, 'click', this._handleAddItem.bind(this));
        }

        const editBtn = this.element.querySelector('[data-action="edit-category"]');
        if (editBtn) {
            this.addEventListener(editBtn, 'click', this._handleEditCategory.bind(this));
        }

        const deleteBtn = this.element.querySelector('[data-action="delete-category"]');
        if (deleteBtn) {
            this.addEventListener(deleteBtn, 'click', this._handleDeleteCategory.bind(this));
        }
    }

    /**
     * Handle add item button click.
     *
     * @param {Event} event - Click event
     */
    async _handleAddItem(event) {
        event.preventDefault();

        const modalForm = new ModalForm({
            formClass: ITEM_MODAL_FORM,
            args: {id: 0, categoryid: this.categoryData.id},
            modalConfig: {title: await getString('addchecklistitem', 'mod_bookit')},
            returnFocus: event.currentTarget,
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            const response = e.detail;
            if (response.result === 'success') {
                this.reactive.dispatch('itemsUpdated', {fields: response.data});
            }
        });

        modalForm.show();
    }

    /**
     * Handle edit category button click.
     *
     * @param {Event} event - Click event
     */
    async _handleEditCategory(event) {
        event.preventDefault();

        const modalForm = new ModalForm({
            formClass: CATEGORY_MODAL_FORM,
            args: {id: this.categoryData.id},
            modalConfig: {title: await getString('editcategory', 'mod_bookit')},
            returnFocus: event.currentTarget,
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            const response = e.detail;
            if (response.result === 'success') {
                this.reactive.dispatch('categoriesUpdated', {fields: response.data});
            }
        });

        modalForm.show();
    }

    /**
     * Handle delete category button click.
     *
     * @param {Event} event - Click event
     */
    async _handleDeleteCategory(event) {
        event.preventDefault();

        const confirmed = await Notification.confirm(
            await getString('confirm', 'core'),
            await getString('deletecategory_confirm', 'mod_bookit'),
            await getString('delete', 'core'),
            await getString('cancel', 'core')
        );

        if (confirmed) {
            this.reactive.dispatch('categoriesDeleted', {fields: {id: this.categoryData.id}});
        }
    }
}

export {CATEGORY_REGION};
