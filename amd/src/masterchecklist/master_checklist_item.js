import {BaseComponent, DragDrop} from 'core/reactive';
import {masterChecklistReactiveInstance} from 'mod_bookit/masterchecklist/master_checklist_reactive';
import {SELECTORS} from 'mod_bookit/masterchecklist/master_checklist_reactive';
import ModalForm from 'core_form/modalform';
import {getString} from 'core/str';
import Templates from 'core/templates';
import Notification from 'core/notification';

export default class extends BaseComponent {

    static MODAL_TIMEOUT_MS = 500;

    create(descriptor) {

        const itemEditBtnSelector = 'EDIT_CHECKLISTITEM_BTN_' + descriptor.element.dataset.bookitChecklistitemId;
        this.selectors[itemEditBtnSelector] = `#edit-checklistitem-${descriptor.element.dataset.bookitChecklistitemId}`;
    }

    static init(target, selectors) {
        return new this({
            element: document.querySelector(target),
            reactive: masterChecklistReactiveInstance,
            selectors: selectors || SELECTORS,
        });
    }

    getWatchers() {
        return [];
    }

    stateReady() {

        this.dragdrop = new DragDrop(this);

        const itemEditBtnSelector = 'EDIT_CHECKLISTITEM_BTN_' + this.element.dataset.bookitChecklistitemId;

        this.addEventListener(this.getElement(this.selectors[itemEditBtnSelector]), 'click', (e) => {
            e.preventDefault();
            this._handleEditChecklistItemButtonClick(e);
        });

        this.shouldBeVisible();

    }

    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    validateDropData() {
        return true;
    }

    drop(dropdata, event) {
        switch (dropdata.type) {
            case 'item':
                this._handleItemDrop(dropdata, event);
                break;
            case 'category':
                this._handleCategoryDrop(dropdata, event);
                break;
            default:
                throw new Error(`Unknown drop type: ${dropdata.type}`);
        }
    }


    showDropZone(dropdata) {

        const root = document.querySelector('html');
        const primaryColor = getComputedStyle(root).getPropertyValue('--primary');

        switch (dropdata.type) {
            case 'item': {
                this.element.style.boxShadow = `0px -5px 0px 0px ${primaryColor} inset`;
                this.element.style.transition = 'box-shadow 0.1s ease';
                break;
            }
            case 'category': {
                const itemParentId = parseInt(this.element.dataset.bookitChecklistitemCategoryid);
                const categoryParentElement = document.getElementById(`bookit-master-checklist-tbody-category-${itemParentId}`);
                var isActive = parseInt(categoryParentElement.dataset.bookitCategoryActive || 0);
                if (!isActive) {
                    categoryParentElement.dataset.bookitCategoryActive = 1;
                }
                const categoryLastChild = categoryParentElement.lastElementChild;
                setTimeout(() => {
                    categoryParentElement.dataset.bookitCategoryActive = 0;
                }, 5);
                categoryLastChild.style.boxShadow = `0px -5px 0px 0px ${primaryColor} inset`;
                categoryLastChild.style.transition = 'box-shadow 0.1s ease';
                break;
            }
            default:
                throw new Error(`Unknown drop type: ${dropdata.type}`);
        }

    }

    hideDropZone(dropdata) {

        switch (dropdata.type) {
            case 'item': {
                this.element.style.boxShadow = '';
                this.element.style.transition = '';
                break;
            }
            case 'category': {
                const itemParentId = parseInt(this.element.dataset.bookitChecklistitemCategoryid);
                const categoryParentElement = document.getElementById(`bookit-master-checklist-tbody-category-${itemParentId}`);
                const categoryLastChild = categoryParentElement.lastElementChild;
                var isActive = parseInt(categoryParentElement.dataset.bookitCategoryActive || 0);
                if (!isActive) {
                    categoryLastChild.style.boxShadow = '';
                    categoryLastChild.style.transition = '';
                }
                break;
            }
            default:
                throw new Error(`Unknown drop type: ${dropdata.type}`);
        }

    }

    _handleItemDrop(dropdata) {
        dropdata.targetId = parseInt(this.element.dataset.bookitChecklistitemId);
        dropdata.targetParentId = parseInt(this.element.dataset.bookitChecklistitemCategoryid);

        this.reactive.dispatch('reOrderCategoryItems', dropdata);

        const itemObject = this.reactive.state.checklistitems.get(dropdata.id);

        const itemElement = document.getElementById(`bookit-master-checklist-item-${itemObject.id}`);

        const itemHasChangedParent = dropdata.parentId !== dropdata.targetParentId;

        if (itemHasChangedParent) {
            itemElement.dataset.bookitChecklistitemCategoryid = dropdata.targetParentId;
        }

        this.element.parentNode.insertBefore(itemElement, this.element.nextElementSibling);
    }


    _handleCategoryDrop(dropdata, event) {
        const categoryElement = document.getElementById(`bookit-master-checklist-tbody-category-${dropdata.id}`);

        dropdata.targetId = this.element.dataset.bookitChecklistitemCategoryid;
        dropdata.targetParentId = categoryElement.dataset.bookitCategoryMasterid;

        this.reactive.dispatch('reOrderCategories', dropdata);

        const tableElement = document.querySelector(this.selectors.TABLE);

        categoryElement.dataset.bookitCategoryActive = 1;

        this.hideDropZone(dropdata, event);

        tableElement.insertBefore(categoryElement, this.element.parentNode.nextElementSibling);
    }


    async _handleEditChecklistItemButtonClick(event) {
        const modalForm = new ModalForm({
            formClass: "mod_bookit\\local\\form\\masterchecklist\\edit_checklist_item_form",
            moduleName: 'mod_bookit/modal_delete_save_cancel',
            args: {
                masterid: this.reactive.state.activechecklist.id,
                itemid: event.currentTarget.value,
                categories: Array.from(this.reactive.state.checklistcategories.values()),
            },
            modalConfig: {
                title: await getString('checklistitem', 'mod_bookit'),
            },

        });

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (response) => {
            this.reactive.stateManager.processUpdates(response.detail);

            if (response.detail[0].action === 'delete') {
                this.reactive.dispatch('checklistitemDeleted',
                    {
                        id: parseInt(response.detail[0].fields.id),
                        categoryid: parseInt(this.element.dataset.bookitChecklistitemCategoryid),
                    });
                this.remove();
                return;
            }

            const parentId = parseInt(this.element.dataset.bookitChecklistitemCategoryid);
            const updatedParentId = parseInt(response.detail[0].fields.categoryid);

            if (parentId !== updatedParentId) {

                const targetParentCategoryObject =
                    this.reactive.state.checklistcategories.get(response.detail[0].fields.categoryid);
                const copiedArray = [...targetParentCategoryObject.items];
                const lastItemOfParentCategoryId = copiedArray.pop();

                const data = {
                    id: parseInt(this.element.dataset.bookitChecklistitemId),
                    type: 'item',
                    parentId: parentId,
                    targetId: lastItemOfParentCategoryId,
                    targetParentId: updatedParentId,
                };

                this.reactive.dispatch('reOrderCategoryItems', data);
                this.element.dataset.bookitChecklistitemCategoryid = data.targetParentId;

                const targetParentElement =
                    document.getElementById(`bookit-master-checklist-tbody-category-${data.targetParentId}`);
                targetParentElement.append(this.element);
            }


        });

        modalForm.addEventListener(modalForm.events.LOADED, () => {
            const deleteButton = modalForm.modal.getRoot().find('button[data-action="delete"]');

            deleteButton.on('click', async(e) => {
                e.preventDefault();

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

            setTimeout(() => {
                this._addResetButtonsToNotificationEditors(modalForm);
            }, this.constructor.MODAL_TIMEOUT_MS);

            setTimeout(() => {
                this._addRequiredIconsToNotificationFields(modalForm);
            }, this.constructor.MODAL_TIMEOUT_MS);

        });

        modalForm.addEventListener(modalForm.events.SERVER_VALIDATION_ERROR, () => {
            setTimeout(() => {
                this._addResetButtonsToNotificationEditors(modalForm);
                this._addRequiredIconsToNotificationFields(modalForm);
            }, this.constructor.MODAL_TIMEOUT_MS);
        });

        modalForm.show();
    }

    /**
     * Add required icons to notification fields
     * @param {ModalForm} modalForm The modal form instance
     */
    _addRequiredIconsToNotificationFields(modalForm) {
        const notificationTypes = ['before_due', 'when_due', 'overdue', 'when_done'];

        notificationTypes.forEach(type => {
            const recipientElement = modalForm.getFormNode().querySelector(`[id^="fitem_id_${type}_recipient_"]`);
            if (recipientElement) {
                const firstDiv = recipientElement.firstElementChild;
                if (firstDiv) {
                    const formLabelAddon = firstDiv.querySelector('.form-label-addon');
                    if (formLabelAddon && formLabelAddon.firstElementChild) {
                        const anchorElement = formLabelAddon.firstElementChild;
                        this._addRequiredIcon(anchorElement, type, 'recipient');
                    }
                }
            }

            const timeElement = modalForm.getFormNode().querySelector(`[id^="fitem_id_${type}_time_"]`);
            if (timeElement) {
                const firstDiv = timeElement.firstElementChild;
                if (firstDiv) {
                    const formLabelAddon = firstDiv.querySelector('.form-label-addon');
                    if (formLabelAddon && formLabelAddon.firstElementChild) {
                        const anchorElement = formLabelAddon.firstElementChild;
                        this._addRequiredIcon(anchorElement, type, 'time');
                    }
                }
            }

            const messagetextElement = modalForm.getFormNode().querySelector(`[id^="fitem_id_${type}_messagetext_"]`);
            if (messagetextElement) {
                const firstDiv = messagetextElement.firstElementChild;
                if (firstDiv) {
                    const formLabelAddon = firstDiv.querySelector('.form-label-addon');
                    if (formLabelAddon && formLabelAddon.firstElementChild) {
                        const anchorElement = formLabelAddon.firstElementChild;
                        this._addRequiredIcon(anchorElement, type, 'messagetext');
                    }
                }
            }
        });
    }

    /**
     * Helper method to add required icon to an element
     * @param {Element} anchorElement The anchor element to insert before
     */
    _addRequiredIcon(anchorElement) {
        Templates.renderForPromise('core/pix_icon_fontawesome', {
            key: 'fa-circle-exclamation',
            title: 'Required',
            alt: 'Required field',
            extraclasses: 'fa text-danger fa-fw',
            'aria-hidden': false,
            unmappedIcon: false
        })
        .then((result) => {
            const iconHtml = result.html;
            const requiredIconHtml = `<div class="text-danger" title="Required" aria-hidden="true">${iconHtml}</div>`;
            anchorElement.insertAdjacentHTML('beforebegin', requiredIconHtml);
            return;
        })
        .catch(() => {
            // Template error silently ignored.
        });
    }

    /**
     * Add reset buttons to notification type message editors
     * @param {ModalForm} modalForm The modal form instance
     */
    _addResetButtonsToNotificationEditors(modalForm) {
        const notificationTypes = ['before_due', 'when_due', 'overdue', 'when_done'];

        notificationTypes.forEach(type => {
            const resetButton = modalForm.getFormNode().querySelector(`button[name="${type}_reset"]`);

            if (resetButton) {
                resetButton.addEventListener('click', async(e) => {
                    e.preventDefault();

                    try {
                        const confirmTitle = await getString('confirm', 'core');
                        const confirmMessage = await getString('resetmessagetoconfirm', 'mod_bookit');

                        Notification.deleteCancel(
                            confirmTitle,
                            confirmMessage,
                            await getString('reset', 'core'),
                            async() => {
                                await this._performReset(modalForm, type);
                            }
                        );

                    } catch (error) {
                        window.console.error('Error showing confirmation dialog:', error);
                    }
                });
            }
        });
    }

    /**
     * Helper method to perform the reset operation
     * @param {ModalForm} modalForm The modal form instance
     * @param {string} type The notification type
     */
    async _performReset(modalForm, type) {
        const defaultMessage = await getString(`customtemplatedefaultmessage_${type}`, 'mod_bookit');

        const editorSelector = `[name="${type}_messagetext[text]"]`;
        const textarea = modalForm.getFormNode().querySelector(editorSelector);

        if (textarea) {
            textarea.value = defaultMessage;
            textarea.dispatchEvent(new Event('input', {bubbles: true}));
            textarea.dispatchEvent(new Event('change', {bubbles: true}));

            if (window.M && window.M.editor_atto && textarea.id) {
                const editorInstance = window.M.editor_atto.get(textarea.id);
                if (editorInstance) {
                    editorInstance.updateOriginal();
                }
            }
        }

        if (window.tinymce) {
            const editorId = textarea?.id;
            if (editorId) {
                const editor = window.tinymce.get(editorId);
                if (editor) {
                    editor.setContent(defaultMessage);
                    editor.save();
                }
            }
        }
    }

    shouldBeVisible() {
        const activeRooms = this.reactive.state.activeRoom;
        const activeRoleId = this.reactive.state.activeRole.id;
        const itemId = parseInt(this.element.dataset.bookitChecklistitemId);
        const stateItem = this.reactive.state.checklistitems.get(itemId);
        const roomIds = stateItem.roomids;
        const roleIds = stateItem.roleids;

        var isInRoom = false;

        const noRoomSelection = activeRooms.some(room => {
            return room.id == 0;
        });

        let parsedRoomIds;
        try {
            parsedRoomIds = JSON.parse(roomIds);
        } catch (error) {
            parsedRoomIds = roomIds;
        }

        let hasMatchingRoom = false;
        if (Array.isArray(parsedRoomIds)) {
            hasMatchingRoom = activeRooms.some(activeRoom =>
                parsedRoomIds.includes(activeRoom.id.toString())
            );
        } else {
            hasMatchingRoom = activeRooms.some(activeRoom =>
                activeRoom.id.toString() === parsedRoomIds.toString()
            );
        }

        if (noRoomSelection || hasMatchingRoom) {
            isInRoom = true;
        }

        var hasRole = false;

        if (activeRoleId == 0 || roleIds.includes(activeRoleId.toString())) {
            hasRole = true;
        }

        const shouldBeVisible = isInRoom && hasRole;

        if (!shouldBeVisible) {
            this.element.classList.add('d-none');
        } else {
            this.element.classList.remove('d-none');
        }

        return shouldBeVisible;
    }
}