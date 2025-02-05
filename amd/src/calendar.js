import {getString} from 'core/str';
import ModalForm from 'core_form/modalform';
import {prefetchStrings} from 'core/prefetch';

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
 * @param {string} eventsource
 * @param {array} capabilities
 * @param {string} lang
 * @param {array} config
 * @returns {Promise<void>}
 */
export async function init(cmid, eventsource, capabilities, lang, config) {
    await theGlobalProperty('EventCalendar');

    // Set textcolor.
    let textcolor = '#ffff00';
    if (Object.hasOwn(config, 'textcolor')) {
        textcolor = config.textcolor;
        console.log('textcolor set to '+textcolor);
    }

    // Define toolbarbuttons.
    let toolbarbuttons = 'prev,next today';
    if (capabilities.addevent) {
        toolbarbuttons = 'prev,next today, addButton';
        console.log(capabilities);
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
                        startdate: startdate,
                    },
                    modalConfig: {title: getString('edit_event', 'mod_bookit')},
                });
                modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, () => {
                    calendar.refetchEvents();
                });
                modalForm.show();
            }
        },
        eventClick: function (info) {
            let id = info.event.id;

            console.log(info);
            console.log("cmid: "+cmid);
            console.log("id: "+id);
            console.log(info.event.extendedProps.reserved);

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
