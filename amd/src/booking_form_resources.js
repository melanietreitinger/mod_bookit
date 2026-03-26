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
 * Room-based resource filtering for booking form.
 *
 * @module     mod_bookit/booking_form_resources
 * @copyright  2026 ssystems GmbH <oss@ssystems.de>
 * @author     Andreas Rosenthal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/notification', 'core/str'], function(Notification, Str) {
    'use strict';

    /**
     * Get the row container for a resource checkbox element.
     *
     * @param {Element} groupElement The resource checkbox element
     * @return {Element} The fitem row div or the element itself as fallback
     */
    function getResourceRow(groupElement) {
        return groupElement.closest('div[id*="fgroup_id_"]') || groupElement;
    }

    /**
     * Show or clear an inline amount error below the given amount input.
     *
     * @param {Element} input The amount text input
     * @param {string|null} message Error message, or null to clear
     */
    function setAmountError(input, message) {
        const errorId = input.name + '_amount_error';
        let errorEl = input.parentElement.querySelector('[data-amount-error]');
        if (message) {
            if (!errorEl) {
                errorEl = document.createElement('span');
                errorEl.dataset.amountError = '1';
                errorEl.className = 'text-danger d-block small';
                input.after(errorEl);
            }
            errorEl.textContent = message;
            input.setAttribute('aria-describedby', errorId);
        } else if (errorEl) {
            errorEl.remove();
            input.removeAttribute('aria-describedby');
        }
    }

    /**
     * Validate the amount input against its data-resource-max attribute.
     *
     * @param {Element} input The amount text input
     * @param {string} msgTooLow Localised "too low" message
     * @param {string} msgTooHigh Localised "too high" message template
     */
    function validateAmountInput(input, msgTooLow, msgTooHigh) {
        const maxAttr = input.getAttribute('data-resource-max');
        if (maxAttr === null) {
            return;
        }
        const max = parseInt(maxAttr, 10);
        const val = parseInt(input.value, 10);

        if (isNaN(val) || val < 1) {
            setAmountError(input, msgTooLow);
        } else if (max > 0 && val > max) {
            setAmountError(input, msgTooHigh.replace('{requested}', val).replace('{available}', max));
        } else {
            setAmountError(input, null);
        }
    }

    /**
     * Attach amount validation listeners to all resource amount inputs in root.
     *
     * @param {Element} modalRoot The modal root element
     */
    async function initAmountValidation(modalRoot) {
        const amountInputs = modalRoot.querySelectorAll('input[data-resource-max]');
        if (!amountInputs.length) {
            return;
        }

        const [msgTooLow, msgTooHigh] = await Promise.all([
            Str.get_string('booking:resource_amount_too_low', 'mod_bookit'),
            Str.get_string('booking:resource_amount_invalid', 'mod_bookit',
                {requested: '{requested}', available: '{available}'}),
        ]);

        amountInputs.forEach(input => {
            input.addEventListener('input', () => {
                validateAmountInput(input, msgTooLow, msgTooHigh);
            });

            // Clear error when checkbox is unchecked (amount field gets disabled).
            const name = input.name; // Format: resource_[id]
            const id = name.replace('resource_', '');
            const checkbox = modalRoot.querySelector('input[name="checkbox_' + id + '"]');
            if (checkbox) {
                checkbox.addEventListener('change', () => {
                    if (!checkbox.checked) {
                        setAmountError(input, null);
                    }
                });
            }
        });
    }

    /**
     * Enable a resource group (room is available).
     *
     * Only re-enables the controlling checkbox; dependent fields remain
     * governed by MoodleQuickForm's disabledIf logic.
     *
     * @param {Element} groupElement The resource checkbox element
     */
    function enableResource(groupElement) {
        const row = getResourceRow(groupElement);
        row.classList.remove('bookit-resource-disabled');
        if (groupElement && groupElement.tagName === 'INPUT') {
            groupElement.disabled = false;
        } else {
            const checkbox = row.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.disabled = false;
            }
        }
    }

    /**
     * Disable and grey out a resource group (room not available).
     *
     * @param {Element} groupElement The resource checkbox element
     */
    function disableResource(groupElement) {
        const row = getResourceRow(groupElement);
        row.classList.add('bookit-resource-disabled');
        row.querySelectorAll('input').forEach(input => {
            input.disabled = true;
            if (input.type === 'checkbox') {
                input.checked = false;
            }
        });
    }

    /**
     * Update visibility of resource category groups based on their content.
     *
     * @param {Element} modalRoot The modal root element
     */
    function updateCategoryVisibility(modalRoot) {
        if (!modalRoot) {
            return;
        }

        // Categories are always visible - resources within are enabled or disabled.
        const categoryGroups = modalRoot.querySelectorAll('fieldset[id*="id_header_cat_"]');
        categoryGroups.forEach(fieldset => {
            fieldset.style.display = '';
        });
    }

    /**
     * Check for resource-room conflicts and show alert if any, then filter.
     *
     * Scenario 2: If resources are already checked and the newly selected room
     * does not support all of them, show a Moodle alert notification.
     *
     * @param {Element} modalRoot The modal root element
     * @param {string} selectedRoomId The newly selected room ID
     * @param {NodeList} resourceGroups All resource group elements
     */
    async function checkConflictAndFilter(modalRoot, selectedRoomId, resourceGroups) {
        const roomId = parseInt(selectedRoomId, 10);

        // Collect all currently checked resource checkboxes.
        const checkedGroups = Array.from(resourceGroups).filter(group => {
            const checkbox = group;
            return checkbox.type === 'checkbox' && checkbox.checked;
        });

        // Check if any checked resource is not available in the new room.
        // null roomids means available in all rooms (no conflict).
        const hasConflict = checkedGroups.some(group => {
            const roomsJson = group.getAttribute('data-resource-rooms');
            if (!roomsJson) {
                return false;
            }
            try {
                const rooms = JSON.parse(roomsJson);
                if (rooms === null) {
                    return false; // Null = available in all rooms, no conflict.
                }
                if (!Array.isArray(rooms)) {
                    return false;
                }
                return !rooms.includes(roomId);
            } catch (e) {
                return false;
            }
        });

        if (hasConflict) {
            const title = await Str.get_string('error', 'core');
            const message = await Str.get_string('booking:resource_room_conflict', 'mod_bookit');
            await Notification.alert(title, message);
        }

        filterResourcesByRoom(modalRoot, selectedRoomId);
    }

    /**
     * Filter resources based on selected room.
     *
     * @param {Element} modalRoot The modal root element
     * @param {number|string} selectedRoomId The selected room ID
     */
    function filterResourcesByRoom(modalRoot, selectedRoomId) {
        if (!modalRoot) {
            return;
        }

        const roomId = parseInt(selectedRoomId, 10);

        // Find all resource groups with data-resource-rooms attribute.
        const resourceGroups = modalRoot.querySelectorAll('[data-resource-rooms]');

        resourceGroups.forEach(group => {
            const roomsJson = group.getAttribute('data-resource-rooms');
            try {
                const rooms = JSON.parse(roomsJson);
                // Null = available in all rooms (always enable).
                // Array with room IDs = available only in those rooms.
                const isAvailable = rooms === null || (Array.isArray(rooms) && rooms.includes(roomId));

                if (isAvailable) {
                    enableResource(group);
                } else {
                    disableResource(group);
                }
            } catch (e) {
                disableResource(group);
            }
        });

        // After filtering individual resources, update category visibility.
        updateCategoryVisibility(modalRoot);
    }

    /**
     * Detect overflowing room badges and replace them with a +N tooltip badge.
     *
     * The .bookit-resource-rooms span has a fixed max-width. Badges that exceed it
     * are hidden and replaced with a "+N" badge. The filter logic reads
     * data-resource-rooms on the checkbox — not the displayed badges — so filtering
     * is unaffected by hiding badges here.
     *
     * @param {Element} modalRoot The modal root element containing the form
     */
    function initRoomBadgeOverflow(modalRoot) {
        const roomSpans = modalRoot.querySelectorAll('.bookit-resource-rooms');
        roomSpans.forEach(span => {
            const badges = Array.from(span.querySelectorAll('.badge'));
            if (badges.length === 0) {
                return;
            }

            const spanRight = span.getBoundingClientRect().right;
            if (spanRight === 0) {
                return; // Not yet rendered.
            }

            // Leave 8px breathing room so badges don't press against the input element.
            const overflowThreshold = spanRight - 8;

            let firstOverflowIdx = -1;
            for (let i = 0; i < badges.length; i++) {
                if (badges[i].getBoundingClientRect().right > overflowThreshold) {
                    firstOverflowIdx = i;
                    break;
                }
            }

            if (firstOverflowIdx >= 0) {
                const overflowCount = badges.length - firstOverflowIdx;
                const hiddenNames = badges.slice(firstOverflowIdx)
                    .map(b => b.title || b.textContent.trim()).join(', ');
                for (let i = firstOverflowIdx; i < badges.length; i++) {
                    badges[i].style.display = 'none';
                }
                const overflow = document.createElement('span');
                overflow.className = 'badge badge-light text-muted ms-1';
                overflow.setAttribute('data-bs-toggle', 'tooltip');
                overflow.setAttribute('data-bs-placement', 'top');
                overflow.title = hiddenNames;
                overflow.textContent = '+' + overflowCount;
                span.appendChild(overflow);
            }
        });
    }

    /**
     * Main entry point called by edit_event_form.php.
     *
     * @param {Element} modalRoot The modal root element containing the form
     */
    function init(modalRoot) {
        if (!modalRoot) {
            return;
        }

        // Find the room select element; form body may not be rendered yet — retry.
        const roomSelect = modalRoot.querySelector('select[name="roomid"]');
        if (!roomSelect) {
            setTimeout(() => init(modalRoot), 50);
            return;
        }

        // Collect all resource groups.
        const resourceGroups = modalRoot.querySelectorAll('[data-resource-rooms]');

        // If no room is selected yet, disable room-restricted resources; null-roomids stay enabled.
        if (!roomSelect.value) {
            resourceGroups.forEach(group => {
                const roomsJson = group.getAttribute('data-resource-rooms');
                try {
                    const rooms = JSON.parse(roomsJson);
                    if (rooms !== null) {
                        disableResource(group);
                    }
                } catch (e) {
                    disableResource(group);
                }
            });
            updateCategoryVisibility(modalRoot);
        }

        // Listen for room changes.
        roomSelect.addEventListener('change', async function() {
            const selectedRoomId = this.value;

            if (selectedRoomId) {
                await checkConflictAndFilter(modalRoot, selectedRoomId, resourceGroups);
            } else {
                // No room selected - disable room-restricted resources; null-roomids stay enabled.
                resourceGroups.forEach(group => {
                    const roomsJson = group.getAttribute('data-resource-rooms');
                    try {
                        const rooms = JSON.parse(roomsJson);
                        if (rooms !== null) {
                            disableResource(group);
                        }
                    } catch (e) {
                        disableResource(group);
                    }
                });
                updateCategoryVisibility(modalRoot);
            }
        });

        // If a room is already selected, filter immediately without wiping existing selections.
        if (roomSelect.value) {
            filterResourcesByRoom(modalRoot, roomSelect.value);
        }

        // Initialize client-side amount validation.
        initAmountValidation(modalRoot);

        // After layout settles (modal fade-in completes), detect badge overflow and apply +N badge.
        setTimeout(() => initRoomBadgeOverflow(modalRoot), 300);
    }

    return {
        init: init
    };
});
