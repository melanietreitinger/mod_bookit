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
 * Resource checklist item component.
 *
 * Standalone component handling a single checklist item row.
 * Manages edit modal, state updates, and DOM updates for one item.
 *
 * @module mod_bookit/resource_settings/resource_settings_item
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop} from 'core/reactive';
import Templates from 'core/templates';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';

const ITEM_MODAL_FORM = 'mod_bookit\\local\\form\\resource\\edit_resource_settings_item_form';
const ITEM_TEMPLATE = 'mod_bookit/resource_settings/resource_settings_item_row';
const ITEM_REGION = 'resource-checklist-item-row';

/**
 * Resource checklist item component.
 */
export default class ResourceSettingsItem extends BaseComponent {

    /**
     * Component descriptor for debugging.
     *
     * @return {string} Component name
     */
    static get componentName() {
        return 'mod_bookit/resource_settings/resource_settings_item';
    }

    /**
     * Create component.
     *
     * @param {Object} descriptor - Component descriptor
     */
    create(descriptor) {
        const itemId = descriptor.element.dataset.itemid || descriptor.element.dataset.bookitItemId;
        if (!itemId) {
            window.console.warn('resource_settings_item: missing itemId on element', descriptor.element);
        }
        this._editBtnSelector = itemId ? `#edit-item-${itemId}` : null;
        this.itemData = descriptor.itemData;
    }

    /**
     * Get state watchers.
     *
     * @return {Array} Empty — item updates are handled via _updateRow() called by the category.
     */
    getWatchers() {
        return [];
    }

    /**
     * Attach event listeners once state is ready.
     */
    stateReady() {
        this._attachEventListeners();
    }

    /**
     * Attach event listeners.
     */
    _attachEventListeners() {
        if (!this._editBtnSelector) {
            return;
        }
        const editBtn = this.getElement(this._editBtnSelector);
        if (editBtn) {
            this.addEventListener(editBtn, 'click', this._handleEdit.bind(this));
        }

        // DragDrop — reinit each render since this.element changes.
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
        this.dragdrop = new DragDrop(this);
    }

    /**
     * Handle edit button click.
     *
     * @param {Event} event - Click event
     */
    async _handleEdit(event) {
        event.preventDefault();

        const itemId = event.currentTarget.value || event.currentTarget.dataset.itemid;
        const modalForm = new ModalForm({
            formClass: ITEM_MODAL_FORM,
            args: {id: parseInt(itemId)},
            modalConfig: {title: await getString('editchecklistitem', 'mod_bookit')},
            returnFocus: event.currentTarget,
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            const response = e.detail;

            // Handle processUpdates format (array of updates from server)
            if (Array.isArray(response)) {
                this.reactive.stateManager.processUpdates(response);
                if (response[0]?.action === 'delete') {
                    this.reactive.dispatch('itemDeleted', {id: parseInt(response[0].fields.id)});
                    this.remove();
                }
                return;
            }

            // Handle direct result format
            if (response.result === 'success') {
                if (response.action === 'delete') {
                    this.reactive.dispatch('itemsDeleted', {fields: response.data});
                    this.remove();
                } else {
                    this.reactive.dispatch('itemsUpdated', {fields: response.data});
                }
            }
        });

        modalForm.show();
    }

    /**
     * Update the item row DOM with new data.
     *
     * @param {Object} itemData - Updated item data from state
     */
    _updateRow(itemData) {
        this.itemData = itemData;

        if (this.element) {
            this.element.dataset.itemName = itemData.name || '';
            this.element.dataset.itemDescription = itemData.description || '';
            this.element.dataset.itemSortorder = itemData.sortorder || 0;
        }

        const nameCell = this.element.querySelector('[data-field="item-name"]');
        if (nameCell) {
            nameCell.textContent = itemData.name;
        }

        const descCell = this.element.querySelector('[data-field="item-description"]');
        if (descCell) {
            descCell.textContent = itemData.description || '';
        }

        // Resource-specific: update due date display cell.
        const duedateCell = this.element.querySelector('[data-field="item-duedate"]');
        if (duedateCell) {
            duedateCell.textContent = itemData.duedatedisplay || '-';
        }
    }

    /**
     * Remove the item element from the DOM.
     */
    remove() {
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
    }

    /**
     * Render item from template (for dynamically created items).
     */
    async _render() {
        const html = await Templates.render(ITEM_TEMPLATE, this.itemData);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html.trim();
        const itemElement = tempDiv.firstElementChild;

        if (this.element.tagName === 'TBODY') {
            this.element.appendChild(itemElement);
        } else {
            this.element.parentNode.replaceChild(itemElement, this.element);
        }

        this.element = itemElement;
        this._editBtnSelector = `#edit-item-${this.itemData.id}`;
        this._attachEventListeners();
    }

    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    getDraggableData() {
        return {
            type: 'checklist-item',
            id: parseInt(this.element.dataset.itemid),
            parentId: parseInt(this.element.dataset.itemCategoryid),
        };
    }

    validateDropData(dropdata) {
        return dropdata?.type === 'checklist-item';
    }

    showDropZone() {
        const primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#0f6cbf';
        this.element.style.boxShadow = `0px -5px 0px 0px ${primary} inset`;
        this.element.style.transition = 'box-shadow 0.1s ease';
    }

    hideDropZone() {
        this.element.style.boxShadow = '';
        this.element.style.transition = '';
    }

    drop(dropdata) {
        if (dropdata.parentId !== parseInt(this.element.dataset.itemCategoryid)) {
            return;
        }
        dropdata.targetId = parseInt(this.element.dataset.itemid);

        const draggedEl = this.element.parentNode
            .querySelector(`[data-region="resource-checklist-item-row"][data-itemid="${dropdata.id}"]`);
        if (draggedEl && draggedEl !== this.element) {
            this.element.parentNode.insertBefore(draggedEl, this.element);
        }

        this.reactive.dispatch('reOrderItems', dropdata);
    }
}

export {ITEM_REGION};
