// This file is part of Moodle - https://moodle.org/
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
 * Reactive booking status dropdown for the event overview table.
 *
 * Listens for changes on select[data-action="update-booking-status"] and
 * persists the new status via the update_event_booking_status web service.
 *
 * @module     mod_bookit/overview/booking_status_dropdown
 * @copyright  2026 ssystems GmbH <oss@ssystems.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {saveCancelPromise, exception} from 'core/notification';
import {get_strings as getStrings} from 'core/str';

const BOOKING_STATUS_SELECTOR = 'select[data-action="update-booking-status"]';
const RESOURCE_STATUS_SELECTOR = 'select[data-action="update-status"]';
const STATUS_SELECTORS = `${BOOKING_STATUS_SELECTOR}, ${RESOURCE_STATUS_SELECTOR}`;
const CANCEL_OVERVIEW_SELECTOR = 'button[data-action="cancel-booking-from-overview"]';
const REACTIVATE_OVERVIEW_SELECTOR = 'button[data-action="reactivate-booking-from-overview"]';
const REQUEST_COUNT_SELECTOR = '[data-region="request-queue-count"]';
const REQUEST_PAGING_SELECTOR = '[data-region="request-paging"]';
const REQUEST_WORKSPACES = ['openrequests', 'acceptedrequests', 'rejectedrequests'];

/**
 * Resolve the CSS row suffix for a request workspace.
 *
 * @param {string} workspace
 * @returns {string}
 */
const getRequestRowClass = (workspace) => {
    if (workspace === 'rejectedrequests') {
        return 'rejected';
    }
    if (workspace === 'acceptedrequests') {
        return 'accepted';
    }
    return 'open';
};

/**
 * Resolve the table selector for a request workspace.
 *
 * @param {string} workspace
 * @returns {string}
 */
const getRequestTableSelector = (workspace) => {
    if (workspace === 'rejectedrequests') {
        return '#rejected-requests-table';
    }
    if (workspace === 'acceptedrequests') {
        return '#accepted-requests-table';
    }
    return '#open-requests-table';
};

/**
 * Resolve the empty-state message for a request workspace.
 *
 * @param {Object} readConfig
 * @returns {string}
 */
const getRequestEmptyMessage = (readConfig) => {
    if (readConfig.workspace === 'rejectedrequests') {
        return readConfig.rejectedrequestsempty || '';
    }
    if (readConfig.workspace === 'acceptedrequests') {
        return readConfig.acceptedrequestsempty || '';
    }
    return readConfig.openrequestsempty || '';
};

/**
 * Resolve the optional governed read config injected by overview.php.
 *
 * @param {string} workspace
 * @returns {?Object}
 */
const getReadConfig = (workspace) => {
    if (!window.bookitOverviewReadConfig || !window.bookitOverviewReadConfig.methodname) {
        return null;
    }

    return {
        ...window.bookitOverviewReadConfig,
        workspace: workspace,
        page: Number(window.bookitOverviewReadConfig.page || 1),
    };
};

/**
 * Resolve the overview tab that should remain active after a workflow action.
 *
 * @param {HTMLElement} element
 * @returns {string}
 */
const resolveActiveTab = (element) => {
    const datasetTab = element.dataset.tab;
    if (datasetTab) {
        return datasetTab;
    }

    const url = new URL(window.location.href);
    return url.searchParams.get('tab') || 'myevents';
};

/**
 * Apply status colour to a select element by reading data attributes from the selected option.
 *
 * @param {HTMLSelectElement} select
 */
const applyColor = (select) => {
    const opt = select.options[select.selectedIndex];
    const bg = (opt && opt.dataset.bg) ? opt.dataset.bg : '#ffffff';
    const fg = (opt && opt.dataset.fg) ? opt.dataset.fg : '#000000';
    select.style.backgroundColor = bg;
    select.style.color = fg;
    const td = select.closest('td');
    if (td) {
        td.style.backgroundColor = bg;
    }
};

/**
 * Escape text for safe HTML interpolation.
 *
 * @param {string} value
 * @returns {string}
 */
const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
};

/**
 * Build the event title cell.
 *
 * @param {Object} item
 * @param {Object} readConfig
 * @returns {string}
 */
const renderTitleCell = (item, readConfig) => {
    if (!item.actions || !item.actions.caneventdetails) {
        return escapeHtml(item.name);
    }

    const footerMode = item.modalfootermode ? ` data-modal-footer-mode="${escapeHtml(item.modalfootermode)}"` : '';
    return `<a href="#"
        class="bookit-event-link"
        data-eventid="${Number(item.eventid || item.id || 0)}"
        data-cmid="${Number(readConfig.cmid || 0)}"${footerMode}>${escapeHtml(item.name)}</a>`;
};

/**
 * Render the Actions column for rejected-request rows.
 *
 * @param {Object} item
 * @param {Object} readConfig
 * @param {string} workspace
 * @returns {string}
 */
const renderReactivateActionsCell = (item, readConfig, workspace) => {
    if (workspace !== 'rejectedrequests') {
        return '';
    }

    if (item.hasreactivateaction) {
        return `<td class="align-middle"><button type="button"
            class="btn btn-sm btn-outline-primary"
            data-action="reactivate-booking-from-overview"
            data-eventid="${Number(item.eventid || item.id || 0)}"
            data-cmid="${Number(readConfig.cmid || 0)}"
            data-status="${Number(item.reactivatetargetstatus || 0)}"
            data-tab="${escapeHtml(item.overviewtab || workspace)}">${escapeHtml(item.reactivateactionlabel || '')}</button></td>`;
    }

    return '<td class="align-middle">-</td>';
};

/**
 * Render a request-workspace table row from the governed payload.
 *
 * @param {Object} item
 * @param {Object} readConfig
 * @param {string} workspace
 * @returns {string}
 */
const renderRequestRow = (item, readConfig, workspace) => {
    const rowClass = getRequestRowClass(workspace);
    const statusGroupKey = escapeHtml(item.statusgroupkey || 'open');
    const statusCell = item.statuscellhtml || '';

    return `<tr class="mod-bookit-${rowClass}-request-row mod-bookit-status-row mod-bookit-status-row-${statusGroupKey}">
    <td class="align-middle">${Number(item.id || item.eventid || 0)}</td>
    <td class="align-middle">${renderTitleCell(item, readConfig)}</td>
    <td class="align-middle">${escapeHtml(item.room || '-')}</td>
    <td class="align-middle">${escapeHtml(item.personincharge || '-')}</td>
    <td class="align-middle">${escapeHtml(item.myrole || '-')}</td>
    ${statusCell}
    <td class="align-middle" data-sort="${Number(item.starttime || 0)}">${escapeHtml(item.datestr || '')}</td>
    ${renderReactivateActionsCell(item, readConfig, workspace)}
</tr>`;
};

/**
 * Apply dropdown colours after SSR or AJAX row render.
 *
 * @param {HTMLElement} root
 */
const applyColorsIn = (root) => {
    root.querySelectorAll(STATUS_SELECTORS).forEach((select) => {
        applyColor(select);
    });
};

/**
 * Update the visible queue count text.
 *
 * @param {Object} queueResponse
 */
const updateVisibleQueueCount = (queueResponse) => {
    const countNode = document.querySelector(REQUEST_COUNT_SELECTOR);
    if (countNode && queueResponse.summary) {
        countNode.textContent = queueResponse.summary.workspacecounttext || '';
    }
};

/**
 * Update the paging markup and browser URL for the current request workspace.
 *
 * @param {Object} readConfig
 * @param {Object} queueResponse
 */
const syncPaging = (readConfig, queueResponse) => {
    const pagingNode = document.querySelector(REQUEST_PAGING_SELECTOR);
    const pagingHtml = queueResponse.fragments ? (queueResponse.fragments.paginghtml || '') : '';
    if (pagingNode) {
        if (pagingHtml) {
            pagingNode.innerHTML = pagingHtml;
        } else {
            pagingNode.remove();
        }
    } else if (pagingHtml) {
        const workspaceSection = document.querySelector('.mod_bookit-overview-examiner_overview');
        if (workspaceSection) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mod-bookit-request-pagination mt-3';
            wrapper.dataset.region = 'request-paging';
            wrapper.innerHTML = pagingHtml;
            workspaceSection.appendChild(wrapper);
        }
    }

    if (queueResponse.paging) {
        readConfig.page = Number(queueResponse.paging.currentpage || 1);
        if (window.bookitOverviewReadConfig) {
            window.bookitOverviewReadConfig.page = readConfig.page;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('queuepage', String(readConfig.page));
        window.history.replaceState({}, '', url);
    }
};

/**
 * Re-render the active request table or empty state.
 *
 * @param {Object} readConfig
 * @param {Object} queueResponse
 */
const renderQueueState = (readConfig, queueResponse) => {
    const tableSelector = getRequestTableSelector(readConfig.workspace);
    const table = document.querySelector(tableSelector);
    const emptyMessage = getRequestEmptyMessage(readConfig);

    updateVisibleQueueCount(queueResponse);
    syncPaging(readConfig, queueResponse);

    if (table && queueResponse.items && queueResponse.items.length) {
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = queueResponse.items.map((item) => renderRequestRow(item, readConfig, readConfig.workspace)).join('');
        applyColorsIn(tbody);
        return;
    }

    const alertMarkup = `<div class="alert alert-info mb-0">${escapeHtml(emptyMessage)}</div>`;
    if (table) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = alertMarkup;
        table.replaceWith(wrapper.firstElementChild);
        return;
    }

    const existingAlert = document.querySelector('.mod_bookit-overview-examiner_overview .alert.alert-info');
    if (existingAlert) {
        existingAlert.textContent = emptyMessage;
    }
};

/**
 * Update overview queue counters and rows after a workflow action.
 *
 * @param {Object} readConfig
 * @param {?Object} initialQueueResponse
 * @param {string} redirectUrl
 * @returns {Promise<void>}
 */
const refreshQueueFromGovernedRead = (readConfig, initialQueueResponse, redirectUrl) => {
    if (!readConfig || !REQUEST_WORKSPACES.includes(readConfig.workspace)) {
        window.location.assign(redirectUrl || window.location.href);
        return Promise.resolve();
    }

    if (initialQueueResponse) {
        renderQueueState(readConfig, initialQueueResponse);
        return Promise.resolve();
    }

    return Ajax.call([{
        methodname: readConfig.methodname,
        args: {
            cmid: readConfig.cmid,
            workspace: readConfig.workspace,
            bookingstatuses: readConfig.bookingstatuses || [],
            facultyids: readConfig.facultyids || [],
            semesterids: readConfig.semesterids || [],
            page: readConfig.page || 1,
            reportstart: readConfig.reportstart || '',
            reportend: readConfig.reportend || '',
        },
    }])[0].then((queueResponse) => {
        renderQueueState(readConfig, queueResponse);
        return null;
    }).catch(() => {
        window.location.assign(redirectUrl || window.location.href);
        return null;
    });
};

/**
 * Persist a resource status change from the event resources table.
 *
 * @param {HTMLSelectElement} select
 */
const handleResourceStatusChange = (select) => {
    const cmid = parseInt(select.dataset.cmid, 10);
    const eventid = parseInt(select.dataset.eventid, 10);
    const row = select.closest('[data-region="event-resources-checklist-item-row"]');
    const resourceid = row ? parseInt(row.dataset.itemResourceid, 10) : 0;
    const status = select.value;
    const previousstatus = row ? (row.dataset.itemStatus || status) : status;

    select.disabled = true;

    Ajax.call([{
        methodname: 'mod_bookit_update_event_resource_status',
        args: {cmid, eventid, resourceid, status},
    }])[0]
    .then(() => {
        applyColor(select);
        select.disabled = false;
        if (row) {
            row.dataset.itemStatus = status;
            row.dispatchEvent(new CustomEvent('bookit-resource-status-saved', {
                bubbles: true,
                detail: {
                    status,
                    itemid: parseInt(row.dataset.itemid, 10),
                },
            }));
        }
        return null;
    })
    .catch((err) => {
        select.value = previousstatus;
        if (row) {
            row.dataset.itemStatus = previousstatus;
        }
        applyColor(select);
        select.disabled = false;
        exception(err);
    });
};

/**
 * Initialise the dropdown listener on the overview table.
 */
export const init = () => {
    applyColorsIn(document);

    const resourcestable = document.getElementById('event-resources-checklist-table');
    if (resourcestable) {
        applyColorsIn(resourcestable);
    }

    document.addEventListener('change', (e) => {
        const resourceselect = e.target.closest(RESOURCE_STATUS_SELECTOR);
        if (resourceselect) {
            handleResourceStatusChange(resourceselect);
            return;
        }

        const select = e.target.closest(BOOKING_STATUS_SELECTOR);
        if (!select) {
            return;
        }

        const cmid = parseInt(select.dataset.cmid, 10);
        const eventid = parseInt(select.dataset.eventid, 10);
        const status = parseInt(select.value, 10);
        const tab = resolveActiveTab(select);

        select.disabled = true;

        Ajax.call([{
            methodname: 'mod_bookit_update_event_booking_status',
            args: {cmid, eventid, status, tab, page: Number((getReadConfig(tab) || {}).page || 1)},
        }])[0]
        .then((response) => {
            applyColor(select);
            if (REQUEST_WORKSPACES.includes(tab)) {
                window.location.assign(window.location.href);
                return null;
            }
            return refreshQueueFromGovernedRead(
                getReadConfig(tab),
                response.queue || null,
                response.redirecturl || window.location.href
            );
        })
        .catch((err) => {
            select.disabled = false;
            exception(err);
        });
    });

    document.addEventListener('click', (e) => {
        const button = e.target.closest(CANCEL_OVERVIEW_SELECTOR);
        if (!button) {
            return;
        }

        e.preventDefault();

        const cmid = parseInt(button.dataset.cmid, 10);
        const eventid = parseInt(button.dataset.eventid, 10);
        const status = parseInt(button.dataset.status, 10);
        const tab = resolveActiveTab(button);

        getStrings([
            {key: 'overview_cancel_booking_confirm', component: 'mod_bookit'},
            {key: 'overview_cancel_booking_confirm_body', component: 'mod_bookit'},
            {key: 'overview_cancel_booking', component: 'mod_bookit'},
        ]).then((strings) => saveCancelPromise(strings[0], strings[1], strings[2], {triggerElement: button}))
        .then(() => {
            button.disabled = true;

            return Ajax.call([{
                methodname: 'mod_bookit_update_event_booking_status',
                args: {cmid, eventid, status, tab, page: Number((getReadConfig(tab) || {}).page || 1)},
            }])[0];
        })
        .then((response) => refreshQueueFromGovernedRead(
            getReadConfig(tab),
            response.queue || null,
            response.redirecturl || window.location.href
        ))
        .catch((err) => {
            button.disabled = false;
            // User dismissed the confirmation dialog — not an error (see mod_quiz/submission_confirmation).
            if (!err || err.type === 'modal-save-cancel:cancel') {
                return null;
            }
            exception(err);
            return null;
        });
    });

    document.addEventListener('click', (e) => {
        const button = e.target.closest(REACTIVATE_OVERVIEW_SELECTOR);
        if (!button) {
            return;
        }

        e.preventDefault();

        const cmid = parseInt(button.dataset.cmid, 10);
        const eventid = parseInt(button.dataset.eventid, 10);
        const status = parseInt(button.dataset.status, 10);
        const tab = resolveActiveTab(button);

        getStrings([
            {key: 'overview_reactivate_booking_confirm', component: 'mod_bookit'},
            {key: 'overview_reactivate_booking_confirm_body', component: 'mod_bookit'},
            {key: 'bookingstatus_action_reactivate', component: 'mod_bookit'},
        ]).then((strings) => saveCancelPromise(strings[0], strings[1], strings[2], {triggerElement: button}))
        .then(() => {
            button.disabled = true;

            return Ajax.call([{
                methodname: 'mod_bookit_update_event_booking_status',
                args: {cmid, eventid, status, tab, page: Number((getReadConfig(tab) || {}).page || 1)},
            }])[0];
        })
        .then((response) => refreshQueueFromGovernedRead(
            getReadConfig(tab),
            response.queue || null,
            response.redirecturl || window.location.href
        ))
        .catch((err) => {
            button.disabled = false;
            if (!err || err.type === 'modal-save-cancel:cancel') {
                return null;
            }
            exception(err);
            return null;
        });
    });
};
