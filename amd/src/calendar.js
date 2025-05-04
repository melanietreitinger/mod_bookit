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
 * Manage the calendar
 *
 * @module     mod_bookit/calendar
 * @copyright  2024 Melanie Treitinger, Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import ModalForm from 'core_form/modalform';
import {prefetchStrings} from 'core/prefetch';
import {initPossibleStarttimesRefresh} from "mod_bookit/possible_slots_refresh";

export const theGlobalProperty = (globalPropertyName) => {
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
 * @param {int} cmid
 * @param {string} eventsource
 * @param {array} capabilities
 * @param {string} lang
 * @param {array} config
 * @returns {Promise<void>}
 */
export async function init(cmid, eventsource, capabilities, lang, config) {
    await theGlobalProperty('EventCalendar');

    // Set textcolor.
    let textcolor = '#ffffff';
    if (Object.hasOwn(config, 'textcolor')) {
        textcolor = config.textcolor;
    }

    // Define toolbarbuttons.
    let toolbarbuttons = 'prev,next today';
    if (capabilities.addevent) {
        toolbarbuttons = 'prev,next today, addButton';
    }

    // String variables.
    prefetchStrings('mod_bookit', ['addbooking']);
    prefetchStrings('core', ['today', 'month', 'week']);
    prefetchStrings('calendar', ['day', 'upcomingevents']);
    const str_request_booking   = await getString('addbooking', 'mod_bookit');
    const str_today             = await getString('today');
    const str_month             = await getString('month');
    const str_week              = await getString('week');
    const str_day               = await getString('day', 'calendar');
    const str_list              = await getString('upcomingevents', 'calendar');
    /*const str_request_booking = 'XXX';
    const str_today             = 'XXX';
    const str_month             = 'XXX';
    const str_week              = 'XXX';
    const str_day               = 'XXX';
    const str_list              = 'XXX';*/

    // Define viewtype.
    let viewType = 'timeGridWeek';
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }

    var calendar;

    calendar = new window.EventCalendar(document.getElementById('ec'), {
        locale: lang,
        view: viewType,
        firstDay: 1,
        scrollTime: '09:00:00',
        slotMinTime: '07:00:00',
        dayMaxEvents: true,
        nowIndicator: true,
        selectable: false,
        eventTextColor: textcolor,
        eventBackgroundColor: '#035AA3',
        eventStartEditable: false,
        eventDurationEditable: false,
        buttonText: function (text) {
            text.today = str_today;
            text.dayGridMonth = str_month;
            text.timeGridWeek = str_week;
            text.timeGridDay = str_day;
            text.listWeek = str_list;
            return text;
        },
        customButtons: {
            addButton: {
                text: str_request_booking,
                click: function() {
                    const modalForm = new ModalForm({
                                    formClass: "mod_bookit\\form\\edit_event_form",
                                    args: {
                                        cmid: cmid,
                                    },
                                    modalConfig: {title: getString('edit_event', 'mod_bookit')},
                                });
                                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                                    calendar.refetchEvents();
                                });
                                modalForm.addEventListener(modalForm.events.LOADED, initPossibleStarttimesRefresh);
                                modalForm.show();
                }
            }
        },
        dateClick: function(info) {
            let d = new Date();
            let dateoff = new Date(d.setMinutes(d.getMinutes() - d.getTimezoneOffset()));
            let startdate = info.dateStr;
            if (capabilities.addevent && startdate > dateoff.toISOString()) {
                const modalForm = new ModalForm({
                    formClass: "mod_bookit\\form\\edit_event_form",
                    args: {
                        cmid: cmid,
                        initialstartdate: startdate,
                    },
                    modalConfig: {title: getString('edit_event', 'mod_bookit')},
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.addEventListener(modalForm.events.LOADED, initPossibleStarttimesRefresh);
                modalForm.show();
            }
        },
        eventClick: function (info) {
            let id = info.event.id;

            if (!info.event.extendedProps.reserved) {
                const modalForm = new ModalForm({
                    formClass: "mod_bookit\\form\\edit_event_form",
                    args: {
                        cmid: cmid,
                        id: id,
                    },
                    modalConfig: {title: getString('edit_event', 'mod_bookit')},
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.addEventListener(modalForm.events.LOADED, initPossibleStarttimesRefresh);
                modalForm.show();
            }
        },
        headerToolbar: {
            start: toolbarbuttons,
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        resources: [],
        eventSources: [
            {
                url: eventsource,
            },
        ],
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
    });
}
