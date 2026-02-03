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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

import $ from 'jquery';

/**
 * Frontend-only filter dropdown UI.
 * IMPORTANT: This does NOT change filter logic. It only writes into the existing selects
 * (#filter-room / #filter-faculty / #filter-status) and triggers 'change'.
 */
export const init = () => {
    const root = document.querySelector('.bookit-filterdropdown');
    if (!root) {
        return;
    }

    const roomId = root.getAttribute('data-room-select');
    const facultyId = root.getAttribute('data-faculty-select');
    const statusId = root.getAttribute('data-status-select');

    const selectRoom = document.getElementById(roomId);
    const selectFaculty = document.getElementById(facultyId);
    const selectStatus = document.getElementById(statusId);

    if (!selectRoom || !selectFaculty || !selectStatus) {
        // eslint-disable-next-line no-console
        console.warn('[BookIT] filter_dropdown: Missing select(s)', {roomId, facultyId, statusId});
        return;
    }

    const $toggle = $(root).find('.bookit-filterdropdown-toggle');
    const $panel = $(root).find('.bookit-filterdropdown-panel');
    const $cats = $(root).find('.bookit-filterdropdown-categories');
    const $opts = $(root).find('.bookit-filterdropdown-options');
    const $chipsInButton = $(root).find('.bookit-filterdropdown-chips');
    const $chipsInPanel = $(root).find('.bookit-filterdropdown-activechips');

   const canStatus = root.getAttribute('data-can-status') === '1';
   const model = [
        {key: 'room', select: selectRoom},
        {key: 'faculty', select: selectFaculty},
    ];
    if (canStatus) {
        model.push({key: 'status', select: selectStatus});
    }


    const isOpen = () => !$panel.prop('hidden');
    const open = () => {
        $panel.prop('hidden', false);
        $toggle.attr('aria-expanded', 'true');
    };
    const close = () => {
        $panel.prop('hidden', true);
        $toggle.attr('aria-expanded', 'false');
        $opts.empty();
        $cats.find('.active').removeClass('active');
    };

    const getSelectedLabel = (sel) => {
        const idx = sel.selectedIndex;
        if (idx < 0) {
            return '';
        }
        const opt = sel.options[idx];
        return (opt && opt.value !== '') ? (opt.textContent || '') : '';
    };

const setValueAndTrigger = (sel, value) => {
    sel.value = value;
    // Native event (works regardless of jQuery instances).
    sel.dispatchEvent(new Event('change', {bubbles: true}));
    // Also trigger via the AMD jQuery instance (keeps old behavior if listeners are jQuery-based).
    $(sel).trigger('change');
};

    const renderChips = () => {
        const chips = [];
        model.forEach(({key, select}) => {
            const label = getSelectedLabel(select);
            if (!label) {
                return;
            }
            chips.push({key, label});
        });

        const chipHtml = (chip) => `
            <span class="bookit-filterchip" data-key="${chip.key}">
                <span class="bookit-filterchip-label">${escapeHtml(chip.label)}</span>
                <button type="button" class="bookit-filterchip-remove" aria-label="Remove filter">×</button>
            </span>
        `;

        const html = chips.map(chipHtml).join('');
        $chipsInButton.html(html);
        $chipsInPanel.html(html);
    };

    const buildCategories = () => {
        $cats.empty();
        model.forEach(({key, select}) => {
            // Use the "All ..." option label as category label (no new lang strings required).
            const label = (select.options[0] && select.options[0].textContent) ? select.options[0].textContent : key;

            $cats.append(`
                <button type="button" class="bookit-filtercat" data-key="${key}">
                    ${escapeHtml(label)}
                </button>
            `);
        });
    };

   const buildOptionsFor = (key) => {
        const item = model.find(x => x.key === key);
        if (!item) {
            return;
        }

        const sel = item.select;
        $opts.empty();

        const options = Array.from(sel.options).filter(o => o.value !== '');
        if (!options.length) {
            $opts.html('<div class="bookit-filteropt-empty">No options</div>');
            return;
        }

        options.forEach((o) => {
            const active = (sel.value === o.value) ? ' active' : '';
            $opts.append(`
                <button type="button"
                        class="bookit-filteropt${active}"
                        data-key="${key}"
                        data-value="${escapeAttr(o.value)}">
                    ${escapeHtml(o.textContent || '')}
                </button>
            `);
        });
    };

    // Toggle dropdown.
    $toggle.on('click', (e) => {
        e.preventDefault();
        if (isOpen()) {
            close();
        } else {
            open();
        }
    });

    // Close on outside click.
    $(document).on('mousedown.bookitFilterDropdown', (e) => {
        if (isOpen() && !root.contains(e.target)) {
            close();
        }
    });

    // Close on ESC.
    $(document).on('keydown.bookitFilterDropdown', (e) => {
        if (isOpen() && e.key === 'Escape') {
            close();
        }
    });

    // Category click -> show options.
    $cats.on('click', '.bookit-filtercat', function(e) {
        e.preventDefault();
        const key = $(this).data('key');
        $cats.find('.active').removeClass('active');
        $(this).addClass('active');
        buildOptionsFor(key);
    });

    // Option click -> apply filter via underlying select, then close.
    $opts.on('click', '.bookit-filteropt', function(e) {
        e.preventDefault();

        const key = String(this.getAttribute('data-key') || '');
        const value = String(this.getAttribute('data-value') || '');

        const item = model.find(x => x.key === key);
        if (!item) {
            // eslint-disable-next-line no-console
            console.warn('[BookIT] filter_dropdown: unknown key for option click', key);
            return;
        }

        setValueAndTrigger(item.select, value);
        renderChips();
        close();
    });


    // Chip remove (both in button and in panel).
    $(root).on('click', '.bookit-filterchip-remove', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const key = $(this).closest('.bookit-filterchip').data('key');
        const item = model.find(x => x.key === key);
        if (!item) {
            return;
        }

        setValueAndTrigger(item.select, '');
        renderChips();
    });

    // If selects change (from elsewhere), update chips.
    $(selectRoom).on('change', renderChips);
    $(selectFaculty).on('change', renderChips);
    $(selectStatus).on('change', renderChips);

    buildCategories();
    renderChips();

    // eslint-disable-next-line no-console
    console.log('[BookIT] filter_dropdown initialized (frontend-only)');
};

const escapeHtml = (s) => String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const escapeAttr = (s) => escapeHtml(s);
