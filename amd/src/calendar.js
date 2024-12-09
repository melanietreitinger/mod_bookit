import {get_string as getString} from 'core/str';
import ModalForm from 'core_form/modalform';
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
    Prefetch.prefetchString('calendar', ['day', 'upcomingevents']);
    const str_request_booking   = await getString('addbooking', 'mod_bookit');
    const str_today             = await getString('today');
    const str_month             = await getString('month');
    const str_week              = await getString('week');
    const str_day               = await getString('day', 'calendar');
    const str_list              = await getString('upcomingevents', 'calendar');

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
                                modalForm.show();
                }
            }
        },
        dateClick: function(info) {
            console.log(info.date);
            console.log(info.dateStr);
            let startdate = info.dateStr;
            const modalForm = new ModalForm({
                formClass: "mod_bookit\\form\\edit_event_form",
                args: {
                    cmid: cmid,
                    startdate: startdate,
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

            console.log(info);
            console.log("cmid: "+cmid);
            console.log("id: "+id);

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
            modalForm.show();
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
