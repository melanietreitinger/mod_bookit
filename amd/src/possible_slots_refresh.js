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
 */
export function initPossibleStarttimesRefresh() {
    const formEl = document.querySelector('.modal-body form');
    if (!formEl) {
        setTimeout(initPossibleStarttimesRefresh, 50);
        return;
    }

    void prefetchStrings('mod_bookit', ['no_slot_available', 'no_weekplan_defined']);

    const roomEl = formEl.querySelector('select[name="roomid"]');
    const durationEl = formEl.querySelector('select[name="duration"]');
    const dateDayEl = formEl.querySelector('select[name="startdate[day]"]');
    const dateMonthEl = formEl.querySelector('select[name="startdate[month]"]');
    const dateYearEl = formEl.querySelector('select[name="startdate[year]"]');

    const timeEl = formEl.querySelector('select[name="starttime"]');

    const starttimeEl = document.querySelector('.fitem:has(select[name="starttime"])');
    const starttimeExplanationEl = document.querySelector('.fitem:has(.form-control-static[data-name="starttime_explanation"])');
    const starttimeExplanationTextEl = starttimeExplanationEl.querySelector(
        '.form-control-static[data-name="starttime_explanation"]'
    );

    const refreshStarttimes = async() => {
        const year = parseInt(dateYearEl.value);
        const month = parseInt(dateMonthEl.value);
        const day = parseInt(dateDayEl.value);

        const {slots: starttimes, status} = await Ajax.call([{
            methodname: 'mod_bookit_get_possible_starttimes',
            args: {
                year: year,
                month: month,
                day: day,
                duration: durationEl.value,
                roomid: roomEl.value,
            }
        }])[0];

        const currentSelected = new Date(timeEl.value * 1000);

        while (timeEl.options.length) {
            timeEl.options.remove(0);
        }

        starttimeEl.hidden = status !== null;
        starttimeExplanationEl.hidden = status === null;

        if (status !== null) {
            starttimeExplanationTextEl.innerHTML =
                await getString(status === 1 ? 'no_weekplan_defined' : 'no_slot_available',
                    'mod_bookit');
        }

        for (let slot of starttimes) {
            const opt = document.createElement("option");
            opt.value = slot.timestamp;
            opt.innerText = slot.string;
            const date = new Date(slot.timestamp * 1000);
            if (date.getHours() * 60 + date.getMinutes() === currentSelected.getHours() * 60 + currentSelected.getMinutes()) {
                opt.selected = true;
            }
            timeEl.options.add(opt);
        }
    };

    for (let el of [roomEl, durationEl, dateDayEl, dateMonthEl, dateYearEl]) {
        el.addEventListener('change', refreshStarttimes);
    }
    void refreshStarttimes();
}
