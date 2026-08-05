// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Synchronise Request Workspace dates with the selected semesters.
 *
 * @module     mod_bookit/overview/semester_date_sync
 * @copyright  2026 ssystems GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SEMESTER_SELECTOR = '[data-action="sync-semester-dates"]';
const START_SELECTOR = '[data-region="report-start"]';
const END_SELECTOR = '[data-region="report-end"]';

/**
 * Convert a semester ID into ISO start and end dates.
 *
 * @param {number} semesterId
 * @returns {{start: string, end: string}|null}
 */
const getSemesterRange = (semesterId) => {
    if (!Number.isInteger(semesterId) || semesterId <= 0) {
        return null;
    }

    const year = Math.floor(semesterId / 10);
    const term = semesterId % 10;

    if (term === 1) {
        return {
            start: `${year}-04-01`,
            end: `${year}-09-30`,
        };
    }

    if (term === 2) {
        return {
            start: `${year}-10-01`,
            end: `${year + 1}-03-31`,
        };
    }

    return null;
};

/**
 * Update the date fields belonging to a semester select.
 *
 * @param {HTMLSelectElement} select
 */
const synchroniseDates = (select) => {
    const form = select.closest('[data-region="overview-filter-form"]');

    if (!form) {
        return;
    }

    const ranges = Array.from(select.selectedOptions)
        .map((option) => getSemesterRange(Number.parseInt(option.value, 10)))
        .filter((range) => range !== null);

    if (ranges.length === 0) {
        return;
    }

    let earliestStart = ranges[0].start;
    let latestEnd = ranges[0].end;

    ranges.forEach((range) => {
        if (range.start < earliestStart) {
            earliestStart = range.start;
        }

        if (range.end > latestEnd) {
            latestEnd = range.end;
        }
    });

    const startInput = form.querySelector(START_SELECTOR);
    const endInput = form.querySelector(END_SELECTOR);

    if (startInput) {
        startInput.value = earliestStart;
    }

    if (endInput) {
        endInput.value = latestEnd;
    }
};

/**
 * Initialise semester/date synchronisation.
 */
export const init = () => {
    document.querySelectorAll(SEMESTER_SELECTOR).forEach((select) => {
        if (select.dataset.semesterDateSyncInitialised === '1') {
            return;
        }

        select.dataset.semesterDateSyncInitialised = '1';
        select.addEventListener('change', () => synchroniseDates(select));
    });
};