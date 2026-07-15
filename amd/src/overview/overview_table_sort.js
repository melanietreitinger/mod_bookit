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
 * Overview table column sorting with persisted user preference.
 *
 * @module     mod_bookit/overview/overview_table_sort
 * @copyright  2026 ssystems GmbH <oss@ssystems.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import UserRepository from 'core_user/repository';

const NUMERIC_COLUMNS = new Set(['id', 'starttime']);

/** @type {{column: string, direction: string}} */
let currentState = {
    column: 'starttime',
    direction: 'desc',
};

/**
 * Extract a comparable value from a table cell.
 *
 * @param {HTMLTableCellElement|null} cell
 * @param {string} column
 * @returns {number|string}
 */
const getCellSortValue = (cell, column) => {
    if (!cell) {
        return '';
    }

    if (cell.dataset.sort !== undefined && cell.dataset.sort !== '') {
        const numeric = Number(cell.dataset.sort);
        if (!Number.isNaN(numeric)) {
            return numeric;
        }
    }

    if (NUMERIC_COLUMNS.has(column)) {
        const numeric = Number(cell.textContent.trim());
        if (!Number.isNaN(numeric)) {
            return numeric;
        }
    }

    return cell.textContent.trim().toLowerCase();
};

/**
 * Compare two cell values for sorting.
 *
 * @param {number|string} a
 * @param {number|string} b
 * @param {string} column
 * @param {string} direction
 * @returns {number}
 */
const compareValues = (a, b, column, direction) => {
    const multiplier = direction === 'asc' ? 1 : -1;

    if (typeof a === 'number' && typeof b === 'number') {
        return (a - b) * multiplier;
    }

    if (NUMERIC_COLUMNS.has(column)) {
        const na = Number(a);
        const nb = Number(b);
        if (!Number.isNaN(na) && !Number.isNaN(nb)) {
            return (na - nb) * multiplier;
        }
    }

    return String(a).localeCompare(String(b), undefined, {numeric: true, sensitivity: 'base'}) * multiplier;
};

/**
 * Find the header index for a sort key.
 *
 * @param {HTMLTableElement} table
 * @param {string} sortkey
 * @returns {number}
 */
const findColumnIndex = (table, sortkey) => {
    const headers = Array.from(table.querySelectorAll('thead th'));
    return headers.findIndex((th) => th.dataset.sortKey === sortkey);
};

/**
 * Update visible sort arrows on sortable headers.
 *
 * @param {HTMLTableElement} table
 * @param {string} activecolumn
 * @param {string} direction
 */
const updateSortIndicators = (table, activecolumn, direction) => {
    table.querySelectorAll('thead th[data-sort-key]').forEach((th) => {
        let arrow = th.querySelector('.sortarrow');
        if (!arrow) {
            arrow = document.createElement('span');
            arrow.className = 'sortarrow';
            th.appendChild(arrow);
        }

        if (th.dataset.sortKey === activecolumn) {
            arrow.textContent = direction === 'asc' ? ' ▲' : ' ▼';
        } else {
            arrow.textContent = '';
        }
    });
};

/**
 * Sort table body rows by column and direction.
 *
 * @param {HTMLTableElement} table
 * @param {string} column
 * @param {string} direction
 */
const sortTable = (table, column, direction) => {
    const colindex = findColumnIndex(table, column);
    if (colindex < 0) {
        return;
    }

    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((rowa, rowb) => {
        const cella = rowa.children[colindex];
        const cellb = rowb.children[colindex];
        const vala = getCellSortValue(cella, column);
        const valb = getCellSortValue(cellb, column);
        return compareValues(vala, valb, column, direction);
    });

    rows.forEach((row) => tbody.appendChild(row));
    currentState = {column, direction};
    updateSortIndicators(table, column, direction);
};

/**
 * Persist sort preference for the current user.
 *
 * @param {string} preferencekey
 * @param {string} column
 * @param {string} direction
 */
const savePreference = (preferencekey, column, direction) => {
    const payload = JSON.stringify({column, direction});
    UserRepository.setUserPreference(preferencekey, payload).catch(() => {
        // Preference persistence is best-effort; sorting still applies for this view.
    });
};

/**
 * Attach click handlers to sortable headers.
 *
 * @param {HTMLTableElement} table
 * @param {{preferenceKey?: string}} config
 */
const attachHeaderHandlers = (table, config) => {
    table.querySelectorAll('thead th[data-sort-key]').forEach((th) => {
        th.style.cursor = 'pointer';
        if (!th.querySelector('.sortarrow')) {
            const span = document.createElement('span');
            span.className = 'sortarrow';
            th.appendChild(span);
        }

        th.addEventListener('click', () => {
            const sortkey = th.dataset.sortKey;
            let direction = 'asc';
            if (currentState.column === sortkey) {
                direction = currentState.direction === 'asc' ? 'desc' : 'asc';
            }
            sortTable(table, sortkey, direction);
            savePreference(config.preferenceKey || 'mod_bookit_overview_sort', sortkey, direction);
        });
    });
};

/**
 * Resolve initial sort state from page config.
 *
 * @param {{initialPreference?: {column?: string, direction?: string}, defaultColumn?: string, defaultDirection?: string}} config
 * @returns {{column: string, direction: string}}
 */
const resolveInitialPreference = (config) => {
    const pref = config.initialPreference || {};
    return {
        column: pref.column || config.defaultColumn || 'starttime',
        direction: pref.direction || config.defaultDirection || 'desc',
    };
};

/**
 * Re-apply the current sort state after dynamic row updates.
 *
 * @param {HTMLTableElement|null} table
 */
export const reapply = (table) => {
    if (!table) {
        return;
    }
    sortTable(table, currentState.column, currentState.direction);
};

/**
 * Initialise overview table sorting.
 *
 * @param {Object} config Sort module configuration.
 */
export const init = (config = {}) => {
    const tableid = config.tableId || 'overview-table';
    const table = document.getElementById(tableid);
    if (!table) {
        return;
    }

    attachHeaderHandlers(table, config);

    let {column, direction} = resolveInitialPreference(config);
    if (findColumnIndex(table, column) < 0) {
        column = config.defaultColumn || 'starttime';
        direction = config.defaultDirection || 'desc';
    }

    sortTable(table, column, direction);
};
