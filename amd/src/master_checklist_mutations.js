import Ajax from 'core/ajax';

export default class {
    masterChecklistStateEvent(stateManager, data) {

    }

    _callDynamicForm(stateManager, data, processUpdates = true) {
        const type = data.formType;
        data.formData[`_qf__mod_bookit_form_edit_checklist_${type}_form`] = 1;
        const formData = new URLSearchParams(data.formData).toString();

        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: formData,
                form: `mod_bookit\\form\\edit_checklist_${type}_form`
            }
        }])[0]
        .then((response) => {
            if (processUpdates) {
                stateManager.processUpdates(JSON.parse(response.data));
            }
        })
        .catch(exception => {
            window.console.error('AJAX error:', exception);
        });

    }

    reOrderCategoryItems(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

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

        } else {
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
            }

            category.items = currentItems;

        }

        stateManager.setReadOnly(true);

        const categoriesToUpdate = [];

        categoriesToUpdate.push(data.targetParentId);

        if (data.parentId !== data.targetParentId) {
            categoriesToUpdate.push(data.parentId);
        }

        categoriesToUpdate.forEach(categoryId => {
            const category = stateManager.state.checklistcategories.get(categoryId);
            const formDataObj = {
                id: category.id,
                masterid: 1,
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

        const masterChecklist = state.masterchecklists.get(1);
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

        const updatedCategoryOrder =  categoryOrder.join(',');

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
            window.console.log('item found in category items - removing');
            currentItems.splice(itemIndex, 1);
            category.items = currentItems;
        }

        stateManager.setReadOnly(true);
    }

    roomChanged(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        state.activeRoom.id = parseInt(data.id);

        stateManager.setReadOnly(true);
    }

    roleChanged(stateManager, data) {
        const state = stateManager.state;

        stateManager.setReadOnly(false);

        state.activeRole.id = parseInt(data.id);

        stateManager.setReadOnly(true);
    }

}