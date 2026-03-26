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
 * Resource catalog reactive component.
 *
 * @module mod_bookit/resource_catalog/resource_catalog
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';
import {getResourceReactive, initResourceReactive} from './resource_reactive';
import ModalForm from 'core_form/modalform';
import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import ResourceCategory from './resource_category';

/**
 * Resource catalog component.
 */
export default class extends BaseComponent {
    /**
     * Static init method.
     *
     * @param {string} target - CSS selector for container element
     * @return {ResourceCatalog} Component instance
     */
    static init(target) {
        const element = document.querySelector(target);
        if (!element) {
            return null;
        }

        const contextId = parseInt(element.dataset.contextid);
        const {categoriesArray, itemsArray} = this._parseDomState(element);

        initResourceReactive({
            categories: categoriesArray,
            items: itemsArray,
        });

        return new this({
            element: element,
            reactive: getResourceReactive(),
            selectors: {contextId},
        });
    }

    /**
     * Parse categories and items from the server-rendered DOM.
     *
     * @param {HTMLElement} element - Container element
     * @return {{categoriesArray: Array, itemsArray: Array}}
     */
    static _parseDomState(element) {
        const categoriesArray = [];
        const itemsArray = [];

        const categoryElements = element.querySelectorAll('[data-region="resource-category"]');
        categoryElements.forEach(categoryEl => {
            categoriesArray.push(this._parseCategoryElement(categoryEl));

            const itemElements = categoryEl.querySelectorAll('[data-region="resource-item-row"]');
            itemElements.forEach(itemEl => {
                itemsArray.push(this._parseItemElement(itemEl));
            });
        });

        return {categoriesArray, itemsArray};
    }

    /**
     * Parse a single category element into a data object.
     *
     * @param {HTMLElement} categoryEl - Category DOM element
     * @return {Object} Category data object
     */
    static _parseCategoryElement(categoryEl) {
        const categoryRow = categoryEl.querySelector('[data-region="resource-category-row"]');
        return {
            id: parseInt(categoryEl.dataset.categoryid),
            name: categoryEl.dataset.categoryName || categoryRow?.textContent.trim(),
            description: categoryEl.dataset.categoryDescription || '',
            sortorder: parseInt(categoryEl.dataset.categorySortorder) || 0,
        };
    }

    /**
     * Parse a single item element into a data object.
     *
     * Parses roomids from the JSON string stored in the data attribute,
     * and roomnames from the server-rendered badge elements so that
     * cross-category re-renders can reproduce the badges from state.
     *
     * @param {HTMLElement} itemEl - Item DOM element
     * @return {Object} Item data object
     */
    static _parseItemElement(itemEl) {
        let roomids = [];
        if (itemEl.dataset.itemRoomids) {
            try {
                roomids = JSON.parse(itemEl.dataset.itemRoomids);
                if (!Array.isArray(roomids)) {
                    roomids = [];
                }
            } catch (e) {
                window.console.warn('Failed to parse roomids:', e);
                roomids = [];
            }
        }

        const roomElements = itemEl.querySelectorAll('[data-bookit-resource-tabledata-is-room-element]');
        const roomnames = Array.from(roomElements).map(el => ({
            roomid: parseInt(el.dataset.bookitResourceTabledataRoomId),
            roomname: el.dataset.bookitResourceRoomname || '',
            eventcolor: el.dataset.bookitResourceTabledataRoomColor || '',
            textclass: el.dataset.bookitResourceTabledataRoomTextclass || '',
            shortname: el.textContent.trim(),
            roomurl: el.href || '',
        }));

        return {
            id: parseInt(itemEl.dataset.itemid),
            name: itemEl.dataset.itemName || '',
            description: itemEl.dataset.itemDescription || '',
            categoryid: parseInt(itemEl.dataset.itemCategoryid),
            amount: parseInt(itemEl.dataset.itemAmount) || 0,
            amountirrelevant: itemEl.dataset.itemAmountirrelevant === '1',
            active: itemEl.dataset.itemActive === '1',
            sortorder: parseInt(itemEl.dataset.itemSortorder) || 0,
            roomids: roomids,
            roomnames: roomnames,
        };
    }

    /**
     * Component descriptor for debugging.
     *
     * @return {string} Component name
     */
    static get componentName() {
        return 'mod_bookit/resource_catalog/resource_catalog';
    }

    /**
     * Create component.
     *
     * Called by BaseComponent constructor.
     */
    create() {
        this.selectors.addCategoryBtn = '#add-category-btn';
        this.selectors.addResourceBtn = '#add-resource-btn';
        this.selectors.noCategoriesMsg = '#resource-no-categories-msg';
        this.selectors.tableView = '#mod-bookit-resource-table-view';
        this.selectors.roomFilter = '#id_roomfilter_filter_section';
        this.categoryComponents = new Map();
        this.selectedRooms = new Set();
        this.totalrooms = parseInt(this.element.dataset.totalrooms) || 0;

        // Cache localized strings for active/inactive.
        this.strings = {};
        getString('active', 'core').then(str => {
            this.strings.active = str;
            return str;
        }).catch(() => {
            this.strings.active = 'Active';
        });
        getString('inactive', 'core').then(str => {
            this.strings.inactive = str;
            return str;
        }).catch(() => {
            this.strings.inactive = 'Inactive';
        });
    }

    /**
     * Initial state ready.
     *
     * Called when reactive state is ready.
     */
    stateReady() {
        // Hide spinner and reveal content.
        const spinner = document.getElementById('mod-bookit-resource-spinner');
        const content = document.getElementById('mod-bookit-resource-content');
        if (spinner) {
            spinner.classList.add('d-none');
        }
        if (content) {
            content.classList.remove('d-none');
        }

        this._initializeCategoryComponents();
        this._attachEventListeners();
        this._initializeRoomFilter();
        this._initializeAllRoomBadges();
        this._updateAddResourceButtonState();
    }

    /**
     * Get state watchers.
     *
     * @return {Array} Array of watcher definitions
     */
    getWatchers() {
        return [
            {watch: `categories:created`, handler: this._handleCategoryCreated},
            {watch: `categories.name:updated`, handler: this._handleCategoryUpdated},
            {watch: `categories.description:updated`, handler: this._handleCategoryUpdated},
            {watch: `categories:deleted`, handler: this._handleCategoryDeleted},
            {watch: `items:created`, handler: this._handleItemCreated},
            {watch: `items.active:updated`, handler: this._handleActiveToggle},
            {watch: `items.name:updated`, handler: this._replaceRenderedItem},
            {watch: `items.description:updated`, handler: this._replaceRenderedItem},
            {watch: `items.amount:updated`, handler: this._replaceRenderedItem},
            {watch: `items.amountirrelevant:updated`, handler: this._replaceRenderedItem},
            {watch: `items.roomids:updated`, handler: this._handleRoomidsUpdated},
            {watch: `items.roomnames:updated`, handler: this._handleRoomnamesUpdated},
        ];
    }

    /**
     * Update add-resource button enabled/disabled state based on whether categories exist.
     * Also toggles the "no categories yet" message visibility.
     */
    _updateAddResourceButtonState() {
        const btn = document.querySelector(this.selectors.addResourceBtn);
        if (btn) {
            btn.disabled = this.reactive.state.categories.size === 0;
        }
        if (this.reactive.state.categories.size > 0) {
            this._hideNoCategoriesMessage();
        } else {
            this._showNoCategoriesMessage();
        }
    }

    /**
     * Handle category created.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - New category data
     */
    async _handleCategoryCreated({element}) {
        await this._renderCategory(element);
        this._updateAddResourceButtonState();
    }

    /**
     * Handle category updated.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - Updated category data
     */
    _handleCategoryUpdated({element}) {
        const component = this.categoryComponents.get(element.id);
        if (component) {
            component.update(element);
        }
    }

    /**
     * Handle category deleted.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - Deleted category data
     */
    _handleCategoryDeleted({element}) {
        const component = this.categoryComponents.get(element.id);
        if (component) {
            component.remove();
            this.categoryComponents.delete(element.id);
        }
        this._updateAddResourceButtonState();
    }

    /**
     * Handle item created.
     *
     * Item creation is handled by the category component.
     */
    _handleItemCreated() {
        // Category component handles this via its watchers.
    }

    /**
     * Handle active state toggle.
     *
     * @param {Object} args - Event args
     * @param {Object} args.element - Updated item data
     */
    _handleActiveToggle({element}) {
        const checkbox = document.querySelector(`#resource-active-${element.id}`);
        if (!checkbox) {
            window.console.warn(`Toggle checkbox not found for item ${element.id}`);
            return;
        }
        checkbox.checked = element.active;

        const label = document.querySelector(`label[for="resource-active-${element.id}"]`);
        if (label) {
            label.textContent = element.active ?
                (this.strings.active || 'Active') :
                (this.strings.inactive || 'Inactive');
        }

        const row = document.querySelector(`#resource-item-row-${element.id}`);
        if (row) {
            row.classList.toggle('opacity-50', !element.active);
        }
    }

    /**
     * Replace rendered item field (follows masterchecklist pattern).
     *
     * @param {Object} event - Event object
     */
    async _replaceRenderedItem(event) {
        const actionParts = event.action.split('.');
        const fieldPart = actionParts[1].split(':')[0];
        const item = this.reactive.state.items.get(event.element.id);

        if (fieldPart === 'amount' || fieldPart === 'amountirrelevant') {
            const amountCell = document.querySelector(`td[data-bookit-resource-tabledata-amount-id="${item.id}"]`);
            if (!amountCell) {
                return;
            }
            if (item.amountirrelevant) {
                amountCell.innerHTML = '<span class="badge badge-secondary">Unlimited</span>';
            } else {
                amountCell.innerHTML = `<span class="badge badge-info">${item.amount}x</span>`;
            }
        } else if (fieldPart === 'name') {
            const nameSpan = document.querySelector(`span[data-bookit-resource-tabledata-name-id="${item.id}"]`);
            if (!nameSpan) {
                return;
            }
            nameSpan.textContent = item.name;
        } else if (fieldPart === 'description') {
            await this._updateDescriptionField(item);
        }
    }

    /**
     * Update description field in the DOM.
     *
     * @param {Object} item - Item data
     */
    async _updateDescriptionField(item) {
        const descSpan = document.querySelector(`small[data-bookit-resource-tabledata-description-id="${item.id}"]`);
        if (descSpan) {
            descSpan.innerHTML = item.description || '';
            return;
        }

        // Description was added but <small> element doesn't exist - need to re-render row.
        if (!item.description) {
            return;
        }

        const row = document.querySelector(`#resource-item-row-${item.id}`);
        if (!row) {
            return;
        }

        // Find category component and trigger re-render.
        const categoryComponent = Array.from(this.categoryComponents.values())
            .find(cat => cat.categoryData.id === item.categoryid);
        if (!categoryComponent) {
            return;
        }

        try {
            await categoryComponent._renderItems();
        } catch (error) {
            window.console.error('Failed to re-render items:', error);
        }
    }

    /**
     * Handle roomnames updated.
     *
     * @param {Object} event - Event object
     */
    _handleRoomnamesUpdated(event) {
        const item = this.reactive.state.items.get(event.element.id);
        if (!item) {
            return;
        }

        const roomsCell = document.querySelector(`td[data-bookit-resource-tabledata-roomids-id="${item.id}"]`);
        if (!roomsCell) {
            return;
        }

        this._renderRoomBadgesForItem(item, roomsCell);
    }

    /**
     * Render room badges for a single item into its table cell.
     * Trims badges that overflow the container width and shows a +N overflow badge.
     *
     * @param {Object} item - Reactive state item
     * @param {HTMLElement} roomsCell - The td element to render into
     */
    _renderRoomBadgesForItem(item, roomsCell) {
        roomsCell.style.maxWidth = '200px';
        roomsCell.style.overflow = 'hidden';
        roomsCell.innerHTML = '<div class="d-flex flex-nowrap align-items-center"></div>';
        const container = roomsCell.querySelector('div');

        if (!item.roomnames || item.roomnames.length === 0) {
            container.innerHTML = '<span class="text-muted">-</span>';
            return;
        }

        // Show "All Rooms" badge when all rooms are assigned.
        const isAllRooms = this.totalrooms > 0 && item.roomids && item.roomids.length === this.totalrooms;
        if (isAllRooms) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-secondary';
            badge.textContent = 'All rooms';
            getString('allrooms', 'mod_bookit').then(str => {
                badge.textContent = str;
                return str;
            }).catch(() => undefined);
            container.appendChild(badge);
            return;
        }

        item.roomnames.forEach(room => {
            const badge = document.createElement('a');
            badge.className = `badge ${room.textclass} mr-1`;
            badge.style.opacity = '0.8';
            badge.style.backgroundColor = room.eventcolor;
            badge.style.textDecoration = 'none';
            badge.href = room.roomurl || '#';
            badge.dataset.bookitResourceTabledataRoomColor = room.eventcolor;
            badge.dataset.bookitResourceTabledataRoomTextclass = room.textclass;
            badge.dataset.bookitResourceTabledataRoomId = room.roomid;
            badge.dataset.bookitResourceRoomname = room.roomname;
            badge.dataset.bookitResourceTabledataIsRoomElement = '';
            badge.setAttribute('data-toggle', 'tooltip');
            badge.title = room.roomname;
            badge.textContent = room.shortname || room.roomname;
            container.appendChild(badge);
        });

        // Compare against the TD's right edge (not the inner div which grows unconstrained).
        const cellRight = roomsCell.getBoundingClientRect().right;
        const allBadges = Array.from(container.querySelectorAll('a[data-bookit-resource-roomname]'));
        let firstOverflowIdx = -1;

        for (let i = 0; i < allBadges.length; i++) {
            if (allBadges[i].getBoundingClientRect().right > cellRight) {
                firstOverflowIdx = i;
                break;
            }
        }

        if (firstOverflowIdx >= 0) {
            const overflowCount = allBadges.length - firstOverflowIdx;
            for (let i = firstOverflowIdx; i < allBadges.length; i++) {
                container.removeChild(allBadges[i]);
            }
            const allNames = item.roomnames.map(r => r.roomname).join(', ');
            const overflow = document.createElement('span');
            overflow.className = 'badge badge-light text-muted mr-1';
            overflow.setAttribute('data-toggle', 'tooltip');
            overflow.title = allNames;
            overflow.textContent = `+${overflowCount}`;
            container.appendChild(overflow);
        }
    }

    /**
     * Initialize room badges for all items on initial state load.
     * Uses server-rendered DOM badges — does not clear or re-render from state.
     */
    _initializeAllRoomBadges() {
        const roomCells = document.querySelectorAll('td[data-bookit-resource-tabledata-roomids-id]');
        roomCells.forEach(roomsCell => {
            // Cap the column width so badges overflow instead of expanding the table.
            roomsCell.style.maxWidth = '200px';
            roomsCell.style.overflow = 'hidden';

            const container = roomsCell.querySelector('div.d-flex');
            if (!container) {
                return;
            }

            const badges = Array.from(
                container.querySelectorAll('a[data-bookit-resource-tabledata-is-room-element]')
            );
            if (badges.length === 0) {
                return;
            }

            // Compare against the TD's right edge (inner div grows unconstrained with flex-nowrap).
            const cellRight = roomsCell.getBoundingClientRect().right;
            if (cellRight === 0) {
                return;
            }

            let firstOverflowIdx = -1;
            for (let i = 0; i < badges.length; i++) {
                if (badges[i].getBoundingClientRect().right > cellRight) {
                    firstOverflowIdx = i;
                    break;
                }
            }

            if (firstOverflowIdx >= 0) {
                // Use full room name from title attribute for tooltip (shortname shown in badge text).
                const allNames = badges.map(b => (b.title || b.textContent.trim())).join(', ');
                const overflowCount = badges.length - firstOverflowIdx;
                for (let i = firstOverflowIdx; i < badges.length; i++) {
                    badges[i].style.display = 'none';
                }
                const overflow = document.createElement('span');
                overflow.className = 'badge badge-light text-muted mr-1';
                overflow.setAttribute('data-toggle', 'tooltip');
                overflow.title = allNames;
                overflow.textContent = `+${overflowCount}`;
                container.appendChild(overflow);
            }
        });
    }

    /**
     * Handle roomids updated.
     *
     * @param {Object} event - Event object
     */
    _handleRoomidsUpdated(event) {
        const item = this.reactive.state.items.get(event.element.id);
        if (!item) {
            return;
        }

        const row = document.querySelector(`tr[data-bookit-item-id="${item.id}"]`);
        if (!row) {
            return;
        }

        // Update data-item-roomids attribute with JSON string.
        row.dataset.itemRoomids = JSON.stringify(item.roomids || []);
        // Also update data-rooms to keep filter in sync.
        row.dataset.rooms = JSON.stringify(item.roomids || []);

        // Re-render room badges.
        const roomsCell = document.querySelector(`td[data-bookit-resource-tabledata-roomids-id="${item.id}"]`);
        if (roomsCell) {
            this._renderRoomBadgesForItem(item, roomsCell);
        }
    }

    /**
     * Attach event listeners.
     */
    _attachEventListeners() {
        const addBtn = document.querySelector(this.selectors.addCategoryBtn);
        if (addBtn) {
            addBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this._handleAddCategory();
            });
        }

        const addResourceBtn = document.querySelector(this.selectors.addResourceBtn);
        if (addResourceBtn) {
            addResourceBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this._handleAddResource();
            });
        }

        // Toggle active switches (event delegation).
        const tableView = document.querySelector(this.selectors.tableView);
        if (tableView) {
            tableView.addEventListener('change', async(e) => {
                const toggle = e.target.closest('[data-action="toggle-active"]');
                if (toggle) {
                    await this._handleToggleActive(toggle);
                }
            });

            // Collapse/expand category rows (event delegation).
            tableView.addEventListener('click', (e) => {
                const toggleBtn = e.target.closest('[data-action="toggle-category"]');
                if (toggleBtn) {
                    this._handleToggleCategory(toggleBtn);
                }
            });
        }
    }

    /**
     * Handle add category action.
     */
    async _handleAddCategory() {
        const modalForm = new ModalForm({
            formClass: 'mod_bookit\\local\\form\\resource\\edit_resource_category_form',
            args: {
                contextid: this.selectors.contextId,
            },
            modalConfig: {
                title: await getString('resources:add_category', 'mod_bookit'),
            },
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (response) => {
            this.reactive.stateManager.processUpdates(response.detail);
        });

        modalForm.show();
    }

    /**
     * Handle add resource button click.
     */
    async _handleAddResource() {
        const modalForm = new ModalForm({
            formClass: 'mod_bookit\\local\\form\\resource\\edit_resource_form',
            args: {
                contextid: this.selectors.contextId,
            },
            modalConfig: {
                title: await getString('resources:add_resource', 'mod_bookit'),
            },
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (response) => {
            this.reactive.stateManager.processUpdates(response.detail);
        });

        modalForm.show();
    }

    /**
     * Handle toggle active switch.
     *
     * @param {HTMLElement} checkbox - Toggle checkbox element
     */
    async _handleToggleActive(checkbox) {
        const itemId = parseInt(checkbox.dataset.itemId);
        const state = this.reactive.state;
        const item = state.items.get(itemId);

        if (!item) {
            return;
        }

        // Get the new active state from the checkbox.
        const newActiveState = checkbox.checked;

        // Build form data object (excluding roomids array).
        const formData = {
            id: itemId,
            name: item.name,
            description: item.description || '',
            categoryid: item.categoryid,
            amount: item.amount,
            amountirrelevant: item.amountirrelevant ? 1 : 0,
            sortorder: item.sortorder,
            active: newActiveState ? 1 : 0,
            action: 'put',
            [`_qf__mod_bookit_local_form_resource_edit_resource_form`]: 1
        };

        // Convert to URL-encoded string.
        const params = new URLSearchParams(formData);

        // Add roomids as repeated parameters (roomids[]=1&roomids[]=2).
        const roomids = item.roomids || [];
        roomids.forEach(roomid => {
            params.append('roomids[]', roomid);
        });

        const formDataString = params.toString();

        // Submit via Ajax.
        try {
            const response = await Ajax.call([{
                methodname: 'core_form_dynamic_form',
                args: {
                    formdata: formDataString,
                    form: 'mod_bookit\\local\\form\\resource\\edit_resource_form'
                }
            }])[0];
            this.reactive.stateManager.processUpdates(JSON.parse(response.data));
        } catch (error) {
            // Revert checkbox on error.
            checkbox.checked = !newActiveState;
            window.console.error('Toggle active error:', error);
        }
    }

    /**
     * Handle collapse/expand of a category row.
     *
     * @param {HTMLElement} btn - The toggle button element
     */
    _handleToggleCategory(btn) {
        const categoryId = btn.dataset.categoryid;
        const isExpanded = btn.getAttribute('aria-expanded') === 'true';
        const tableView = document.querySelector(this.selectors.tableView);
        const itemRows = tableView
            ? tableView.querySelectorAll(`[data-item-categoryid="${categoryId}"]`)
            : [];

        if (isExpanded) {
            itemRows.forEach(row => row.classList.add('d-none'));
            btn.setAttribute('aria-expanded', 'false');
            btn.classList.add('collapsed');
        } else {
            itemRows.forEach(row => row.classList.remove('d-none'));
            btn.setAttribute('aria-expanded', 'true');
            btn.classList.remove('collapsed');
        }
    }

    /**
     * Show no categories message.
     */
    _showNoCategoriesMessage() {
        const msg = document.querySelector(this.selectors.noCategoriesMsg);
        if (msg) {
            msg.classList.remove('d-none');
        }
    }

    /**
     * Hide no categories message.
     */
    _hideNoCategoriesMessage() {
        const msg = document.querySelector(this.selectors.noCategoriesMsg);
        if (msg) {
            msg.classList.add('d-none');
        }
    }

    /**
     * Initialize category components from existing DOM.
     */
    _initializeCategoryComponents() {
        const categoryElements = this.element.querySelectorAll('[data-region="resource-category"]');
        const state = this.reactive.state;

        categoryElements.forEach(categoryEl => {
            const categoryId = parseInt(categoryEl.dataset.categoryid);
            const category = state.categories.get(categoryId);
            if (category) {
                // Component attaches to existing DOM element (no rendering).
                const component = new ResourceCategory({
                    element: this.element.querySelector('#mod-bookit-resource-table'),
                    reactive: this.reactive,
                    categoryData: category,
                });
                // Set existing DOM element.
                component.categoryElement = categoryEl;
                component._attachEventListeners();
                // Initialize item components from existing DOM.
                component._initItemsFromDOM();

                this.categoryComponents.set(categoryId, component);
            }
        });
    }

    /**
     * Render a new category component.
     *
     * @param {Object} categoryData - Category data object
     */
    async _renderCategory(categoryData) {
        const tableEl = this.element.querySelector('#mod-bookit-resource-table');
        if (!tableEl) {
            return;
        }

        const component = new ResourceCategory({
            element: tableEl,
            reactive: this.reactive,
            categoryData: categoryData,
        });

        await component._render();

        this.categoryComponents.set(categoryData.id, component);
    }

    /**
     * Initialize room filter event handlers.
     *
     * Integrates the roomfilter form element with reactive component.
     */
    _initializeRoomFilter() {
        const filterContainer = this.element.querySelector(this.selectors.roomFilter);
        if (!filterContainer) {
            return;
        }

        const selectedRow = filterContainer.querySelector('[data-row="selected"]');
        const unselectedRow = filterContainer.querySelector('[data-row="unselected"]');

        if (!selectedRow || !unselectedRow) {
            return;
        }

        // Event delegation on both rows using addEventListener (BaseComponent integration).
        this.addEventListener(selectedRow, 'click', (e) => {
            if (!e.target.matches('button') && !e.target.closest('button')) {
                return;
            }
            const button = e.target.closest('button');
            this._toggleFilterButton(button, selectedRow, unselectedRow);
            this._applyFilter();
        });

        this.addEventListener(unselectedRow, 'click', (e) => {
            if (!e.target.matches('button') && !e.target.closest('button')) {
                return;
            }
            const button = e.target.closest('button');
            this._toggleFilterButton(button, selectedRow, unselectedRow);
            this._applyFilter();
        });
    }

    /**
     * Toggle filter button state and move between rows.
     *
     * @param {HTMLElement} button - Button element to toggle
     * @param {HTMLElement} selectedRow - Selected row container
     * @param {HTMLElement} unselectedRow - Unselected row container
     */
    _toggleFilterButton(button, selectedRow, unselectedRow) {
        const value = button.dataset.value;
        const isPressed = button.getAttribute('aria-pressed') === 'true';
        const icon = button.querySelector('.filter-icon');
        const filterSection = button.closest('.filter-section');
        const hiddenSelect = filterSection ? filterSection.querySelector('select[multiple]') : null;

        if (isPressed) {
            // Deselect: Move to unselected row, show plus.
            button.setAttribute('aria-pressed', 'false');
            if (icon) {
                icon.textContent = '+';
            }
            this.selectedRooms.delete(value);
            unselectedRow.appendChild(button);

            // Update hidden select.
            if (hiddenSelect) {
                const option = hiddenSelect.querySelector(`option[value="${value}"]`);
                if (option) {
                    option.selected = false;
                }
            }
        } else {
            // Select: Move to selected row, show checkmark.
            button.setAttribute('aria-pressed', 'true');
            if (icon) {
                icon.textContent = '✓';
            }
            this.selectedRooms.add(value);
            selectedRow.appendChild(button);

            // Update hidden select.
            if (hiddenSelect) {
                const option = hiddenSelect.querySelector(`option[value="${value}"]`);
                if (option) {
                    option.selected = true;
                }
            }
        }
    }

    /**
     * Apply current filter to resource table.
     */
    _applyFilter() {
        const allCategories = this.element.querySelectorAll('[data-region="resource-category"]');
        const hasActiveFilters = this.selectedRooms.size > 0;

        // Reset: Show all categories and resources when no filters are active.
        if (!hasActiveFilters) {
            allCategories.forEach(category => {
                category.style.display = '';
                const resourceRows = category.querySelectorAll('[data-region="resource-item-row"]');
                resourceRows.forEach(row => {
                    row.style.display = '';
                });
            });
            return;
        }

        // Filters active: Apply strict matching.
        allCategories.forEach(category => {
            const resourceRows = category.querySelectorAll('[data-region="resource-item-row"]');

            let hasVisibleResources = false;

            resourceRows.forEach(row => {
                const resourceRooms = this._getResourceRooms(row);

                if (this._hasMatchingRoom(resourceRooms)) {
                    row.style.display = '';
                    hasVisibleResources = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Hide category if no resources match filter.
            if (hasVisibleResources) {
                category.style.display = '';
            } else {
                category.style.display = 'none';
            }
        });
    }

    /**
     * Get room IDs for a resource row.
     *
     * @param {HTMLElement} row - Resource row element
     * @return {Array} Array of room IDs
     */
    _getResourceRooms(row) {
        const roomsData = row.dataset.rooms;
        if (!roomsData) {
            return null;
        }

        try {
            const parsed = JSON.parse(roomsData);
            // Null means available in all rooms (canonical: null roomids = no room restriction).
            return Array.isArray(parsed) ? parsed : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Check if resource has any matching room.
     *
     * Null roomids means available in all rooms — always matches any active filter.
     *
     * @param {Array|null} resourceRooms - Array of room IDs, or null for all-rooms
     * @return {boolean} True if resource should be shown for current filter
     */
    _hasMatchingRoom(resourceRooms) {
        // Null means available in all rooms: always visible regardless of filter.
        if (resourceRooms === null) {
            return true;
        }

        // Empty array (restricted to no specific rooms) cannot match any filter.
        if (resourceRooms.length === 0) {
            return false;
        }

        return resourceRooms.some(roomId =>
            this.selectedRooms.has(String(roomId))
        );
    }
}
