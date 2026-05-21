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
import Ajax from 'core/ajax';
import ModalForm from 'core_form/modalform';
import {prefetchStrings} from 'core/prefetch';
import {initPossibleStarttimesRefresh} from "mod_bookit/possible_slots_refresh";
import BookingFormResources from "mod_bookit/booking_form_resources";

/**
 * Wait until a global property exists (EventCalendar is loaded asynchronously).
 *
 * @param {String} globalPropertyName
 * @returns {Promise<void>}
 */
export const theGlobalProperty = (globalPropertyName) =>
    new Promise(resolve => (function loop() {
        if (!window[globalPropertyName]) {
            setTimeout(loop, 20);
            return;
        }
        resolve();
    })());

/**
 * Initializes the calendar.
 * @param {Number}  cmid         Course-module id
 * @param {Object}  readconfig   Governed read configuration
 * @param {Object}  capabilities {addevent: Boolean}
 * @param {String}  lang         Current UI language code
 * @param {Object}  config       Extra config (e.g. {textcolor:'#fff'})
 */
export async function init(cmid, readconfig, capabilities, lang, config) {
    await theGlobalProperty('EventCalendar');

    // Set textcolor.
    let textcolor = '#ffffff';
    if (Object.prototype.hasOwnProperty.call(config, 'textcolor')) {
        textcolor = config.textcolor;
    }

    // Define toolbarbuttons.
    let toolbarbuttons = 'prev,next today';
    if (capabilities.addevent) {
        toolbarbuttons = 'prev,next today addButton';
    }

    // String variables
    await prefetchStrings('mod_bookit', ['calendar_addbooking', 'calendar_editevent', 'calendar_eventlist']);
    await prefetchStrings('core', ['today', 'month', 'week']);
    await prefetchStrings('calendar', ['day']);
    const strRequestBooking = await getString('calendar_addbooking', 'mod_bookit');
    const editevent = await getString('calendar_editevent', 'mod_bookit');
    const strToday = await getString('today');
    const strMonth = await getString('month');
    const strWeek = await getString('week');
    const strDay = await getString('day', 'calendar');
    const strList = await getString('calendar_eventlist', 'bookit');

    // Define viewtype
    let viewType = 'timeGridWeek';
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }
    // Weekday visibility from admin settings (injected by PHP)
    const allowedWeekdays = (window.M && M.cfg && Array.isArray(M.cfg.bookit_allowedweekdays))
        ? M.cfg.bookit_allowedweekdays.map(x => Number(x))
        : [1, 2, 3, 4, 5];

    const hiddenDays = [0, 1, 2, 3, 4, 5, 6].filter(d => !allowedWeekdays.includes(d));

    // Runtime filter parameters – mutable via bookitCalendarUpdate()
    let extraFilterParams = {}; // {room:123, status:2, faculty:'ENG', …}

    const parseIds = (value) => {
        if (!value) {
            return [];
        }
        if (Array.isArray(value)) {
            return value.map(Number).filter((item) => !Number.isNaN(item));
        }
        return String(value).split(',')
            .map((item) => Number(item))
            .filter((item) => !Number.isNaN(item));
    };

    const loadEvents = (fetchInfo, successCallback, failureCallback) => {
        Ajax.call([{
            methodname: readconfig.methodname,
            args: {
                cmid: readconfig.cmid || cmid,
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
                roomids: parseIds(extraFilterParams.room),
                facultyids: parseIds(extraFilterParams.faculty),
                bookingstatuses: parseIds(extraFilterParams.status),
                search: extraFilterParams.search || '',
                exportmode: false,
            },
        }])[0]
            .then((response) => {
                successCallback(response.events || []);
                return null;
            })
            .catch((error) => {
                failureCallback(error);
            });
    };

    const calendar = window.EventCalendar.create(document.getElementById('ec'), {
        /* Appearance / behaviour */
        locale: lang,
        view: viewType,
        firstDay: 1,
        weekends: allowedWeekdays.includes(0) || allowedWeekdays.includes(6),
        scrollTime: '09:00:00',
        slotMinTime: '07:00:00',
        dayMaxEvents: false,
        nowIndicator: true,
        hiddenDays: hiddenDays,
        selectable: false,
        eventTextColor: textcolor,
        eventBackgroundColor: '#035AA3',
        eventStartEditable: false,
        eventDurationEditable: false,
        buttonText: function(text) {
            text.today = strToday;
            text.dayGridMonth = strMonth;
            text.timeGridWeek = strWeek;
            text.timeGridDay = strDay;
            text.listWeek = strList;
            return text;
        },

        eventsSet: function(events) {
            // Comment console.log('[BookIT] eventsSet: received', events.length, 'events');
            if (events.length) {
                /* Comment
                console.log('[BookIT] sample event', {
                    id: events[0].id,
                    title: events[0].title,
                    start: events[0].startStr || events[0].start,
                    end: events[0].endStr || events[0].end,
                    room: events[0].extendedProps?.room,
                    department: events[0].extendedProps?.department,
                    bookingstatus: events[0].extendedProps?.bookingstatus
                });
                */
            }
        },

        /* Custom toolbar button (“Add booking”) */
        customButtons: {
            addButton: {
                text: strRequestBooking,
                click: function() {
                    const modalForm = new ModalForm({
                        formClass: 'mod_bookit\\form\\edit_event_form',
                        args: {
                            cmid: cmid
                        },
                        modalConfig: {title: editevent},
                    });
                    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                        calendar.refetchEvents();
                    });
                    modalForm.addEventListener(modalForm.events.LOADED, () => {
                        initPossibleStarttimesRefresh(cmid);
                        BookingFormResources.init(modalForm.modal.getRoot()[0]);
                    });
                    modalForm.show();
                }
            }
        },

        /* Date click (create new event) */
        dateClick: function(info) {
            const weekday = info.date.getUTCDay(); // 0=Sun … 6=Sat
            if (!allowedWeekdays.includes(weekday)) {
                return;
            }

            let startdate = info.dateStr;
            const isFutureSlot = info.date.getTime() > Date.now();

            if (capabilities.addevent && isFutureSlot) {
                const modalForm = new ModalForm({
                    formClass: 'mod_bookit\\form\\edit_event_form',
                    args: {
                        cmid: cmid,
                        timeclicked: startdate,
                    },
                    modalConfig: {title: editevent},
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.addEventListener(modalForm.events.LOADED, () => {
                    initPossibleStarttimesRefresh(cmid);
                    BookingFormResources.init(modalForm.modal.getRoot()[0]);
                });
                modalForm.show();
            }
        },

        /* Event click (edit) */
        eventClick: function(info) {
            let id = info.event.id;
            if (info.event.extendedProps.visibilitymode === 'reserved_projection') {
                return;
            }

            const modalForm = new ModalForm({
                formClass: "mod_bookit\\form\\edit_event_form",
                args: {
                    cmid: cmid,
                    id: id
                },
                modalConfig: {title: editevent},
            });
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                calendar.refetchEvents();
            });
            modalForm.addEventListener(modalForm.events.LOADED, () => {
                initPossibleStarttimesRefresh(cmid, id);
                BookingFormResources.init(modalForm.modal.getRoot()[0]);
            });
            modalForm.show();
        },

        // Toolbar configuration
        headerToolbar: {
            start: toolbarbuttons,
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },

        resources: [],

        // Feed with logged extra params
        eventSources: [{
            events: loadEvents,
        }],

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
        }
    });

    window.bookitCalendar = calendar;

    /* Expose update for the filter form (called from view.php) */
    window.bookitCalendarUpdate = function(paramObj = {}) {
        extraFilterParams = paramObj;
        window.currentFilterParams = extraFilterParams;
        calendar.refetchEvents();
    };
}
