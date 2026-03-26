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
 * Resource checklist container component.
 *
 * Standalone top-level component for the resource checklist.
 * Manages the reactive store, category components, and toolbar buttons
 * (add category, add item). Initializes state from DOM data attributes.
 *
 * @module mod_bookit/resource_settings/resource_settings_container
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import ResourceSettingsCategory from './resource_settings_category';
import {initChecklistReactive} from './resource_settings_reactive';

const CATEGORY_REGION = 'resource-checklist-category';
const ITEM_REGION = 'resource-checklist-item-row';
const CATEGORY_MODAL_FORM = 'mod_bookit\\form\\edit_category_form';
const ITEM_MODAL_FORM = 'mod_bookit\\local\\form\\resource\\edit_resource_settings_item_form';

/**
 * Resource checklist container component.
 */
export default class ResourceSettingsContainer extends BaseComponent {

    /**
     * Component descriptor for debugging.
     *
     * @return {string} Component name
     */
    static get componentName() {
        return 'mod_bookit/resource_settings/resource_settings_container';
    }

    /**
     * Initialize the resource checklist.
     *
     * Parses initial data from DOM, creates the reactive store, and registers
     * the container component. State is set AFTER the component is registered
     * so stateReady() fires correctly.
     *
     * @param {string} target - CSS selector for the container element
     * @return {ResourceSettingsContainer|null} Component instance or null
     */
    static init(target) {
        const element = document.querySelector(target);
        if (!element) {
            return null;
        }

        const initialData = ResourceSettingsContainer._parseInitialState(element);

        const reactive = initChecklistReactive();

        // Register component BEFORE setting initial state so stateReady() fires.
        const instance = new ResourceSettingsContainer({
            element,
            reactive,
        });

        reactive.setInitialState({
            categories: initialData.categories,
            checklistitems: initialData.items,
        });

        return instance;
    }

    /**
     * Create component — called by BaseComponent constructor.
     */
    create() {
        this.categoryComponents = new Map();
    }

    /**
     * Get state watchers.
     *
     * @return {Array} Watcher definitions for category and item lifecycle.
     */
    getWatchers() {
        return [
            {watch: 'categories:created', handler: this._handleCategoryCreated},
            {watch: 'categories.name:updated', handler: this._handleCategoryUpdated},
            {watch: 'categories.description:updated', handler: this._handleCategoryUpdated},
            {watch: 'categories:deleted', handler: this._handleCategoryDeleted},
            {watch: 'checklistitems:created', handler: this._handleItemCreated},
            {watch: 'checklistitems.name:updated', handler: this._replaceRenderedItem},
            {watch: 'checklistitems.description:updated', handler: this._replaceRenderedItem},
        ];
    }

    /**
     * Initialize once state is ready.
     *
     * Creates category sub-components for all categories present in the DOM.
     */
    stateReady() {
        const spinner = document.getElementById('mod-bookit-resource-settings-spinner');
        const content = document.getElementById('mod-bookit-resource-settings-content');
        if (spinner) {
            spinner.classList.add('d-none');
        }
        if (content) {
            content.classList.remove('d-none');
        }
        this._initializeCategoryComponents();
        this._attachEventListeners();
    }

    // -------------------------------------------------------------------------
    // Category event handlers
    // -------------------------------------------------------------------------

    /**
     * Handle category added to state.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - New category data
     */
    async _handleCategoryCreated({element}) {
        await this._renderCategory(element);
    }

    /**
     * Handle category name or description updated in state.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - Updated category data
     */
    _handleCategoryUpdated({element}) {
        const component = this.categoryComponents.get(element.id);
        if (component) {
            component._updateCategoryRow(element);
        }
    }

    /**
     * Handle category deleted from state.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - Deleted category data
     */
    _handleCategoryDeleted({element}) {
        const component = this.categoryComponents.get(element.id);
        if (component) {
            component.unregister();
            this.categoryComponents.delete(element.id);
        }

        const categoryElement = this.getElement(
            `[data-region="${CATEGORY_REGION}"][data-categoryid="${element.id}"]`
        );
        if (categoryElement) {
            categoryElement.remove();
        }
    }

    // -------------------------------------------------------------------------
    // Item event handlers (delegated to category components)
    // -------------------------------------------------------------------------

    /**
     * Handle item created — delegate to the owning category component.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - New item data
     */
    _handleItemCreated({element}) {
        const component = this.categoryComponents.get(element.categoryid);
        if (component) {
            component._handleItemCreated({element});
        }
    }

    /**
     * Handle item updated — delegate to the owning category component.
     *
     * @param {Object} args - Watcher args
     * @param {Object} args.element - Updated item data
     */
    _replaceRenderedItem({element}) {
        const component = this.categoryComponents.get(element.categoryid);
        if (component) {
            component._replaceRenderedItem({element});
        }
    }

    // -------------------------------------------------------------------------
    // Toolbar button handlers
    // -------------------------------------------------------------------------

    /**
     * Attach toolbar button listeners.
     */
    _attachEventListeners() {
        const addCategoryBtn = this.getElement('#add-category-btn');
        if (addCategoryBtn) {
            this.addEventListener(addCategoryBtn, 'click', this._handleAddCategoryClick.bind(this));
        }

        const addItemBtn = this.getElement('#add-checklist-item-btn');
        if (addItemBtn) {
            this.addEventListener(addItemBtn, 'click', this._handleAddItemClick.bind(this));
        }
    }

    /**
     * Handle add category button click.
     *
     * @param {Event} event - Click event
     */
    async _handleAddCategoryClick(event) {
        event.preventDefault();

        const modalForm = new ModalForm({
            formClass: CATEGORY_MODAL_FORM,
            args: {id: 0},
            modalConfig: {title: await getString('addcategory', 'mod_bookit')},
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
     * Handle add checklist item button click.
     *
     * @param {Event} event - Click event
     */
    async _handleAddItemClick(event) {
        event.preventDefault();

        const modalForm = new ModalForm({
            formClass: ITEM_MODAL_FORM,
            args: {id: 0},
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

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Create ResourceSettingsCategory components for all categories in the DOM.
     */
    _initializeCategoryComponents() {
        this.reactive.state.categories.forEach((category, categoryId) => {
            const categoryElement = this.getElement(
                `[data-region="${CATEGORY_REGION}"][data-categoryid="${categoryId}"]`
            );
            if (categoryElement) {
                const instance = new ResourceSettingsCategory({
                    element: categoryElement,
                    reactive: this.reactive,
                });
                this.categoryComponents.set(categoryId, instance);
            }
        });
    }

    /**
     * Render a newly created category into the table.
     *
     * @param {Object} categoryData - New category data from state
     */
    async _renderCategory(categoryData) {
        const tableView = this.getElement('#resource-checklist-table-view');
        if (!tableView) {
            return;
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = `
            <tbody data-region="${CATEGORY_REGION}" data-categoryid="${categoryData.id}">
                <tr data-region="checklist-category-row">
                    <td colspan="100">${categoryData.name}</td>
                </tr>
            </tbody>
        `;
        const categoryElement = tempDiv.firstElementChild;
        tableView.appendChild(categoryElement);

        const instance = new ResourceSettingsCategory({
            element: categoryElement,
            reactive: this.reactive,
        });
        this.categoryComponents.set(categoryData.id, instance);
    }

    /**
     * Parse categories and checklist items from DOM data attributes.
     *
     * Each category element carries its data in dataset attributes.
     * Each item element within a category carries item data in dataset attributes.
     *
     * @param {HTMLElement} containerElement - The checklist container element
     * @return {{categories: Array, items: Array}} Initial state data
     */
    static _parseInitialState(containerElement) {
        const categories = [];
        const items = [];

        const categoryElements = containerElement.querySelectorAll(
            `[data-region="${CATEGORY_REGION}"]`
        );

        categoryElements.forEach(categoryEl => {
            const categoryId = parseInt(categoryEl.dataset.categoryid);
            if (!categoryId) {
                return;
            }

            categories.push({
                id: categoryId,
                name: categoryEl.dataset.categoryName || '',
                description: categoryEl.dataset.categoryDescription || '',
                sortorder: parseInt(categoryEl.dataset.categorySortorder) || 0,
            });

            const itemElements = categoryEl.querySelectorAll(
                `[data-region="${ITEM_REGION}"]`
            );

            itemElements.forEach(itemEl => {
                const itemId = parseInt(itemEl.dataset.itemid);
                if (!itemId) {
                    return;
                }

                items.push({
                    id: itemId,
                    name: itemEl.dataset.itemName || '',
                    description: itemEl.dataset.itemDescription || '',
                    categoryid: categoryId,
                    sortorder: parseInt(itemEl.dataset.itemSortorder) || 0,
                    resourceid: parseInt(itemEl.dataset.itemResourceid) || 0,
                    duedate: itemEl.dataset.itemDuedate ? parseInt(itemEl.dataset.itemDuedate) : null,
                    duedatetype: itemEl.dataset.itemDuedatetype || null,
                    duedatedisplay: itemEl.dataset.itemDuedatedisplay || null,
                    active: itemEl.dataset.itemActive === '1',
                    beforedueid: itemEl.dataset.itemBeforedueid ? parseInt(itemEl.dataset.itemBeforedueid) : null,
                    whendueid: itemEl.dataset.itemWhendueid ? parseInt(itemEl.dataset.itemWhendueid) : null,
                    overdueid: itemEl.dataset.itemOverdueid ? parseInt(itemEl.dataset.itemOverdueid) : null,
                    whendoneid: itemEl.dataset.itemWhendoneid ? parseInt(itemEl.dataset.itemWhendoneid) : null,
                });
            });
        });

        return {categories, items};
    }
}
