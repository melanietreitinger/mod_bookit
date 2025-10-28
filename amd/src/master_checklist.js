import {BaseComponent} from 'core/reactive';
import {masterChecklistReactiveInstance} from 'mod_bookit/master_checklist_reactive';
import {SELECTORS} from 'mod_bookit/master_checklist_reactive';
import ModalForm from 'core_form/modalform';
import Templates from 'core/templates';
import * as Toast from 'core/toast';
import {getString} from 'core/str';
import ChecklistHelper from 'mod_bookit/checklist_helper';
import html2pdf from 'mod_bookit/html2pdf.bundle';

export default class extends BaseComponent {

    static getEvents() {
        return {
            categoryRendered: 'mod_bookit:master_checklist_category_rendered',
        };
    }

    static init(target, selectors) {
        return new this({
            element: document.querySelector(target),
            reactive: masterChecklistReactiveInstance,
            selectors: selectors || SELECTORS,
        });
    }

    create() {
        this.helper = new ChecklistHelper();
    }

    getWatchers() {
        return [
            {watch: 'checklistcategories:created', handler: this._handleCategoryCreatedEvent},
            {watch: 'checklistcategories:deleted', handler: this._handleCategoryDeletedEvent},
            {watch: 'checklistcategories.name:updated', handler: this._handleCategoryNameUpdatedEvent},

            {watch: 'checklistitems:created', handler: this._handleItemCreatedEvent},
            {watch: 'checklistitems:deleted', handler: this._handleItemDeletedEvent},
            // {watch: 'checklistitems.categoryid:updated', handler: this._handleItemCategoryUpdatedEvent},
            {watch: 'checklistitems.title:updated', handler: this._replaceRenderedItem},
            {watch: 'checklistitems.roomids:updated', handler: this._replaceRenderedItem},
            {watch: 'checklistitems.roleids:updated', handler: this._replaceRenderedItem},
            // {watch: 'activeRole:updated', handler: this._handleRoleUpdate},
            // {watch: 'activeRoom:updated', handler: this._handleRoomUpdate},
            {watch: 'activeRoom:created', handler: this._handleFilterUpdate},
        ];
    }

    stateReady(state) {

        const name = state.masterchecklists.get(1).name;

        const titleElement = this.getElement(this.selectors.MASTER_CHECKLIST_TITLE);

        titleElement.innerHTML = name;

        this.addEventListener(this.getElement(this.selectors.ADD_CHECKLIST_ITEM_BUTTON), 'click', (e) => {
            e.preventDefault();
            this._handleAddChecklistItemButtonClick(e);
        });

        this.addEventListener(this.getElement(this.selectors.ADD_CHECKLIST_CATEGORY_BUTTON), 'click', (e) => {
            e.preventDefault();
            this._handleAddChecklistCategoryButtonClick(e);
        });

        this.addEventListener(this.getElement(this.selectors.ROLE_SELECT), 'change', (e) => {
            this.reactive.dispatch('roleChanged', {id: e.target.value});
        });
        this.addEventListener(this.getElement(this.selectors.ROOM_SELECT), 'change', (e) => {

            window.console.log('ROOM SELECT CHANGED: ', e);

            // TODO we need to dispatch all selected values

            this.reactive.dispatch('roomChanged', {options: e.target.selectedOptions});
        });

        // Add event listener for export button
        this.addEventListener(this.getElement(this.selectors.EXPORT_BTN), 'click', (e) => {
            e.preventDefault();
            this._handleExportChecklistButtonClick(e);
        });

        // Add event listener for import button
        this.addEventListener(this.getElement(this.selectors.IMPORT_BTN), 'click', (e) => {
            e.preventDefault();
            this._handleImportChecklistButtonClick(e);
        });

        const spinnerElement = document.querySelector(this.selectors.LOADING_SPINNER);
        spinnerElement.classList.add('d-none');

        const mainElement = document.querySelector(this.selectors.MAIN_ELEMENT);
        mainElement.classList.remove('d-none');
        mainElement.classList.add('d-flex');

    }

    _handleFilterUpdate(event) {
        window.console.log('Filter update in master checklist:', event);

        // Check if the created element has id 0 (No selection)
        if (event.element.id === "0") {
            window.console.log('No selection detected - clearing other room selections');

            const roomSelect = this.getElement(this.selectors.ROOM_SELECT);

            // Unselect all options except the one with value "0"
            for (let option of roomSelect.options) {
                if (option.value !== "0") {
                    option.selected = false;
                } else {
                    option.selected = true;
                }
            }
        }
    }

    async _handleAddChecklistItemButtonClick() {
        const modalForm = new ModalForm({
            formClass: "mod_bookit\\form\\edit_checklist_item_form",
            args: {
                masterid: 1,
                itemid: null,
                categories: Array.from(this.reactive.state.checklistcategories.values()),
            },
            modalConfig: {
                title: await getString('checklistitem', 'mod_bookit'),
            },

        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (response) => {
            this.reactive.dispatch('checklistitemCreated', response.detail);
        });

        modalForm.show();
    }


    async _handleAddChecklistCategoryButtonClick() {
        const modalForm = new ModalForm({
            formClass: "mod_bookit\\form\\edit_checklist_category_form",
            args: {
                masterid: 1
            },
            modalConfig: {
                title: await getString('checklistcategory', 'mod_bookit'),
            },
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (response) => {
            this.reactive.stateManager.processUpdates(response.detail);

        });

        modalForm.show();
    }

    async _handleExportChecklistButtonClick(e) {
        const masterid = e.target.dataset.masterId;

        const modalForm = new ModalForm({
            formClass: "mod_bookit\\form\\export_checklist_form",
            args: {
                masterid: masterid
            },
            modalConfig: {
                title: await getString('export', 'mod_bookit'),
            },
            saveButtonText: await getString('export', 'mod_bookit'),
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, async (response) => {
            if (response.detail.success) {
                // Check if this is HTML-based PDF export or server-side export
                if (response.detail.method === 'html') {
                    // Client-side HTML2PDF export
                    window.console.log('Starting HTML2PDF export...');
                    window.console.log('MAIN_ELEMENT selector:', this.selectors.MAIN_ELEMENT);

                    var element = document.querySelector(this.selectors.MAIN_ELEMENT);
                    window.console.log('Selected element:', element);

                    if (!element) {
                        window.console.error('Element not found with selector:', this.selectors.MAIN_ELEMENT);
                        Toast.add('Export failed: Element not found', {type: 'error'});
                        return;
                    }

                    window.console.log('Element content length:', element.innerHTML.length);
                    window.console.log('html2pdf function:', typeof html2pdf, html2pdf);
                    window.console.log('Calling html2pdf...');

                    try {
                        // Generate timestamp for filename
                        const now = new Date();
                        const timestamp = now.getFullYear() +
                            String(now.getMonth() + 1).padStart(2, '0') +
                            String(now.getDate()).padStart(2, '0') + '_' +
                            String(now.getHours()).padStart(2, '0') +
                            String(now.getMinutes()).padStart(2, '0') +
                            String(now.getSeconds()).padStart(2, '0');

                        // Configure html2pdf options for better output
                        const options = {
                            margin: 1,
                            filename: `master-checklist_${timestamp}.pdf`,
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { scale: 2, useCORS: true },
                            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                        };

                        window.console.log('Using html2pdf options:', options);

                        const result = html2pdf(element, options);
                        window.console.log('html2pdf result:', result);

                        // html2pdf returns a promise, so we need to handle it
                        if (result && typeof result.then === 'function') {
                            result.then(() => {
                                window.console.log('PDF export completed successfully');
                                Toast.add(getString('export_success', 'mod_bookit'), {type: 'success'});
                            }).catch((error) => {
                                window.console.error('PDF export failed:', error);
                                Toast.add('Export failed: ' + error.message, {type: 'error'});
                            });
                        } else {
                            window.console.log('PDF export initiated (no promise returned)');
                            Toast.add(await getString('export_success', 'mod_bookit'), {type: 'success'});
                        }
                    } catch (error) {
                        window.console.error('Error calling html2pdf:', error);
                        Toast.add('Export failed: ' + error.message, {type: 'error'});
                    }
                } else if (response.detail.downloadurl) {
                    // Server-side export (TCPDF, CSV, etc.)
                    window.console.log('Starting server-side export...');
                    window.open(response.detail.downloadurl, '_blank');
                    Toast.add(await getString('export_success', 'mod_bookit'), {type: 'success'});
                } else {
                    Toast.add(response.detail.message || await getString('export_error', 'mod_bookit'), {type: 'error'});
                }
            } else {
                Toast.add(response.detail.message || await getString('export_error', 'mod_bookit'), {type: 'error'});
            }
        });

        modalForm.show();
    }

    async _handleImportChecklistButtonClick(e) {
        const masterid = e.target.dataset.masterId;

        const modalForm = new ModalForm({
            formClass: "mod_bookit\\form\\import_checklist_form",
            args: {
                masterid: masterid
            },
            modalConfig: {
                title: await getString('import', 'mod_bookit'),
            },
            saveButtonText: await getString('import', 'mod_bookit'),
        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, async (response) => {
            if (response.detail.success) {
                Toast.add(response.detail.message || await getString('importsuccessful', 'mod_bookit'), {type: 'success'});
                if (response.detail.reload) {
                    // Reload the page to show imported data
                    window.location.reload();
                }
            } else {
                Toast.add(response.detail.message || await getString('importfailed', 'mod_bookit'), {type: 'error'});
            }
        });

        modalForm.show();
    }

    _handleCategoryCreatedEvent(event) {

        if (this.reactive.state.checklistcategories && this.reactive.state.checklistcategories.size > 0) {
            const noContentElement = this.getElement(this.selectors.NOCONTENT);
            if (noContentElement) {
                noContentElement.remove();
            }
        }

        Templates.renderForPromise('mod_bookit/bookit_checklist_category',
            {
                id: event.element.id,
                name: event.element.name,
                order: event.element.order,
                masterid: 1, // TODO get from state
                type: 'category',
            })
            .then(({html, js}) => {
                Templates.appendNodeContents(this.getElement(this.selectors.TABLE), html, js);
            })
            .then(async () => {
                Toast.add(await getString('checklistcategorysuccess', 'mod_bookit'),
                    {type: 'success'});

                if (this.reactive.state.activeRole.id !== 0 || this.reactive.state.activeRoom.id !== 0) {
                    const components = this.reactive.components;
                    const categoryResults = this.helper.findComponents(components, {
                        dataset: {bookitTbodyCategoryId: event.element.id.toString()},
                        onlyFirst: false
                    });

                    const categoryComponent = categoryResults.find(comp => typeof comp._handleFilterUpdate === 'function');
                    categoryComponent._handleFilterUpdate(event);
                }
            })
            .catch(error => {
                window.console.error('Error rendering checklist item:', error);
            });
    }

    _handleItemCreatedEvent(event) {

        window.console.log('HANDLE ITEM CREATED EVENT: ', event);

        const targetElement = this.getElement(`#bookit-master-checklist-tbody-category-${event.element.category}`);

        const roomNames = [];
        if (event.element.roomnames) {
            window.console.log('ROOMNAMES: ', event.element.roomnames);
            event.element.roomnames.forEach((room) => {
                window.console.log('ROOM: ', room);
                roomNames.push({
                    'roomid': room.roomid,
                    'roomname': room.roomname,
                    'eventcolor': room.eventcolor,
                    'textclass': room.textclass,
                });
            });
        }

        const roleNames = [];
        if (event.element.rolenames) {
            event.element.rolenames.forEach((role) => {
                roleNames.push({
                    'roleid': role.roleid,
                    'rolename': role.rolename,
                    'extraclasses': role.extraclasses,
                });
            });
        }

        Templates.renderForPromise('mod_bookit/bookit_checklist_item',
            {
                id: event.element.id,
                title: event.element.title,
                order: event.element.order,
                categoryid: event.element.category,
                roomids: event.element.roomids,
                roomnames: roomNames,
                roleids: event.element.roleids,
                rolenames: roleNames,
                type: 'item',
            })
            .then(({html, js}) => {
                Templates.appendNodeContents(targetElement, html, js);
            })
            .then(async () => {
                Toast.add(await getString('checklistitemsuccess', 'mod_bookit'),
                    {type: 'success'});
                window.console.log('ITEM IS RENDERED');

                // Find and trigger filter update on parent category component
                const components = this.reactive.components;
                const categoryResults = this.helper.findComponents(components, {
                    dataset: {bookitCategoryId: event.element.category},
                    onlyFirst: true
                });

                if (categoryResults.length > 0) {
                    const categoryComponent = categoryResults[0];
                    if (categoryComponent._handleFilterUpdate) {
                        categoryComponent._handleFilterUpdate(event);
                    }
                }
            })
            .catch(error => {
                window.console.error('Error rendering checklist item:', error);
            });
    }

    _handleItemDeletedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-item-${event.element.id}`);
        targetElement.remove();

        Toast.add(getString('checklistitemdeleted', 'mod_bookit', {title: event.element.title}),
            {type: 'success'});
    }

    _replaceRenderedItem(event) {

        const actionParts = event.action.split('.');
        const fieldPart = actionParts[1].split(':')[0];

        if (fieldPart.endsWith('ids')) {

            const stateItem = this.reactive.state.checklistitems.get(event.element.id);

            const selector = `td[data-bookit-checklistitem-tabledata-${fieldPart}-id="${event.element.id}"]`;

            const targetElement = this.getElement(selector);
            let templateName, templateData;

            if (fieldPart.startsWith('room')) {

                templateName = 'mod_bookit/bookit_checklist_item_rooms';
                templateData = {
                    id: event.element.id,
                    roomnames: stateItem.roomnames
                };
            } else if (fieldPart.startsWith('role')) {

                templateName = 'mod_bookit/bookit_checklist_item_roles';
                templateData = {
                    id: event.element.id,
                    rolenames: stateItem.rolenames
                };
            } else {
                return;
            }

            Templates.renderForPromise(templateName, templateData)
            .then(({html, js}) => {
                Templates.replaceNode(targetElement, html, js);
            })
            .then(async () => {
                Toast.add(await getString('checklistitemupdatesuccess', 'mod_bookit'),
                    {type: 'success'});
            })
            .catch(error => {
                window.console.error('Error rendering checklist item field update:', error);
            });

        } else {
            const elementSelector = `span[data-bookit-checklistitem-tabledata-${fieldPart}-id="${event.element.id}"]`;

            const targetElement = this.getElement(elementSelector);

            if (targetElement) {
                targetElement.innerHTML = event.element[fieldPart];
            } else {
                window.console.error('Target element not found for selector:', elementSelector);
            }
        }

    }

    _handleCategoryDeletedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-tbody-category-${event.element.id}`);

        targetElement.remove();

        Toast.add(getString('checklistcategorydeleted', 'mod_bookit', {name: event.element.name}),
            {type: 'success'});
    }

    _handleCategoryNameUpdatedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-category-row-${event.element.id}`);

        Templates.renderForPromise('mod_bookit/bookit_checklist_category_row',
            {
                id: event.element.id,
                name: event.element.name,
                order: event.element.order,
                type: 'category',
            })
            .then(({html, js}) => {
                Templates.replaceNode(targetElement, html, js);

            })
            .then(async () => {
                Toast.add(await getString('checklistcategoryupdatesuccess', 'mod_bookit'),
                    {type: 'success'});
                    this.dispatchEvent(this.events.categoryRendered, {
                        categoryId: event.element.id
                    });
            })
            .catch(error => {
                window.console.error('Error rendering checklist category:', error);
            });

    }


    _handleRoleUpdate(event) {
        const allCategoryElements = document.querySelectorAll(this.selectors.ALL_CATEGORY_TABLE_ROWS);
        allCategoryElements.forEach(categoryElement => {

            const category = this.reactive.state.checklistcategories.get(categoryElement.dataset.bookitCategoryId);
            const items = [...category.items];
            var hasVisibleItems = false;

            const activeRoom = this.reactive.state.activeRoom.id;

            items.forEach(itemId => {

                // TODO fix rooms

                const itemElement = document.querySelector(`tr[data-bookit-checklistitem-id="${itemId}"]`);

                if (activeRoom === 0) {

                    if (parseInt(itemElement.dataset.bookitChecklistitemRole) === event.element.id ||  event.element.id === 0) {
                        itemElement.classList.remove('d-none');
                        if (!hasVisibleItems) {
                            hasVisibleItems = true;
                        }
                    } else {
                        itemElement.classList.add('d-none');
                    }
                } else if (activeRoom === parseInt(itemElement.dataset.bookitChecklistitemRoom)) {

                    if (parseInt(itemElement.dataset.bookitChecklistitemRole) === event.element.id || event.element.id === 0) {
                        itemElement.classList.remove('d-none');
                        if (!hasVisibleItems) {
                            hasVisibleItems = true;
                        }
                    } else {
                        itemElement.classList.add('d-none');
                    }
                } else {

                    itemElement.classList.add('d-none');
                }
            });

            if (!hasVisibleItems) {
                categoryElement.classList.add('d-none');
            } else {
                categoryElement.classList.remove('d-none');
            }

        });

    }

    _handleRoomUpdate(event) {

        const allCategoryElements = document.querySelectorAll(this.selectors.ALL_CATEGORY_TABLE_ROWS);
        allCategoryElements.forEach(categoryElement => {

            const category = this.reactive.state.checklistcategories.get(categoryElement.dataset.bookitCategoryId);

            const items = [...category.items];

            var hasVisibleItems = false;

            const activeRole = this.reactive.state.activeRole.id;

            // items.forEach(itemId => {

            //     const stateItem = this.reactive.state.checklistitems.get(itemId);

            //     var isInRoom = false;

            //     const roomIds = stateItem.roomids;

            //     if (roomIds.includes(event.element.id.toString())) {
            //         isInRoom = true;
            //         window.console.log('ITEM IS IN ROOM: ', itemId);
            //     } else {
            //         window.console.log('ITEM IS NOT IN ROOM: ', itemId);
            //     }

            //     const itemElement = document.querySelector(`tr[data-bookit-checklistitem-id="${itemId}"]`);

            //     if (isInRoom) {
            //         if (activeRole === 0 || parseInt(itemElement.dataset.bookitChecklistitemRole) === activeRole) {
            //             itemElement.classList.remove('d-none');
            //             if (!hasVisibleItems) {
            //                 hasVisibleItems = true;
            //             }
            //         } else {
            //             itemElement.classList.add('d-none');
            //         }
            //     } else {

            //         if (event.element.id === 0 &&
            //             (parseInt(itemElement.dataset.bookitChecklistitemRole) === activeRole || activeRole === 0)) {
            //             itemElement.classList.remove('d-none');
            //             if (!hasVisibleItems) {
            //                 hasVisibleItems = true;
            //             }
            //         } else {
            //             itemElement.classList.add('d-none');
            //         }
            //     }
            // });

            // if (!hasVisibleItems) {
            //     categoryElement.classList.add('d-none');
            // } else {
            //     categoryElement.classList.remove('d-none');
            // }

        });
    }

}