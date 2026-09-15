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
 * Fresh possible slots in the booking form.
 *
 * @module     mod_bookit/possible_slots_refresh
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';
import {getString} from 'core/str';
import {prefetchStrings} from 'core/prefetch';

/**
 * Initializes the calendar.
 * @param {int} cmId
 * @param {?int} exceptEventId
 */
export function initPossibleStarttimesRefresh(cmId, exceptEventId = null) {
    const formEl = document.querySelector('.modal-body form');
    if (!formEl) {
        setTimeout(initPossibleStarttimesRefresh, 50, cmId, exceptEventId);
        return;
    }

    void prefetchStrings('mod_bookit', [
        'no_slot_available',
        'no_weekplan_defined',
        'event_error_weekplan_before',
        'event_error_weekplan_after',
        'event_error_weekplan_both',
    ]);
    const roomEl = formEl.querySelector('select[name="roomid"]');
    const durationEl = formEl.querySelector('select[name="duration"]');
    const dateDayEl = formEl.querySelector('select[name="startdate[day]"]');
    const dateMonthEl = formEl.querySelector('select[name="startdate[month]"]');
    const dateYearEl = formEl.querySelector('select[name="startdate[year]"]');
    const extraBeforeEl = formEl.querySelector('input[name="extratimebefore"]');
    const extraAfterEl = formEl.querySelector('input[name="extratimeafter"]');

    const timeEl = formEl.querySelector('select[name="starttime"]');

    const starttimeEl = document.querySelector('.fitem:has(select[name="starttime"])');
    const starttimeExplanationEl = document.querySelector('.fitem:has(.form-control-static[data-name="starttime_explanation"])');
    const starttimeExplanationTextEl = starttimeExplanationEl.querySelector(
        '.form-control-static[data-name="starttime_explanation"]'
    );

    const updateWeekplanValidation = async(
        beforeoutside,
        afteroutside,
        currentSelectionValue,
        parsedExtraBefore,
        parsedExtraAfter
    ) => {
        const outsideWeekplan = beforeoutside || afteroutside;

        const errorId = 'bookit-extra-time-weekplan-error';
        const modalEl = formEl.closest('.modal');
        const saveButton = modalEl
            ?.querySelector('.modal-footer [data-action="save"]');

        modalEl?.querySelector('#' + errorId)?.remove();

        if (saveButton) {
            saveButton.hidden = outsideWeekplan;
        }

        if (!outsideWeekplan || !currentSelectionValue) {
            return;
        }

        let before = parsedExtraBefore;
        if (Number.isNaN(before)) {
            before = 0;
        }

        let after = parsedExtraAfter;
        if (Number.isNaN(after)) {
            after = 0;
        }

        const selectedTimeText = timeEl.options[timeEl.selectedIndex]?.textContent?.trim() ?? '';
        const match = selectedTimeText.match(/^(\d{1,2}):(\d{2})/);

        let rangeStart = selectedTimeText;
        let rangeEnd = selectedTimeText;

        if (match) {
            const startMinutes = parseInt(match[1], 10) * 60 + parseInt(match[2], 10);

            const formatMinutes = (minutes) => {
                const normalized = ((minutes % 1440) + 1440) % 1440;
                const hours = Math.floor(normalized / 60);
                const mins = normalized % 60;

                return String(hours).padStart(2, '0') + ':' +
                    String(mins).padStart(2, '0');
            };

            rangeStart = formatMinutes(startMinutes - before);
            rangeEnd = formatMinutes(
                startMinutes + parseInt(durationEl.value, 10) + after
            );
        }

        let stringKey = 'event_error_weekplan_after';

        if (beforeoutside && afteroutside) {
            stringKey = 'event_error_weekplan_both';
        } else if (beforeoutside) {
            stringKey = 'event_error_weekplan_before';
        }

        const errorEl = document.createElement('div');

        errorEl.id = errorId;
        errorEl.className = 'text-danger small me-2';

        errorEl.textContent = await getString(
            stringKey,
            'mod_bookit',
            {
                start: rangeStart,
                end: rangeEnd,
            }
        );

        saveButton?.before(errorEl);
    };

    const refreshStarttimes = async() => {
        const year = parseInt(dateYearEl.value);
        const month = parseInt(dateMonthEl.value);
        const day = parseInt(dateDayEl.value);

        const currentSelectionValue =
        timeEl.value || timeEl.dataset.currentStarttime || '';

        const extraBefore = extraBeforeEl?.value.trim() ?? '';
        const extraAfter = extraAfterEl?.value.trim() ?? '';

        const parsedExtraBefore = parseInt(extraBefore, 10);
        const parsedExtraAfter = parseInt(extraAfter, 10);

        const {
            slots: starttimes,
            status,
            beforeoutside,
            afteroutside,
        } = await Ajax.call([{
            methodname: 'mod_bookit_get_possible_starttimes',
            args: {
                cmid: cmId,
                year: year,
                month: month,
                day: day,
                duration: durationEl.value,
                roomid: roomEl.value,
                excepteventid: exceptEventId,
                currentstarttime:
                    currentSelectionValue
                        ? parseInt(currentSelectionValue, 10)
                        : 0,
                extratimebefore:
                    extraBefore === '' || Number.isNaN(parsedExtraBefore)
                        ? -1
                        : parsedExtraBefore,
                extratimeafter:
                    extraAfter === '' || Number.isNaN(parsedExtraAfter)
                        ? -1
                        : parsedExtraAfter,
            }
        }])[0];
        const currentSelected = currentSelectionValue ? new Date(currentSelectionValue * 1000) : null;
        const preserveCurrentStarttime = exceptEventId !== null && currentSelectionValue !== '';

        while (timeEl.options.length) {
            timeEl.options.remove(0);
        }

        if (status !== null && preserveCurrentStarttime) {
            starttimeEl.hidden = false;
            starttimeExplanationEl.hidden = true;
            const preservedOption = document.createElement('option');
            preservedOption.value = currentSelectionValue;
            preservedOption.innerText = currentSelected.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            });
            preservedOption.selected = true;
            timeEl.options.add(preservedOption);
        } else {
            starttimeEl.hidden = status !== null;
            starttimeExplanationEl.hidden = status === null;

            if (status !== null) {
                starttimeExplanationTextEl.innerHTML =
                    await getString(status === 1 ? 'no_weekplan_defined' : 'no_slot_available',
                        'mod_bookit');
            }
        }

        for (let slot of starttimes) {
            const opt = document.createElement("option");
            opt.value = slot.timestamp;
            opt.innerText = slot.string;

            const date = new Date(slot.timestamp * 1000);

            if (
                currentSelected !== null &&
                date.getHours() * 60 + date.getMinutes() ===
                    currentSelected.getHours() * 60 + currentSelected.getMinutes()
            ) {
                opt.selected = true;
            }

            timeEl.options.add(opt);
        }

        /*
        * Room/date/duration changes can cause EventCalendar to select another
        * start time while this request was running. Validate that new value once.
        */
        if (timeEl.value && timeEl.value !== currentSelectionValue) {
            timeEl.dataset.currentStarttime = timeEl.value;
            await refreshStarttimes();
            return;
        }

        await updateWeekplanValidation(
            beforeoutside,
            afteroutside,
            currentSelectionValue,
            parsedExtraBefore,
            parsedExtraAfter
        );
        if (timeEl.value) {
            timeEl.dataset.currentStarttime = timeEl.value;
        }
    };

    for (let el of [
        roomEl,
        durationEl,
        dateDayEl,
        dateMonthEl,
        dateYearEl,
        timeEl,
        extraBeforeEl,
        extraAfterEl,
    ]) {
        el?.addEventListener('change', refreshStarttimes);
    }
    void refreshStarttimes();
}
