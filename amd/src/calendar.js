import {get_string as getString} from 'core/str';
import ModalForm from 'core_form/modalform';
import Prefetch from 'core/prefetch';
import {FilterManager} from './filter_manager';
import {FilterUI} from './filter_ui';

/**
 * Wait for a global property to be available
 * @param {string} globalPropertyName
 * @returns {Promise}
 */
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
 * Initialize the calendar
 * @param {number} cmid - Course Module ID
 * @param {number} instanceid - Instance ID
 * @param {string} eventsource - URL for events
 * @param {string} lang - Language code
 * @param {Object} permissions - Permission object with isAdmin and isServiceTeam
 * @returns {Promise<void>}
 */
export async function init(cmid, instanceid, eventsource, lang, permissions) {
    // Preload strings
    Prefetch.prefetchString('mod_bookit', 'addbooking');
    await theGlobalProperty('EventCalendar');

    // String variables.
    Prefetch.prefetchStrings('mod_bookit', ['addbooking']);
    Prefetch.prefetchStrings('core', ['today', 'month', 'week']);
    Prefetch.prefetchStrings('core_calendar', ['day', 'upcomingevents']);

    const str_request_booking = await getString('addbooking', 'mod_bookit');
    const str_today = await getString('today', 'core');
    const str_month = await getString('month', 'core');
    const str_week = await getString('week', 'core');
    const str_day = await getString('day', 'core_calendar');
    const str_list = await getString('upcomingevents', 'core_calendar');

    // Choose view based on screen size
    let viewType = 'timeGridWeek';
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }

    // Initialize filters
    const filterManager = new FilterManager();
    const filterUI = new FilterUI();
    await filterUI.init(permissions.isAdmin || permissions.isServiceTeam);
    filterManager.init();

    // Initialize calendar
    const calendar = new window.EventCalendar(document.getElementById('ec'), {
        locale: lang,
        view: viewType,
        firstDay: 1,
        scrollTime: '09:00:00',
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        dayMaxEvents: true,
        nowIndicator: true,
        selectable: true,
        eventStartEditable: false,
        eventDurationEditable: false,
        hiddenDays: [0, 6], // Hide Sunday and Saturday
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
                    modalForm.show();
                }
            }
        },
        dateClick: function(info) {
            const modalForm = new ModalForm({
                formClass: "mod_bookit\\form\\edit_event_form",
                args: {
                    cmid: cmid,
                    startdate: info.dateStr,
                },
                modalConfig: {title: getString('add_event', 'mod_bookit')},
            });
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                calendar.refetchEvents();
            });
            modalForm.show();
        },
        eventClick: function(info) {
            if (info.event.title.toLowerCase() !== "reserved") {
                const modalForm = new ModalForm({
                    formClass: "mod_bookit\\form\\edit_event_form",
                    args: {
                        cmid: cmid,
                        id: info.event.id,
                    },
                    modalConfig: {title: getString('edit_event', 'mod_bookit')},
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.show();
            }
        },
        headerToolbar: {
            start: 'prev,next today addButton',
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        eventSources: [{
            url: eventsource,
            method: 'POST',
            extraParams: function() {
                // Base parameters
                const params = {
                    id: String(cmid)
                };

                // Filter parameters from filterManager
                const filters = filterManager.filters;
                if (filters) {
                    if (filters.search) {
                        params.search = String(filters.search);
                    }
                    if (filters.room) {
                        params.roomid = String(filters.room);
                    }
                    if (filters.faculty) {
                        params.facultyid = String(filters.faculty);
                    }
                    if (filters.status !== undefined && filters.status !== '') {
                        params.status = parseInt(filters.status, 10);
                    }
                    if (filters.timeslot) {
                        params.timeslot = String(filters.timeslot);
                    }
                }
                return params;
            }
        }]
    });

    // Monitor filter changes
    document.addEventListener('filterchange', (event) => {
        // Update filter values
        filterManager.filters = event.detail;
        // Reload calendar
        calendar.refetchEvents();
    });
}
