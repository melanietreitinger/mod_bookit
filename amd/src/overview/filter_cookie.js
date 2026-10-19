// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Persist overview filter settings in a cookie.
 *
 * @module     mod_bookit/overview/filter_cookie
 * @copyright   2026 Vadym Kuzyak, Humboldt Universität Berlin
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


const FILTER_NAMES = [
    'reportstart',
    'reportend',
    'bookingstatusfilter[]',
    'facultyid',
    'semesterids[]',
    'assignmentfilter',
];

// Keep filter preferences persistent across Moodle logouts.
const COOKIE_MAX_AGE = 365 * 24 * 60 * 60; // Max age is 1 year.

/**
 * Write one persistent cookie.
 *
 * @param {string} name
 * @param {Object} value
 */
const setCookie = (name, value) => {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${name}=${encodeURIComponent(JSON.stringify(value))}` +
        `; Max-Age=${COOKIE_MAX_AGE}; Path=/; SameSite=Lax${secure}`;
};

/**
 * Delete one filter cookie.
 *
 * @param {string} name
 */
const deleteCookie = (name) => {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax${secure}`;
};

/**
 * Store the currently applied filter form values.
 *
 * @param {HTMLFormElement} form
 * @param {string} cookieName
 */
const storeFilters = (form, cookieName) => {
    const formData = new FormData(form);
    const filters = {};
    FILTER_NAMES.forEach((name) => {
        if (form.elements.namedItem(name)) {
            const values = formData
                .getAll(name)
                .map((value) => String(value));

            if (values.length > 0) {
                filters[name] = values;
            }
        }
    });
    setCookie(cookieName, filters);
};

/**
 * Initialise persistent overview filters.
 *
 * @param {string} cookieName
 */
export const init = (cookieName) => {
    const form = document.querySelector(
        '[data-region="overview-filter-form"]'
    );

    if (!form || !cookieName) {
        return;
    }

    form.addEventListener('submit', () => {
        storeFilters(form, cookieName);
    });

    form.querySelector(
        '[data-action="reset-report-filters"]'
    )?.addEventListener('click', () => {
        deleteCookie(cookieName);
    });
};
