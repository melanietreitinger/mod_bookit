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
 * @module     mod_bookit/available_calendar
 * @copyright  2024 Melanie Treitinger, Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';
import {getString} from 'core/str';
import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';
import {prefetchStrings} from 'core/prefetch';
import {theGlobalProperty} from "mod_bookit/calendar";

/**
 * Initializes the calendar.
 * @param {string} eventsource
 * @param {array} capabilities
 * @param {string} lang
 * @returns {Promise<void>}
 */
export async function init(eventsource, capabilities, lang) {
    await theGlobalProperty('EventCalendar');

    // Define toolbarbuttons.
    const toolbarbuttons = 'prev, next, today, addButton';

    // String variables.
    prefetchStrings('mod_bookit', ['add_blocker']);
    prefetchStrings('core', ['today', 'month', 'week']);
    prefetchStrings('calendar', ['day', 'upcomingevents']);
    const strrequestbooking = await getString('add_blocker', 'mod_bookit');
    const strtoday = await getString('today');
    const strmonth = await getString('month');
    const strweek = await getString('week');
    const strday = await getString('day', 'calendar');
    const strlist = await getString('upcomingevents', 'calendar');

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
        eventBackgroundColor: '#035AA3',
        eventStartEditable: false,
        eventDurationEditable: false,
        buttonText: function(text) {
            text.today = strtoday;
            text.dayGridMonth = strmonth;
            text.timeGridWeek = strweek;
            text.timeGridDay = strday;
            text.listWeek = strlist;
            return text;
        },
        customButtons: {
            addButton: {
                text: strrequestbooking,
                click: function() {
                    const modalForm = new ModalForm({
                        formClass: "mod_bookit\\local\\form\\edit_blocker_form",
                        args: {
                        },
                        modalConfig: {title: getString('add_blocker', 'mod_bookit')},
                    });
                    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                        calendar.refetchEvents();
                    });
                    modalForm.show();
                }
            }
        },
        dateClick: function(info) {
            let d = new Date();
            let dateoff = new Date(d.setMinutes(d.getMinutes() - d.getTimezoneOffset()));
            let startdate = info.dateStr;
            if (startdate > dateoff.toISOString()) {
                const modalForm = new ModalForm({
                    formClass: "mod_bookit\\local\\form\\edit_blocker_form",
                    args: {
                        startdate: startdate,
                    },
                    modalConfig: {title: getString('add_blocker', 'mod_bookit')},
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.show();
            }
        },
        eventClick: function(info) {
            if (info.event.extendedProps.type === 'blocker') {
                const modalForm = new ModalForm({
                    formClass: "mod_bookit\\local\\form\\edit_blocker_form",
                    args: {
                        id: info.event.id,
                    },
                    modalConfig: {
                        title: getString('edit_blocker', 'mod_bookit'),
                    },
                    moduleName: 'mod_bookit/modal_delete_save_cancel',
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.addEventListener(modalForm.events.LOADED, () => {
                    modalForm.modal.getRoot().on(ModalEvents.delete, async() => {
                        await deleteBlocker(info.event.id);
                        calendar.refetchEvents();
                    });
                });
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

/**
 * Deletes a blocker.
 *
 * @param {int} blockerId
 * @returns Promise<any>
 */
function deleteBlocker(blockerId) {
    return Ajax.call([{
        methodname: 'mod_bookit_delete_blocker',
        args: {
            blockerid: blockerId
        }
    }])[0];
}