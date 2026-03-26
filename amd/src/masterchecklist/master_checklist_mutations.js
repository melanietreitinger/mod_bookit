import Ajax from 'core/ajax';

export default class {
    masterChecklistStateEvent() {
        // This method is intentionally empty - it's a placeholder for state events.
    }

    _callDynamicForm(stateManager, data, processUpdates = true) {
        const type = data.formType;
        data.formData[`_qf__mod_bookit_local_form_masterchecklist_edit_checklist_${type}_form`] = 1;
        const formData = new URLSearchParams(data.formData).toString();

        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: formData,
                form: `mod_bookit\\local\\form\\masterchecklist\\edit_checklist_${type}_form`
            }
        }])[0]
        .then((response) => {
            if (processUpdates) {
                stateManager.processUpdates(JSON.parse(response.data));
            }
            return;
        })
        .catch(exception => {
            window.console.error('AJAX error:', exception);
        });

    }

    _updateChecklistItemCategory(stateManager, itemId) {
        const state = stateManager.state;
        const itemObject = state.checklistitems.get(itemId);

        const formDataObj = {
            itemid: itemObject.id,
            masterid: state.activechecklist.id,
            title: itemObject.title,
            categoryid: itemObject.categoryid,
            roomid: itemObject.roomid,
            roleid: itemObject.roleid,
            action: 'put',
        };

        const mutationData = {
            formData: formDataObj,
            formType: 'item'
        };

        this._callDynamicForm(stateManager, mutationData, false);
    }

    reOrderCategoryItems(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        // The item was moved to a different category. We need to update both categories.
        if (data.parentId !== data.targetParentId) {
            const sourceCategory = state.checklistcategories.get(data.parentId);
            const targetCategory = state.checklistcategories.get(data.targetParentId);

            if (!sourceCategory.items || !Array.isArray(sourceCategory.items)) {
                sourceCategory.items = [];
            }

            if (!targetCategory.items || !Array.isArray(targetCategory.items)) {
                targetCategory.items = [];
            }

            const idToMove = parseInt(data.id);
            const targetId = parseInt(data.targetId);

            sourceCategory.items = sourceCategory.items.filter(item => item !== idToMove);

            const targetItems = [...targetCategory.items];
            const existingTargetIndex = targetItems.indexOf(idToMove);

            if (existingTargetIndex === -1) {
                const targetIndex = targetItems.indexOf(targetId);

                if (targetIndex !== -1) {
                    targetItems.splice(targetIndex + 1, 0, idToMove);
                } else {
                    targetItems.push(idToMove);
                }
            }

            targetCategory.items = targetItems;

            const targetItem = state.checklistitems.get(idToMove);

            targetItem.categoryid = parseInt(targetCategory.id);

            this._updateChecklistItemCategory(stateManager, idToMove);

        } else {
            // The item was moved within the same category. We only need to update one category.
            const category = state.checklistcategories.get(data.targetParentId);

            if (!category.items || !Array.isArray(category.items)) {
                category.items = [];
            }

            const currentItems = [...category.items];
            const idToMove = parseInt(data.id);
            const targetId = parseInt(data.targetId);

            const currentIndex = currentItems.indexOf(idToMove);

            if (currentIndex === -1) {
                currentItems.push(idToMove);
            }

            const targetIndex = currentItems.indexOf(targetId);

            if (targetIndex !== -1 && currentIndex !== -1) {
                currentItems.splice(currentIndex, 1);

                const newTargetIndex = currentItems.indexOf(targetId);

                currentItems.splice(newTargetIndex + 1, 0, idToMove);
            } else if (currentIndex !== -1) {
                // Handle case where target is not found but current index exists.
                currentItems.splice(currentIndex, 1);
                currentItems.push(idToMove);
            }

            category.items = currentItems;

        }

        stateManager.setReadOnly(true);

        const categoriesToUpdate = [];

        categoriesToUpdate.push(data.targetParentId);

        if (data.parentId !== data.targetParentId) {
            categoriesToUpdate.push(data.parentId);
        }

        // Persist state changes.
        categoriesToUpdate.forEach(categoryId => {
            const category = stateManager.state.checklistcategories.get(categoryId);
            const formDataObj = {
                id: category.id,
                masterid: stateManager.state.activechecklist.id,
                name: category.name,
                checklistitems: category.items,
                action: 'put',
            };

            const mutationData = {
                formData: formDataObj,
                formType: 'category'
            };

            this._callDynamicForm(stateManager, mutationData, false);
        });
    }

    reOrderCategories(stateManager, data) {
        const state = stateManager.state;

        // Get master checklist ID from DOM instead of hardcoding
        const tableElement = document.querySelector('#mod-bookit-master-checklist-table');
        const masterId = parseInt(tableElement.dataset.masterChecklistId);
        const masterChecklist = state.masterchecklists.get(masterId);
        if (!masterChecklist) {
            window.console.error('Master checklist not found');
            stateManager.setReadOnly(true);
            return;
        }

        let categoryOrder = masterChecklist.mastercategoryorder ?
            masterChecklist.mastercategoryorder.split(',').map(id => parseInt(id)) : [];

        const idToMove = parseInt(data.id);
        const targetId = parseInt(data.targetId);

        categoryOrder = categoryOrder.filter(id => id !== idToMove);

        const targetIndex = categoryOrder.indexOf(targetId);

        if (targetIndex !== -1) {
            categoryOrder.splice(targetIndex + 1, 0, idToMove);
        } else {
            categoryOrder.push(idToMove);
        }

        const updatedCategoryOrder = categoryOrder.join(',');

        const formDataObj = {
            id: data.parentId,
            mastercategoryorder: updatedCategoryOrder,
            action: 'put',
        };

        data.formData = formDataObj;
        data.formType = 'master';

        this._callDynamicForm(stateManager, data);
    }

    checklistitemCreated(stateManager, data) {
        const state = stateManager.state;

        stateManager.processUpdates(data);

        stateManager.setReadOnly(false);
        const category = state.checklistcategories.get(data[0].fields.category);
        const currentItems = [...category.items];
        currentItems.push(data[0].fields.id);
        category.items = currentItems;
        stateManager.setReadOnly(true);

    }

    checklistitemDeleted(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        const category = state.checklistcategories.get(data.categoryid);

        const currentItems = [...category.items];
        const itemIndex = currentItems.indexOf(data.id);

        if (itemIndex !== -1) {
            currentItems.splice(itemIndex, 1);
            category.items = currentItems;
        }

        stateManager.setReadOnly(true);
    }

    roomChanged(stateManager, data) {
        const state = stateManager.state;

        const optionsArray = Array.from(data.options);

        stateManager.setReadOnly(false);

        let tempArray = [];
        optionsArray.forEach(option => {
            tempArray.push({
                id: option.value,
                name: option.textContent
            });
        });

        const hasNoSelection = tempArray.some(room => room.id === "0");
        if (hasNoSelection) {
            tempArray = tempArray.filter(room => room.id === "0");
        }

        // Clear the existing StateMap and add new elements
        state.activeRoom.clear();
        tempArray.forEach((room) => {
            state.activeRoom.set(room.id, room);
        });

        stateManager.setReadOnly(true);
    }

    roleChanged(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        state.activeRole.id = parseInt(data.id);

        stateManager.setReadOnly(true);
    }

}