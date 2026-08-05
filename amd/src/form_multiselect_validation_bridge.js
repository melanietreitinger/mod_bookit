// This file is part of Moodle - http://moodle.org/
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
 * Bridge Moodle multi-select client validation from hidden submission marker to visible select.
 *
 * @module     mod_bookit/form_multiselect_validation_bridge
 * @copyright  2026 SSYSTEMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {eventTypes, notifyFieldValidationFailure} from 'core_form/events';

const BRIDGE_ATTR = 'data-bookit-ms-bridge';
const HIDDEN_MARKER = '_qf__force_multiselect_submission';

/**
 * Remove legacy QuickForm error markup if present.
 *
 * @param {string} fieldName
 * @param {HTMLElement|null} felement
 */
const removeLegacyErrorSpan = (fieldName, felement) => {
    const legacySpan = document.getElementById('id_error_' + fieldName);
    if (legacySpan) {
        legacySpan.remove();
    }
    const legacyBreak = document.getElementById('id_error_break_' + fieldName);
    if (legacyBreak) {
        legacyBreak.remove();
    }
    if (felement) {
        felement.className = felement.className.replace(/\berror\b/g, '').trim();
    }
};

/**
 * Wire one hidden multi-select marker input to its visible select sibling.
 *
 * @param {HTMLInputElement} hiddenInput
 */
const bridgeHiddenMultiselect = (hiddenInput) => {
    if (hiddenInput.getAttribute(BRIDGE_ATTR) === '1') {
        return;
    }

    const fieldName = hiddenInput.name;
    if (!fieldName) {
        return;
    }

    const felement = hiddenInput.closest('.felement');
    const select = felement?.querySelector(`select[name="${fieldName}[]"][multiple]`);
    if (!select) {
        return;
    }

    hiddenInput.addEventListener(eventTypes.formFieldValidationFailed, (e) => {
        removeLegacyErrorSpan(fieldName, felement);
        const forwarded = notifyFieldValidationFailure(select, e.detail.message);
        if (forwarded.defaultPrevented) {
            e.preventDefault();
        }
    });

    hiddenInput.setAttribute(BRIDGE_ATTR, '1');
};

/**
 * Enhance a form so multi-select validation errors render on the visible select.
 *
 * @param {HTMLFormElement|null} formElement
 */
export const enhanceForm = (formElement) => {
    if (!formElement) {
        return;
    }

    formElement.querySelectorAll(`input[type="hidden"][value="${HIDDEN_MARKER}"]`).forEach(bridgeHiddenMultiselect);
};

export default {
    enhanceForm,
};
