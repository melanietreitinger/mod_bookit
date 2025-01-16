import {get_string as getString} from 'core/str';
import ModalForm from 'core_form/modalform';
import Prefetch from 'core/prefetch';
import Log from 'core/log';
import {call as fetchMany} from 'core/ajax';

/**
 * Get the configuration from the server
 * @returns {Promise<Object>}
 */
async function getConfig() {
    try {
        const response = await fetchMany([{
            methodname: 'mod_bookit_get_config',
            args: {}
        }])[0];
        return response;
    } catch (error) {
        Log.error('Fehler beim Abrufen der Konfiguration:', error);
        throw error;
    }
}

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
 * @param {int} cmid
 * @param {string} moduleinstanceid
 * @param {string} eventsource
 * @param {string} lang
 * @returns {Promise<void>}
 */
export async function init(cmid, moduleinstanceid, eventsource, lang) {
    await theGlobalProperty('EventCalendar');

    // String variables.
    Prefetch.prefetchString('mod_bookit', ['addbooking']);
    Prefetch.prefetchString('core', ['today', 'month', 'week']);
    Prefetch.prefetchString('core_calendar', ['day', 'upcomingevents']);
    const str_request_booking   = await getString('addbooking', 'mod_bookit');
    const str_today             = await getString('today');
    const str_month             = await getString('month');
    const str_week              = await getString('week');
    const str_day               = await getString('day', 'core_calendar');
    const str_list              = await getString('upcomingevents', 'core_calendar');

    let viewType = 'timeGridWeek';
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }

    var calendar;
    const config = await getConfig();

    calendar = new window.EventCalendar(document.getElementById('ec'), {
        locale: lang,
        view: viewType,
        firstDay: 1,
        scrollTime: '09:00:00',
        slotMinTime: `${String(config.min_time_hour).padStart(2, '0')}:${String(config.min_time_minute).padStart(2, '0')}:00`,
        slotMaxTime: `${String(config.max_time_hour).padStart(2, '0')}:${String(config.max_time_minute).padStart(2, '0')}:00`,
        defaultTimedEventDuration: `${config.default_duration}:00`,
        dayMaxEvents: true,
        nowIndicator: true,
        selectable: false,
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
                            config: config
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
            Log.debug('Date clicked:', info.date);
            Log.debug('Date string:', info.dateStr);
            let startdate = info.dateStr;
            const modalForm = new ModalForm({
                formClass: "mod_bookit\\form\\edit_event_form",
                args: {
                    cmid: cmid,
                    startdate: startdate,
                    config: config
                },
                modalConfig: {title: getString('edit_event', 'mod_bookit')},
            });
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                calendar.refetchEvents();
            });
            modalForm.show();
        },
        eventClick: function (info) {
            let id = info.event.id;
            let title = info.event.title;

            Log.debug('Event clicked:', info);
            Log.debug('CMID:', cmid);
            Log.debug('Event ID:', id);
            Log.debug('Event title:', title);

            if ("reserved" != title.toLowerCase()) {
                const modalForm = new ModalForm({
                    formClass: "mod_bookit\\form\\edit_event_form",
                    args: {
                        cmid: cmid,
                        id: id,
                        config: config
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
            start: 'prev,next today, addButton',
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
