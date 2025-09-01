import { Reactive } from 'core/reactive';
import Mutations from 'mod_bookit/master_checklist_mutations';

export const SELECTORS = {
            TABLE: '#mod-bookit-master-checklist-table',
            MAIN_ELEMENT: '#mod-bookit-master-checklist',
            ADD_CHECKLIST_CATEGORY_BUTTON: '#add-checklist-category-button',
            ADD_CHECKLIST_ITEM_BUTTON: '#add-checklist-item-button',
            MASTER_CHECKLIST_TITLE: '#mod-bookit-master-checklist-title',
            ALL_CATEGORY_TABLE_ROWS: 'tr[data-bookit-category-id]',
            ALL_ITEM_TABLE_ROWS: 'tr[data-bookit-checklistitem-id]',
            TABLE_BODY: '#mod-bookit-master-checklist-tbody',
            ALL_ROLE_OPTIONS: 'option[data-bookit-roleoption]',
            ALL_ROOM_OPTIONS: 'option[data-bookit-roomoption]',
            ROLE_SELECT: '#bookit-master-role-select',
            ROOM_SELECT: '#bookit-master-room-select',
        };


const EVENTNAME = 'mod_bookit:master_checklist_state_event'

export const masterChecklistReactiveInstance = new Reactive({
        eventName: EVENTNAME,
        eventDispatch: dispatchMasterChecklistStateEvent,
        mutations: new Mutations(),
        name: 'Moodle Bookit Master Checklist',
    });

export const init = (elementId) => {

    loadState(masterChecklistReactiveInstance);
}

function dispatchMasterChecklistStateEvent(detail, target) {

    window.console.log('dispatch master checklist state event function');
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(
        new CustomEvent(
            EVENTNAME,
            {
                bubbles: true,
                detail: detail,
            }
        )
    );
}

/**
 * Load the initial state.
 *
 * This iterates over the initial tree of category items, and captures the data required for the state from each category.
 * It also captures a count of the number of children in each list.
 *
 * @param {Reactive} reactive
 * @return {Promise<void>}
 */
const loadState = async(reactive) => {

    window.console.log('loading state');

    const tableElement = document.querySelector(SELECTORS.TABLE);

    const stateData = {
        masterchecklists: [{
            id: tableElement.dataset.masterChecklistId,
            name: tableElement.dataset.masterChecklistName,
            mastercategoryorder: tableElement.dataset.masterChecklistCategoryorder,
        }],
        activechecklist: {
            id: tableElement.dataset.masterChecklistId,
        },
        checklistcategories: [],
        checklistitems: [],
        roles: [],
        rooms: [],
        activeRole: {
            id: 0,
        },
        activeRoom: {
            id: 0,
        },
    };
    const checklistCategoryRows = document.querySelectorAll(SELECTORS.ALL_CATEGORY_TABLE_ROWS);
    checklistCategoryRows.forEach(categoryRow => {

        const categoryItemRows = document.querySelectorAll(`tr[data-bookit-checklistitem-categoryid="${categoryRow.dataset.bookitCategoryId}"]`);

        const checklistItems = [];
        categoryItemRows.forEach(itemRow => {
            checklistItems.push(parseInt(itemRow.dataset.bookitChecklistitemId))
        });

        stateData.checklistcategories.push({
            id: categoryRow.dataset.bookitCategoryId,
            name: categoryRow.dataset.bookitCategoryName,
            order: categoryRow.dataset.bookitCategoryOrder,
            items: checklistItems
        });
    });

    const checklistItemRows = document.querySelectorAll(SELECTORS.ALL_ITEM_TABLE_ROWS);
    checklistItemRows.forEach(itemRow => {
        stateData.checklistitems.push({
            id: itemRow.dataset.bookitChecklistitemId,
            title: itemRow.dataset.bookitChecklistitemTitle,
            order: itemRow.dataset.bookitChecklistitemOrder,
            categoryid: itemRow.dataset.bookitChecklistitemCategoryid,
            roomid: itemRow.dataset.bookitChecklistitemRoom,
            roomname: itemRow.dataset.bookitChecklistitemRoomname,
            roleid: itemRow.dataset.bookitChecklistitemRole,
            rolename: itemRow.dataset.bookitChecklistitemRolename,
        });
    });

    // Get all room options
    const roomOptions = document.querySelectorAll(SELECTORS.ALL_ROOM_OPTIONS);
    roomOptions.forEach(roomOption => {
        stateData.rooms.push({
            id: roomOption.value,
            name: roomOption.dataset.bookitRoomname
        });
    });

    // Get all role options
    const roleOptions = document.querySelectorAll(SELECTORS.ALL_ROLE_OPTIONS);
    roleOptions.forEach(roleOption => {
        stateData.roles.push({
            id: roleOption.value,
            name: roleOption.dataset.bookitRolename
        });
    });

    reactive.setInitialState(stateData);
};