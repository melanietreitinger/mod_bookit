/* -------------------------------------------------------------------------
 *  BookIT calendar (FullCalendar wrapper) – COMPLETE SOURCE FILE
 * ------------------------------------------------------------------------ */

import {getString}        from 'core/str';
import ModalForm          from 'core_form/modalform';
import {prefetchStrings}  from 'core/prefetch';

/**
 * Wait until a global property exists (EventCalendar is loaded asynchronously).
 *
 * @param {String} globalPropertyName
 * @returns {Promise<void>}
 */
const waitForGlobal = (globalPropertyName) =>
    new Promise(resolve => (function loop() {
        if (!window[globalPropertyName]) {
            setTimeout(loop, 20);
            return;
        }
        resolve();
    })());

/**
 * Initialise BookIT calendar.
 *
 * @param {Number}  cmid         Course-module id
 * @param {String}  eventsource  URL for JSON feed (events.php)
 * @param {Object}  capabilities {addevent: Boolean}
 * @param {String}  lang         Current UI language code
 * @param {Object}  config       Extra config (e.g. {textcolor:'#fff'})
 */
export async function init(cmid, eventsource, capabilities, lang, config) {

    /* --------------------------------------------------------------------
       0. Wait for EventCalendar to be present
       -------------------------------------------------------------------- */
    await waitForGlobal('EventCalendar');

    /* --------------------------------------------------------------------
       1. Read config
       -------------------------------------------------------------------- */
    let textcolor = '#ffffff';
    if (Object.prototype.hasOwnProperty.call(config, 'textcolor')) {
        textcolor = config.textcolor;
    }

    let toolbarButtons = 'prev,next today';
    if (capabilities.addevent) {
        toolbarButtons = 'prev,next today addButton';
    }

    /* --------------------------------------------------------------------
       2. Prefetch & fetch required strings
       -------------------------------------------------------------------- */
    await prefetchStrings('mod_bookit', ['addbooking', 'edit_event']);
    await prefetchStrings('core',       ['today', 'month', 'week']);
    await prefetchStrings('calendar',   ['day', 'upcomingevents']);

    const STR_ADD_BOOKING  = await getString('addbooking',     'mod_bookit');
    const STR_EDIT_EVENT   = await getString('edit_event',     'mod_bookit');
    const STR_TODAY        = await getString('today');
    const STR_MONTH        = await getString('month');
    const STR_WEEK         = await getString('week');
    const STR_DAY          = await getString('day',            'calendar');
    const STR_LIST         = await getString('upcomingevents', 'calendar');

    /* --------------------------------------------------------------------
       3. Pick default view (week grid vs list) based on viewport width
       -------------------------------------------------------------------- */
    const viewType = window.screen.width <= 1000 ? 'listWeek' : 'timeGridWeek';

    /* --------------------------------------------------------------------
       4. Weekday visibility from admin settings (injected by PHP)
       -------------------------------------------------------------------- */
    const allowedWeekdays = (window.M && M.cfg && Array.isArray(M.cfg.bookit_allowedweekdays))
        ? M.cfg.bookit_allowedweekdays
        : [1, 2, 3, 4, 5];             // Fallback: Mon-Fri

    const hiddenDays = [0,1,2,3,4,5,6].filter(d => !allowedWeekdays.includes(d));

    /* --------------------------------------------------------------------
       5.  Runtime filter parameters – mutable via bookitCalendarUpdate()
       -------------------------------------------------------------------- */
    let extraFilterParams = {};   // {room:123, status:2, faculty:'ENG', …}

    /* --------------------------------------------------------------------
       6.  Build the calendar instance
       -------------------------------------------------------------------- */
    const calendar = new window.EventCalendar(document.getElementById('ec'), {

        /* ----- appearance / behaviour ---------------------------------- */
        locale            : lang,
        view              : viewType,
        firstDay          : 1,
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

        /* ----- labels --------------------------------------------------- */
        buttonText(text) {
            text.today        = STR_TODAY;
            text.dayGridMonth = STR_MONTH;
            text.timeGridWeek = STR_WEEK;
            text.timeGridDay  = STR_DAY;
            text.listWeek     = STR_LIST;
            return text;
        },

        /* ----- custom toolbar button (“Add booking”) ------------------- */
        customButtons: {
            addButton: {
                text : STR_ADD_BOOKING,
                click() {
                    const modal = new ModalForm({
                        formClass  : 'mod_bookit\\form\\edit_event_form',
                        args       : {cmid},
                        modalConfig: {title: STR_EDIT_EVENT},
                    });
                    modal.addEventListener(modal.events.FORM_SUBMITTED,
                        () => calendar.refetchEvents());
                    modal.show();
                }
            }
        },

        /* ----- date click (create new event) --------------------------- */
        dateClick(info) {
            const weekday = info.date.getUTCDay();   // 0=Sun … 6=Sat
            if (!allowedWeekdays.includes(weekday)) { return; }

            const nowISO = new Date(Date.now() - (new Date).getTimezoneOffset()*60000)
                            .toISOString();
            if (capabilities.addevent && info.dateStr > nowISO) {
                const modal = new ModalForm({
                    formClass  : 'mod_bookit\\form\\edit_event_form',
                    args       : {cmid, startdate: info.dateStr},
                    modalConfig: {title: STR_EDIT_EVENT},
                });
                modal.addEventListener(modal.events.FORM_SUBMITTED,
                    () => calendar.refetchEvents());
                modal.show();
            }
        },

        /* ----- event click (edit) -------------------------------------- */
        eventClick(info) {
            if (info.event.extendedProps.reserved) { return; }

            const modal = new ModalForm({
                formClass  : 'mod_bookit\\form\\edit_event_form',
                args       : {cmid, id: info.event.id},
                modalConfig: {title: STR_EDIT_EVENT},
            });
            modal.addEventListener(modal.events.FORM_SUBMITTED,
                () => calendar.refetchEvents());
            modal.show();
        },

        /* ----- toolbar configuration ----------------------------------- */
        headerToolbar: {
            start : toolbarButtons,
            center: 'title',
            end   : 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },

        /* ----- data source --------------------------------------------- */
        resources   : [],
        eventSources: [{
            url         : eventsource,
            extraParams : () => extraFilterParams  
        }],

        /* ----- extra view tweaks --------------------------------------- */
        views: {
            timeGridWeek         : {pointer:true},
            resourceTimeGridWeek : {pointer:true},
            resourceTimelineWeek : {
                pointer     : true,
                slotMinTime : '09:00',
                slotMaxTime : '21:00',
                slotWidth   : 80,
                resources   : []
            }
        }
    });

    window.bookitCalendar = calendar;      
    /* --------------------------------------------------------------------
       7. Expose updater for the filter form (called from view.php)
       -------------------------------------------------------------------- */
    window.bookitCalendarUpdate = function (paramObj = {}) {
        extraFilterParams = paramObj;
        extraFilterParams      = params || {};
        window.currentFilterParams = extraFilterParams;  // ← new
        calendar.refetchEvents();
    };
}