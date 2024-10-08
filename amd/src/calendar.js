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
 * Implements reviewing functionality
 *
 * @module     mod_moodleoverflow/reviewing
 * @copyright  2022 Justus Dieckmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {get_string as getString} from 'core/str';
import Prefetch from 'core/prefetch';

const theGlobalProperty = (globalPropertyName) => {
    return new Promise((resolve) => {
       const innerWait = () => {
           if (!window[globalPropertyName]) {
               setTimeout(innerWait, 20);
           }
           resolve();
       };
       innerWait();
    });
};

/**
 * Initializes the calendar.
 * @param {string} entryform
 * @param {string} eventsource
 * @returns {Promise<void>}
 */
export async function init(entryform, eventsource) {
    Prefetch.prefetchString('mod_bookit', 'addbooking');
    await theGlobalProperty('EventCalendar');
    const str_request_booking = await getString('addbooking', 'mod_bookit');

    let viewType = 'timeGridWeek';
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }

    new window.EventCalendar(document.getElementById('ec'), {
        view: viewType,
        firstDay: 1,
        customButtons: {
            addButton: {
                text: str_request_booking,
                click: function() {
                    window.location.replace(entryform);
                }
            }
        },
        headerToolbar: {
            start: 'prev,next today, addButton',
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek resourceTimeGridWeek,resourceTimelineWeek'
        },
        resources: [],
        eventSources: [
            {
                url: eventsource,
            },
        ],
        scrollTime: '09:00:00',
        slotMinTime: '07:00:00',
        views: {
            timeGridWeek: {pointer: true},
            resourceTimeGridWeek: {pointer: true},
            resourceTimelineWeek: {
                pointer: true,
                slotMinTime: '09:00',
                slotMaxTime: '21:00',
                slotWidth: 80,
                resources: []
            }
        },
        dayMaxEvents: true,
        nowIndicator: true,
        selectable: true,
        eventStartEditable: false
    });
}
