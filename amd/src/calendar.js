import {getString} from 'core/str';
import ModalForm from 'core_form/modalform';
import {prefetchStrings} from 'core/prefetch';

/**
 * Wait until a global property exists (EventCalendar is loaded asynchronously).
 *
 * @param {String} globalPropertyName
 * @returns {Promise<void>}
 */
const theGlobalProperty = (globalPropertyName) =>
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
 * @param {String}  eventsource  URL for JSON feed (events.php)
 * @param {Object}  capabilities {addevent: Boolean}
 * @param {String}  lang         Current UI language code
 * @param {Object}  config       Extra config (e.g. {textcolor:'#fff'})
 */
export async function init(cmid, eventsource, capabilities, lang, config) {
    await theGlobalProperty('EventCalendar');

    // Set textcolor.
    let textcolor = '#ffffff';
    if (Object.prototype.hasOwnProperty.call(config, 'textcolor')) {
        textcolor = config.textcolor;
        //console.log('textcolor set to '+textcolor);
    }

    // Define toolbarbuttons.
    let toolbarbuttons = 'prev,next today';
    if (capabilities.addevent) {
        toolbarbuttons = 'prev,next today addButton';
        //console.log(capabilities);
    }

    //String variables
    await prefetchStrings('mod_bookit', ['addbooking', 'edit_event']);
    await prefetchStrings('core', ['today', 'month', 'week']);
    await prefetchStrings('calendar', ['day', 'upcomingevents']);
    const str_request_booking   = await getString('addbooking', 'mod_bookit');
    const str_today             = await getString('today');
    const str_month             = await getString('month');
    const str_week              = await getString('week');
    const str_day               = await getString('day', 'calendar');
    const str_list              = await getString('upcomingevents', 'calendar');

    //Define viewtype
    let viewType = 'timeGridWeek';
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }
    // weekday visibility from admin settings (injected by PHP)
    const allowedWeekdays = (window.M && M.cfg && Array.isArray(M.cfg.bookit_allowedweekdays))
    ? M.cfg.bookit_allowedweekdays.map(x => Number(x))
    : [1, 2, 3, 4, 5];             // Fallback: Mon‑Fri

    const hiddenDays = [0,1,2,3,4,5,6].filter(d => !allowedWeekdays.includes(d));

     // Runtime filter parameters – mutable via bookitCalendarUpdate()
    let extraFilterParams = {};   // {room:123, status:2, faculty:'ENG', …}

    var calendar;
    calendar = new window.EventCalendar(document.getElementById('ec'), {
        /* ----- appearance / behaviour(improved code readability by intendations) ---------------------------------- */
        locale            : lang,
        view              : viewType,
        firstDay          : 1,
        weekends          : allowedWeekdays.includes(0) || allowedWeekdays.includes(6),
        scrollTime        : '09:00:00',
        slotMinTime       : '07:00:00',
        dayMaxEvents      : true,
        nowIndicator      : true,
        hiddenDays        : hiddenDays,
        selectable        : false,
        eventTextColor    : textcolor,
        eventBackgroundColor : '#035AA3',
        eventStartEditable   : false,
        eventDurationEditable: false,
        buttonText: function (text) {
            text.today = str_today;
            text.dayGridMonth = str_month;
            text.timeGridWeek = str_week;
            text.timeGridDay = str_day;
            text.listWeek = str_list;
            return text;
        },

        /* ----- custom toolbar button (“Add booking”) ------------------- */
        customButtons: {
            addButton: {
                text: str_request_booking,
                click: function() {
                    const modalForm = new ModalForm({
                        formClass: 'mod_bookit\\form\\edit_event_form',
                        args: {
                            cmid:cmid,
                        },
                        modalConfig: {title: getString('edit_event', 'mod_bookit')},
                    });
                    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED,() => {
                        calendar.refetchEvents();
                    });
                    modalForm.show();
                }
            }
        },

        /* ----- date click (create new event) --------------------------- */
        dateClick: function(info) {
            const weekday = info.date.getUTCDay();   // 0=Sun … 6=Sat
            if (!allowedWeekdays.includes(weekday)) { return; }

            let d = new Date();
            let dateoff = new Date(d.setMinutes(d.getMinutes() - d.getTimezoneOffset()));
            let startdate = info.dateStr;

            if (capabilities.addevent && startdate > dateoff.toISOString()) {
                const modalForm = new ModalForm({
                    formClass: 'mod_bookit\\form\\edit_event_form',
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

        /* ----- event click (edit) -------------------------------------- */
        eventClick: function(info) {
            let id = info.event.id;

            //console.log(info);
            //console.log("cmid: "+cmid);
            //console.log("id: "+id);
            //console.log(info.event.extendedProps.reserved);

            if (info.event.extendedProps.reserved) { return; }

            const modalForm = new ModalForm({
                formClass: "mod_bookit\\form\\edit_event_form",
                args: {
                    cmid:cmid,
                    id: id
                },
                modalConfig: {title: getString('edit_event', 'mod_bookit')},
            });
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED,() => {
                calendar.refetchEvents();
            });
            modalForm.show();
        },
        //toolbar configuration
        headerToolbar: {
            start: toolbarbuttons,
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        resources   : [],
        //additional parameters for the filter weekdays functionality
        eventSources: [{
            url         : eventsource,
            extraParams : () => extraFilterParams
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
    /* --------------------------------------------------------------------
    Expose update for the filter form (called from view.php). Work in progress
       -------------------------------------------------------------------- */
    window.bookitCalendarUpdate = function (paramObj = {}) {
        extraFilterParams = paramObj;
        //extraFilterParams      = params || {};
        window.currentFilterParams = extraFilterParams;
        calendar.refetchEvents();
    };
}