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
 * Resource category reactive component.
 *
 * @module mod_bookit/resource_catalog/resource_category
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent, DragDrop} from 'core/reactive';
import Templates from 'core/templates';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Notification from 'core/notification';
import ResourceItem from './resource_item';
import {SELECTORS} from './resource_reactive';

/**
 * Resource category component.
 */
export default class ResourceCategory extends BaseComponent {
    /**
     * Component descriptor for debugging.
     *
     * @return {string} Component name
     */
    static get componentName() {
        return 'mod_bookit/resource_catalog/resource_category';
    }

    /**
     * Create component.
     *
     * @param {Object} options - Component options
     * @param {HTMLElement} options.element - Parent container element
     * @param {Object} options.categoryData - Category data
     */
    create({element, categoryData}) {
        this.categoryData = categoryData;
        this.categoryElement = null;
        this.itemComponents = new Map();
        this.parentElement = element;
        this._categoryDragDrop = null;
        this._categoryHandleDragDrop = null;

        // Note: _render() must be called explicitly when creating new categories.
        // When initializing from existing DOM, categoryElement is set externally.
    }

    destroy() {
        if (this._categoryDragDrop) {
            this._categoryDragDrop.unregister();
        }
        if (this._categoryHandleDragDrop) {
            this._categoryHandleDragDrop.unregister();
        }
    }

    /**
     * Get state watchers.
     *
     * @return {Array} Array of watcher definitions
     */
    getWatchers() {
        return [
            {watch: `items:created`, handler: this._handleItemCreated},
            {watch: `items.categoryid:updated`, handler: this._handleItemCategoryChanged},
            {watch: `items:deleted`, handler: this._handleItemDeleted},
        ];
    }

    /**
     * Render category (table view: tbody with category row).
     */
    async _render() {
        // Render category row.
        const categoryRowHtml = await Templates.render('mod_bookit/resource_catalog/resource_category_row', this.categoryData);

        // Create tbody element for this category.
        const tbody = document.createElement('tbody');
        tbody.dataset.region = 'resource-category';
        tbody.dataset.categoryid = this.categoryData.id;
        tbody.dataset.categoryName = this.categoryData.name;
        tbody.dataset.categoryDescription = this.categoryData.description || '';
        tbody.dataset.categorySortorder = this.categoryData.sortorder || 0;
        tbody.innerHTML = categoryRowHtml.trim();

        this.categoryElement = tbody;
        this.parentElement.appendChild(this.categoryElement);

        await this._renderItems();
        this._attachEventListeners();
    }

    /**
     * Build Mustache template context for a single resource item row.
     *
     * @param {Object} itemData - Item from reactive state
     * @return {Object} Template context
     */
    _buildItemTemplateContext(itemData) {
        const catalogEl = document.querySelector('[data-totalrooms]');
        const totalrooms = catalogEl ? parseInt(catalogEl.dataset.totalrooms) || 0 : 0;
        const roomids = itemData.roomids || [];
        const isallrooms = totalrooms > 0 && roomids.length === totalrooms;

        return {
            id: itemData.id,
            name: itemData.name,
            description: itemData.description || '',
            categoryid: itemData.categoryid,
            amount: itemData.amount,
            amountirrelevant: itemData.amountirrelevant,
            sortorder: itemData.sortorder,
            active: itemData.active,
            roomids: JSON.stringify(roomids),
            roomnames: isallrooms ? [] : (itemData.roomnames || []),
            isallrooms: isallrooms,
            hasmore: false,
            moreroomscount: 0,
            allroomnames: '',
        };
    }

    /**
     * Render items in this category (table view: append rows to tbody).
     * Used when creating new items dynamically.
     */
    async _renderItems() {
        if (!this.categoryElement) {
            return;
        }

        // Clear existing item rows (keep category row).
        const categoryRow = this.categoryElement.querySelector('[data-region="resource-category-row"]');
        this.categoryElement.innerHTML = '';
        if (categoryRow) {
            this.categoryElement.appendChild(categoryRow);
        }
        this.itemComponents.clear();

        // Get items from state.
        const state = this.reactive.state;
        const items = Array.from(state.items.values())
            .filter(item => item.categoryid === this.categoryData.id)
            .sort((a, b) => a.sortorder - b.sortorder);

        // Render each item as a table row using Templates.
        for (const itemData of items) {
            const context = this._buildItemTemplateContext(itemData);
            const {html, js} = await Templates.renderForPromise(
                'mod_bookit/resource_catalog/resource_item_row',
                context
            );
            await Templates.appendNodeContents(this.categoryElement, html, js);
            const rowEl = this.categoryElement.querySelector(`#resource-item-row-${itemData.id}`);
            if (rowEl) {
                const itemComponent = new ResourceItem({
                    element: rowEl,
                    reactive: this.reactive,
                    selectors: SELECTORS,
                });
                this.itemComponents.set(itemData.id, itemComponent);
            }
        }

        // Restore collapse state after re-rendering items.
        const catId = this.categoryData.id;
        const ctxEl = document.querySelector('[data-contextid]');
        const contextId = ctxEl ? ctxEl.dataset.contextid : '';
        const storageKey = `bookit_cat_${contextId}_collapsed_${catId}`;
        if (localStorage.getItem(storageKey)) {
            const itemRows = this.categoryElement.querySelectorAll('[data-item-categoryid]');
            itemRows.forEach(r => r.classList.add('d-none'));
            const btn = this.categoryElement.querySelector('[data-action="toggle-category"]');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    }

    /**
     * Initialize item components from existing DOM.
     * Called when attaching to pre-rendered HTML.
     */
    _initItemsFromDOM() {
        if (!this.categoryElement) {
            return;
        }

        const state = this.reactive.state;
        const itemRows = this.categoryElement.querySelectorAll('tr[data-itemid]');

        itemRows.forEach(itemRow => {
            const itemId = parseInt(itemRow.dataset.bookitItemId);
            const itemData = state.items.get(itemId);

            if (itemData && itemData.categoryid === this.categoryData.id) {
                const itemComponent = new ResourceItem({
                    element: itemRow,
                    reactive: this.reactive,
                    selectors: SELECTORS,
                });

                this.itemComponents.set(itemData.id, itemComponent);
            }
        });
    }

    /**
     * Handle item created.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - New item data
     */
    async _handleItemCreated({element}) {
        if (element.categoryid === this.categoryData.id) {
            await this._renderItems();
        }
    }

    /**
     * Handle item category changed (item moved between categories).
     *
     * For drag-and-drop operations, drop() already moved the DOM row via insertBefore
     * before this watcher fires. In that case we only update bookkeeping (no re-render).
     * For AJAX edits (category changed via edit form), the DOM has not been touched yet
     * and we fall back to a full _renderItems() call.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - Updated item data
     */
    async _handleItemCategoryChanged({element}) {
        const hadItem = this.itemComponents.has(element.id);
        const shouldHaveItem = element.categoryid === this.categoryData.id;

        if (hadItem === shouldHaveItem) {
            return;
        }

        const rowEl = document.getElementById(`resource-item-row-${element.id}`);

        if (hadItem && !shouldHaveItem) {
            // Item moved OUT of this category.
            const isStillHere = rowEl && this.categoryElement.contains(rowEl);
            if (!isStillHere) {
                // Drag: DOM row already moved out by insertBefore - just clean up bookkeeping.
                const component = this.itemComponents.get(element.id);
                if (component) {
                    component.unregister();
                }
                this.itemComponents.delete(element.id);
            } else {
                // AJAX edit: row still here - full re-render.
                await this._renderItems();
            }
            return;
        }

        // Item moved INTO this category.
        const isAlreadyHere = rowEl && this.categoryElement.contains(rowEl);
        if (isAlreadyHere) {
            // Drag: DOM row already moved in by insertBefore - just register new component.
            const itemComponent = new ResourceItem({
                element: rowEl,
                reactive: this.reactive,
                selectors: SELECTORS,
            });
            this.itemComponents.set(element.id, itemComponent);

            // Apply collapse state if this category is collapsed.
            const catId = this.categoryData.id;
            const ctxEl = document.querySelector('[data-contextid]');
            const contextId = ctxEl ? ctxEl.dataset.contextid : '';
            const storageKey = `bookit_cat_${contextId}_collapsed_${catId}`;
            if (localStorage.getItem(storageKey)) {
                rowEl.classList.add('d-none');
            }
        } else {
            // AJAX edit: full re-render.
            await this._renderItems();
        }
    }

    /**
     * Handle item deleted.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - Deleted item data
     */
    _handleItemDeleted({element}) {
        const component = this.itemComponents.get(element.id);
        if (component) {
            component.remove();
            this.itemComponents.delete(element.id);
        }
    }

    /**
     * Attach event listeners.
     */
    _attachEventListeners() {
        if (!this.categoryElement) {
            return;
        }

        // Setup drop-only DragDrop on the category header row (no getDraggableData = not draggable from row).
        const categoryRowEl = this.categoryElement.querySelector('[data-region="resource-category-row"]');
        if (categoryRowEl) {
            if (this._categoryDragDrop) {
                this._categoryDragDrop.unregister();
            }
            if (this._categoryHandleDragDrop) {
                this._categoryHandleDragDrop.unregister();
            }
            const self = this;
            let dropBefore = true;

            const onDragOver = (e) => {
                const rect = categoryRowEl.getBoundingClientRect();
                dropBefore = e.clientY < rect.top + rect.height / 2;
                // Re-paint indicator only when this row is already an active drop zone.
                // Moodle DragDrop adds 'dragover' class only when validateDropData returns true.
                if (!categoryRowEl.classList.contains('dragover')) {
                    return;
                }
                const primary = getComputedStyle(document.documentElement)
                    .getPropertyValue('--primary').trim() || '#0f6cbf';
                // For inset box-shadow: +5px = top edge, -5px = bottom edge.
                const offset = dropBefore ? '5px' : '-5px';
                categoryRowEl.style.boxShadow = `0px ${offset} 0px 0px ${primary} inset`;
            };
            categoryRowEl.addEventListener('dragover', onDragOver);

            this._categoryDragDrop = new DragDrop({
                element: categoryRowEl,
                reactive: self.reactive,
                validateDropData(dropdata) {
                    return dropdata?.type === 'resource-category' || dropdata?.type === 'resource-item';
                },
                showDropZone(dropdata, event) {
                    // Update dropBefore from the enter event so the initial indicator is correct.
                    if (event) {
                        const rect = categoryRowEl.getBoundingClientRect();
                        dropBefore = event.clientY < rect.top + rect.height / 2;
                    }
                    const primary = getComputedStyle(document.documentElement)
                        .getPropertyValue('--primary').trim() || '#0f6cbf';
                    // For inset box-shadow: +5px = top edge, -5px = bottom edge.
                    const offset = dropBefore ? '5px' : '-5px';
                    categoryRowEl.style.boxShadow = `0px ${offset} 0px 0px ${primary} inset`;
                    categoryRowEl.style.transition = 'box-shadow 0.1s ease';
                },
                hideDropZone() {
                    categoryRowEl.style.boxShadow = '';
                    categoryRowEl.style.transition = '';
                },
                drop(dropdata) {
                    if (dropdata.type === 'resource-item') {
                        // Item dropped on category header — append to end of this category.
                        const draggedEl = document.getElementById(`resource-item-row-${dropdata.id}`);
                        if (draggedEl) {
                            self.categoryElement.appendChild(draggedEl);
                            draggedEl.dataset.itemCategoryid = self.categoryData.id;
                        }
                        dropdata.targetCategoryId = self.categoryData.id;
                        dropdata.targetId = null;
                        self.reactive.dispatch('reOrderItems', dropdata);
                        return;
                    }

                    // Category reorder: insert before or after based on cursor position.
                    dropdata.targetId = self.categoryData.id;
                    dropdata.dropBefore = dropBefore;
                    const draggedEl = self.categoryElement.parentNode
                        .querySelector(`[data-region="resource-category"][data-categoryid="${dropdata.id}"]`);
                    if (draggedEl && draggedEl !== self.categoryElement) {
                        if (dropBefore) {
                            self.categoryElement.parentNode.insertBefore(draggedEl, self.categoryElement);
                        } else {
                            self.categoryElement.parentNode.insertBefore(
                                draggedEl, self.categoryElement.nextElementSibling
                            );
                        }
                    }
                    self.reactive.dispatch('reOrderCategories', dropdata);
                },
            });

            // Drag-only DragDrop on the drag handle button (restrict drag to handle, masterchecklist pattern).
            const handleBtn = categoryRowEl.querySelector('[data-action="drag-handle"]');
            if (handleBtn) {
                this._categoryHandleDragDrop = new DragDrop({
                    element: handleBtn,
                    reactive: self.reactive,
                    fullregion: categoryRowEl,
                    relativeDrag: true,
                    getDraggableData() {
                        return {
                            type: 'resource-category',
                            id: self.categoryData.id,
                        };
                    },
                });
            }

            // Register thead as first-position drop zone (only once per table).
            const tableEl = self.categoryElement.parentNode;
            const theadEl = tableEl ? tableEl.querySelector('thead') : null;
            if (theadEl && !theadEl.dataset.bookitDdRegistered) {
                theadEl.dataset.bookitDdRegistered = '1';
                new DragDrop({
                    element: theadEl,
                    reactive: self.reactive,
                    validateDropData(dropdata) {
                        return dropdata?.type === 'resource-category';
                    },
                    showDropZone() {
                        const primary = getComputedStyle(document.documentElement)
                            .getPropertyValue('--primary').trim() || '#0f6cbf';
                        theadEl.style.boxShadow = `0px 5px 0px 0px ${primary} inset`;
                        theadEl.style.transition = 'box-shadow 0.1s ease';
                    },
                    hideDropZone() {
                        theadEl.style.boxShadow = '';
                        theadEl.style.transition = '';
                    },
                    drop(dropdata) {
                        const firstTbody = tableEl.querySelector('[data-region="resource-category"]');
                        if (!firstTbody) {
                            return;
                        }
                        const firstCatId = parseInt(firstTbody.dataset.categoryid);
                        if (firstCatId === dropdata.id) {
                            return;
                        }
                        const draggedEl = tableEl.querySelector(
                            `[data-region="resource-category"][data-categoryid="${dropdata.id}"]`
                        );
                        if (draggedEl) {
                            firstTbody.parentNode.insertBefore(draggedEl, firstTbody);
                        }
                        self.reactive.dispatch('reOrderCategories', {
                            id: dropdata.id,
                            targetId: firstCatId,
                            dropBefore: true,
                        });
                    },
                });
            }
        }

        // Add Item.
        const addBtn = this.categoryElement.querySelector('[data-action="add-item"]');
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this._handleAddItem();
            });
        }

        // Edit Category.
        const editBtn = this.categoryElement.querySelector('[data-action="edit-category"]');
        if (editBtn) {
            editBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this._handleEdit();
            });
        }
    }

    /**
     * Handle add item.
     */
    async _handleAddItem() {
        const modalForm = new ModalForm({
            formClass: 'mod_bookit\\local\\form\\resource\\edit_resource_form',
            args: {
                categoryid: this.categoryData.id,
            },
            modalConfig: {
                title: await getString('resources:add_resource', 'mod_bookit'),
            },
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            this.reactive.dispatch('createItem', e.detail);
        });

        modalForm.show();
    }

    /**
     * Handle edit category.
     */
    async _handleEdit() {
        const modalForm = new ModalForm({
            formClass: 'mod_bookit\\local\\form\\resource\\edit_resource_category_form',
            moduleName: 'mod_bookit/modal_delete_save_cancel',
            args: {
                id: this.categoryData.id,
            },
            modalConfig: {
                title: await getString('resources:edit_category', 'mod_bookit'),
            },
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
            this.reactive.stateManager.processUpdates(e.detail);
        });

        modalForm.addEventListener(modalForm.events.LOADED, () => {
            const deleteButton = modalForm.modal.getRoot().find('button[data-action="delete"]');

            deleteButton.on('click', async(e) => {
                e.preventDefault();

                // Check if category has resources.
                const state = this.reactive.state;
                const resourceCount = Array.from(state.items.values())
                    .filter(item => item.categoryid === this.categoryData.id).length;

                if (resourceCount > 0) {
                    // Category has resources - show informative message.
                    const errorTitle = await getString('error', 'core');
                    const errorMessage = await getString('resources:category_has_resources', 'mod_bookit');

                    Notification.alert(
                        errorTitle,
                        errorMessage
                    );
                    return;
                }

                // No resources - proceed with delete confirmation.
                const confirmTitle = await getString('confirm', 'core');
                const confirmMessage = await getString('areyousure', 'core');
                const deleteText = await getString('delete', 'core');

                Notification.deleteCancel(
                    confirmTitle,
                    confirmMessage,
                    deleteText,
                    () => {
                        modalForm.getFormNode().querySelector('input[name="action"]').value = 'delete';
                        modalForm.submitFormAjax();
                    }
                );
            });
        });

        modalForm.show();
    }

    /**
     * Update with new data (re-render category row).
     *
     * @param {Object} newData - Updated category data
     */
    update(newData) {
        this.categoryData = newData;
        // Re-render the category row.
        const categoryRow = this.categoryElement.querySelector('[data-region="resource-category-row"]');
        if (categoryRow) {
            Templates.render('mod_bookit/resource_catalog/resource_category_row', newData)
                .then(html => {
                    const wrapper = document.createElement('tbody');
                    wrapper.innerHTML = html.trim();
                    const newRow = wrapper.firstChild;
                    categoryRow.parentNode.replaceChild(newRow, categoryRow);
                    this._attachEventListeners();
                    return true;
                })
                .catch(error => {
                    window.console.error('Error updating category row:', error);
                });
        }
    }

    /**
     * Remove from DOM.
     */
    remove() {
        this.itemComponents.forEach(component => component.remove());
        this.itemComponents.clear();

        if (this.categoryElement && this.categoryElement.parentNode) {
            this.categoryElement.parentNode.removeChild(this.categoryElement);
        }
    }
}
