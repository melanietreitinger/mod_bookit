import { BaseComponent, DragDrop } from 'core/reactive';
import { masterChecklistReactiveInstance } from 'mod_bookit/master_checklist_reactive';
import { SELECTORS } from 'mod_bookit/master_checklist_reactive';
import ModalForm from 'core_form/modalform';
import {getString} from 'core/str';

export default class extends BaseComponent {

    create(descriptor) {

        const categoryEditBtnSelector = 'EDIT_CHECKLISTCATEGORY_BTN_' + descriptor.element.dataset.bookitCategoryId;

        this.selectors[categoryEditBtnSelector] = `#edit-checklistcategory-${descriptor.element.dataset.bookitCategoryId}`;

        const categoryTbodySelector = 'CATEGORY_TBODY_' + descriptor.element.dataset.bookitCategoryId;
        this.selectors[categoryTbodySelector] = `#bookit-master-checklist-tbody-category-${descriptor.element.dataset.bookitCategoryId}`;
    }

    static init(target, selectors) {
        return new this({
            element: document.querySelector(target),
            reactive: masterChecklistReactiveInstance,
            selectors: selectors || SELECTORS,
        });
    }

    getWatchers() {
        return [
            {watch: 'checklistcategories.name:updated', handler: this._refreshEditButtonListener},
        ];
    }

    stateReady(state) {

        this.dragdrop = new DragDrop(this);

        const categoryEditBtnSelector = 'EDIT_CHECKLISTCATEGORY_BTN_' + this.element.dataset.bookitCategoryId;

        this.addEventListener(this.getElement(this.selectors[categoryEditBtnSelector]), 'click', (e) => {
            e.preventDefault();
            window.console.log('EDIT CHECKLIST CATEGORY BUTTON CLICKED', e.currentTarget);
            this._handleEditChecklistCategoryButtonClick(e);
        });

        document.addEventListener('mod_bookit:master_checklist_category_rendered', (e) => {

            this._refreshEditButtonListener(e);
        }, true);
    }

    destroy() {
        if (this.dragdrop !== undefined) {
            this.dragdrop.unregister();
        }
    }

    validateDropData(dropdata) {
        return true;
    }

    async _handleEditChecklistCategoryButtonClick(event) {
        const modalForm = new ModalForm({
            formClass: 'mod_bookit\\form\\edit_checklist_category_form',
            moduleName: 'mod_bookit/modal_delete_save_cancel',
            args: {
                id: this.element.dataset.bookitCategoryId,
                masterid: 1,
                checklistitems: JSON.stringify(this.reactive.state.checklistcategories.get(this.element.dataset.bookitCategoryId).items),
            },
            modalConfig: {
                title: await getString('checklistcategory', 'mod_bookit'),
            },
        })

        modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (response) => {
            this.reactive.stateManager.processUpdates(response.detail);
        });

        modalForm.addEventListener(modalForm.events.LOADED, (response) => {

            const deleteButton = modalForm.modal.getRoot().find('button[data-action="delete"]');

            deleteButton.on('click', (e) => {
                modalForm.getFormNode().querySelector('input[name="action"]').value = 'delete';
                modalForm.submitFormAjax();
            });
        });

        modalForm.show();
    };

    _refreshEditButtonListener(event) {
        this.removeAllEventListeners();

        const categoryEditBtnSelector = 'EDIT_CHECKLISTCATEGORY_BTN_' + this.element.dataset.bookitCategoryId;

        this.addEventListener(this.getElement(this.selectors[categoryEditBtnSelector]), 'click', (e) => {
            e.preventDefault();
            this._handleEditChecklistCategoryButtonClick(e);
        });
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

    showDropZone(dropdata, event) {
        const root = document.querySelector('html');
        const primaryColor = getComputedStyle(root).getPropertyValue('--primary');

        this.element.style.boxShadow = `0px -5px 0px 0px ${primaryColor} inset`;
        this.element.style.transition = 'box-shadow 0.1s ease';
    }

    hideDropZone(dropdata, event) {
        this.element.style.boxShadow = '';
        this.element.style.backgroundBlendMode = '';
        this.element.style.transition = '';
    }

    _handleItemDrop(dropdata, event) {

        const categoryObject = this.reactive.state.checklistcategories.get(this.element.dataset.bookitCategoryId);

        const categoryObjectItems = [...categoryObject.items];

        const lastItemId = categoryObjectItems[categoryObjectItems.length - 1];

        dropdata.targetId = parseInt(lastItemId);
        dropdata.targetParentId = parseInt(this.element.dataset.bookitCategoryId);

        this.reactive.dispatch('reOrderCategoryItems', dropdata);

        const itemObject = this.reactive.state.checklistitems.get(dropdata.id);

        const itemElement = document.getElementById(`bookit-master-checklist-item-${itemObject.id}`);

        const itemHasChangedParent = dropdata.parentId !== dropdata.targetParentId;

        if (itemHasChangedParent) {
            itemElement.dataset.bookitChecklistitemCategoryid = dropdata.targetParentId;
        }

        this.element.append(itemElement);
    }


    _handleCategoryDrop(dropdata, event) {

        dropdata.targetId = parseInt(this.element.dataset.bookitCategoryId);
        dropdata.targetParentId = parseInt(this.element.dataset.bookitCategoryMasterid);

        this.reactive.dispatch('reOrderCategories', dropdata);

        const categoryObject = this.reactive.state.checklistcategories.get(dropdata.id);

        const categoryElement = document.getElementById(`bookit-master-checklist-tbody-category-${categoryObject.id}`);

        const tableElement = document.querySelector(this.selectors.TABLE);

        tableElement.insertBefore(categoryElement, this.element.nextElementSibling);

    }

}