// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Sorting and filtering for the "My booked events" table.
 *
 * @module     mod_bookit/overview/my_booked_events_sort
 * @copyright  2026 Humboldt-Universität zu Berlin
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import {setUserPreference} from 'core_user/repository';

/**
 * Initialise sorting and filtering for the overview table.
 *
 * @param {string} tableId
 * @param {string} preferenceKey
 * @param {string} initialColumn
 * @param {string} initialDirection
 */
export const init = (tableId, preferenceKey, initialColumn, initialDirection) => {
    const table = document.getElementById(tableId);

    if (!table) {
        return;
    }

    // Keep the existing text filter.
    $('#bookit-filter').on('keyup', function() {
        const value = $(this).val().toLowerCase();

        $(`#${tableId} tbody tr`).each(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) !== -1);
        });
    });

    let currentColumn = initialColumn;
    let currentDirection = initialDirection;

    /**
     * Sort the rendered table rows.
     *
     * @param {string} column
     * @param {string} direction
     */
    const sortTable = (column, direction) => {
        const header = table.querySelector(
            `thead th[data-sort-key="${column}"]`
        );

        if (!header) {
            return;
        }

        const headers = Array.from(table.querySelectorAll('thead th'));
        const columnIndex = headers.indexOf(header);
        const tbody = table.querySelector('tbody');

        if (!tbody || columnIndex < 0) {
            return;
        }

        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((rowA, rowB) => {
            const cellA = rowA.children[columnIndex];
            const cellB = rowB.children[columnIndex];

            const valueA = cellA.dataset.sort !== undefined
                ? cellA.dataset.sort
                : cellA.textContent.trim().toLowerCase();

            const valueB = cellB.dataset.sort !== undefined
                ? cellB.dataset.sort
                : cellB.textContent.trim().toLowerCase();

            const numberA = Number(valueA);
            const numberB = Number(valueB);
            let result;

            // Compare timestamps and other numeric values numerically.
            if (
                valueA !== ''
                && valueB !== ''
                && !Number.isNaN(numberA)
                && !Number.isNaN(numberB)
            ) {
                result = numberA - numberB;
            } else {
                result = String(valueA).localeCompare(
                    String(valueB),
                    undefined,
                    {numeric: true, sensitivity: 'base'}
                );
            }

            return direction === 'asc' ? result : -result;
        });

        rows.forEach((row) => tbody.appendChild(row));

        // Update the visible sort indicator.
        table.querySelectorAll('thead th[data-sort-key]').forEach((th) => {
            th.querySelector('.bookit-sort-arrow')?.remove();
            th.removeAttribute('aria-sort');
        });

        const arrow = document.createElement('span');
        arrow.className = 'bookit-sort-arrow';
        arrow.textContent = direction === 'asc' ? ' ↑' : ' ↓';
        header.appendChild(arrow);
        header.setAttribute(
            'aria-sort',
            direction === 'asc' ? 'ascending' : 'descending'
        );

        currentColumn = column;
        currentDirection = direction;
    };

    // Make sortable headers clickable and remember the user's choice.
    table.querySelectorAll('thead th[data-sort-key]').forEach((header) => {
        header.style.cursor = 'pointer';

        header.addEventListener('click', () => {
            const column = header.dataset.sortKey;
            const direction = currentColumn === column && currentDirection === 'asc'
                ? 'desc'
                : 'asc';

            sortTable(column, direction);

            setUserPreference(
                preferenceKey,
                JSON.stringify({
                    column,
                    direction,
                })
            ).catch(() => {
                // Sorting still works if the preference cannot be saved.
            });
        });
    });

    // Apply the saved or default order on page load.
    sortTable(currentColumn, currentDirection);
};