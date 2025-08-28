import { BaseComponent } from 'core/reactive';
import { masterChecklistReactiveInstance } from 'mod_bookit/master_checklist_reactive';
import { SELECTORS } from 'mod_bookit/master_checklist_reactive';
import ModalForm from 'core_form/modalform';
import Templates from 'core/templates';
import * as Toast from 'core/toast';
import {getString} from 'core/str';
import Ajax from 'core/ajax';

export default class extends BaseComponent {

    static getEvents() {
        return {
            categoryRendered: 'mod_bookit:master_checklist_category_rendered',
        };
    }

    create(descriptor) {

        window.console.log('create component: ' + descriptor.reactive.name);
        window.console.log("selectors in create master checklist: ", SELECTORS);

    }

    static init(target, selectors) {
        return new this({
            element: document.querySelector(target),
            reactive: masterChecklistReactiveInstance,
            selectors: selectors || SELECTORS,
        });
    }


    getWatchers() {
        window.console.log('GET WATCHERS');
        return [
            {watch: 'state:updated', handler: this._handleStateEvent},
            {watch: 'checklistcategories:created', handler: this._handleCategoryCreatedEvent},
            {watch: 'checklistcategories:deleted', handler: this._handleCategoryDeletedEvent},
            {watch: 'checklistcategories.name:updated', handler: this._handleCategoryNameUpdatedEvent},
            {watch: 'checklistcategories.items:updated', handler: this._handleCategoryItemsUpdatedEvent},
            {watch: 'checklistitems:created', handler: this._handleItemCreatedEvent},
            {watch: 'checklistitems:deleted', handler: this._handleItemDeletedEvent},
            {watch: 'checklistitems:updated', handler: this._handleItemUpdatedEvent},
            {watch: 'checklistitems.categoryid:updated', handler: this._handleItemCategoryUpdatedEvent},
            {watch: 'checklistitems.title:updated', handler: this._replaceRenderedItem},
            {watch: 'checklistitems.roomid:updated', handler: this._replaceRenderedItem},
            {watch: 'checklistitems.roleid:updated', handler: this._replaceRenderedItem},
            {watch: 'activeRole:updated', handler: this._handleRoleUpdate},
            {watch: 'activeRoom:updated', handler: this._handleRoomUpdate},
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
            this.reactive.dispatch('roleChanged', { id: e.target.value });
        });
        this.addEventListener(this.getElement(this.selectors.ROOM_SELECT), 'change', (e) => {
            this.reactive.dispatch('roomChanged', { id: e.target.value });
        });

    }

    _handleStateEvent(event) {

    }

    async _handleAddChecklistItemButtonClick(event) {

        // TODO do this in mutation instead
        const modalForm = new ModalForm({
            formClass: "mod_bookit\\form\\edit_checklistitem_form",
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

    async _handleAddChecklistCategoryButtonClick(event) {
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

    _handleCategoryCreatedEvent(event) {
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
                    {type: 'success' });
            })
            .catch();
    }

    _handleItemCreatedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-tbody-category-${event.element.category}`);

        Templates.renderForPromise('mod_bookit/bookit_checklist_item',
            {
                id: event.element.id,
                title: event.element.title,
                order: event.element.order,
                categoryid: event.element.category,
                roomid: event.element.roomid,
                roomname: event.element.roomname,
                roleid: event.element.roleid,
                rolename: event.element.rolename,
                type: 'item',
            })
            .then(({html, js}) => {
                Templates.appendNodeContents(targetElement, html, js);
            })
            .then(async () => {
                Toast.add(await getString('checklistitemsuccess', 'mod_bookit'),
                    {type: 'success' });
            })
            .catch(error => {
                window.console.error('Error rendering checklist item:', error);
            });
    }

    _handleItemDeletedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-item-${event.element.id}`);
        targetElement.remove();

        Toast.add(getString('checklistitemdeleted', 'mod_bookit', {title: event.element.title}),
            {type: 'success' });
    }

    _handleItemUpdatedEvent(event) {
        //TODO remove
    }

    _replaceRenderedItem(event) {

        const actionParts = event.action.split('.');
        const fieldPart = actionParts[1].split(':')[0];

        const elementSelector = `td[data-bookit-checklistitem-tabledata-${fieldPart}-id="${event.element.id}"]`;

        const targetElement = this.getElement(elementSelector);

        if (fieldPart.endsWith('id')) {
            const nameField = fieldPart.substring(0, fieldPart.length - 2) + 'name';

            if (nameField in event.element) {
                targetElement.innerHTML = event.element[nameField];
            }
        } else {
            targetElement.innerHTML = event.element[fieldPart];
        }

    }

    _handleItemCategoryUpdatedEvent(event) {
        const itemObject = this.reactive.state.checklistitems.get(event.element.id);

        const formDataObj = {
            itemid: itemObject.id,
            masterid: 1,
            title: itemObject.title,
            categoryid: itemObject.categoryid,
            roomid: itemObject.roomid,
            roleid: itemObject.roleid,
            action: 'put',
            _qf__mod_bookit_form_edit_checklistitem_form: 1,
        };

        const formData = new URLSearchParams(formDataObj).toString();
        // TODO move to mutation
        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: formData,
                form: 'mod_bookit\\form\\edit_checklistitem_form'
            }
            }])[0]
            .then((response) => {
                // TODO handle response?
                })
                .catch(exception => {
                    window.console.error('AJAX error:', exception);
                });

    }

    _handleCategoryDeletedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-tbody-category-${event.element.id}`);

        targetElement.remove();

        Toast.add(getString('checklistcategorydeleted', 'mod_bookit', {name: event.element.name}),
            {type: 'success' });
    }

    _handleCategoryNameUpdatedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-category-row-${event.element.id}`);

        Templates.renderForPromise('mod_bookit/bookit_checklist_category_row',
            {
                id: event.element.id,
                name: event.element.name,
                order: event.element.order,
            })
            .then(({html, js}) => {
                Templates.replaceNode(targetElement, html, js);

            })
            .then(async () => {
                Toast.add(await getString('checklistcategoryupdatesuccess', 'mod_bookit'),
                    {type: 'success' });
                    this.dispatchEvent(this.events.categoryRendered, {
                        categoryId: event.element.id
                    });
            })
            .catch(error => {
                window.console.error('Error rendering checklist category:', error);
            });

    }

    _handleCategoryItemsUpdatedEvent(event) {
        const targetElement = this.getElement(`#bookit-master-checklist-tbody-category-${event.element.id}`);

        const category = this.reactive.state.checklistcategories.get(event.element.id);

        const formDataObj = {
            id: category.id,
            masterid: 1,
            name: category.name,
            checklistitems: category.items,
            action: 'put',
            _qf__mod_bookit_form_edit_checklist_category_form: 1,
        };

        const formData = new URLSearchParams(formDataObj).toString();
        // TODO move to mutation
        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: formData,
                form: 'mod_bookit\\form\\edit_checklist_category_form'
            }
        }])[0]
        .then((response) => {
            // TODO handle response?
        })
        .catch(exception => {
            window.console.error('AJAX error:', exception);

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

            items.forEach(itemId => {
                const itemElement = document.querySelector(`tr[data-bookit-checklistitem-id="${itemId}"]`);

                if (parseInt(itemElement.dataset.bookitChecklistitemRoom) === event.element.id) {
                    if (activeRole === 0 || parseInt(itemElement.dataset.bookitChecklistitemRole) === activeRole) {
                        itemElement.classList.remove('d-none');
                        if (!hasVisibleItems) {
                            hasVisibleItems = true;
                        }
                    } else {
                        itemElement.classList.add('d-none');
                    }
                } else {

                    if (event.element.id === 0 &&
                        (parseInt(itemElement.dataset.bookitChecklistitemRole) === activeRole || activeRole === 0)) {
                        itemElement.classList.remove('d-none');
                        if (!hasVisibleItems) {
                            hasVisibleItems = true;
                        }
                    } else {
                        itemElement.classList.add('d-none');
                    }
                }
            });

            if (!hasVisibleItems) {
                categoryElement.classList.add('d-none');
            } else {
                categoryElement.classList.remove('d-none');
            }

        });
    }

}