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
import {openEditEventModal} from 'mod_bookit/event_modal_opener';
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

    // Overlap mode per view (overlapping or fixed borders). true = overlapping (default),
    // false = equal-width columns, no overlap. Day and week only (time-grid views).
    const overlapFor = (view) => {
        const key = 'eventoverlap_' + view;
        if (Object.prototype.hasOwnProperty.call(config, key)) {
            return Number(config[key]) !== 0;
        }
        return true;
    };
    const dayOverlap = overlapFor('day');
    const weekOverlap = overlapFor('week');
    window.console.log('[bookit] overlap', {config, dayOverlap, weekOverlap});

    // Define toolbarbuttons.
    let toolbarbuttons = 'prev,next today';
    if (capabilities.addevent) {
        toolbarbuttons = 'prev,next today addButton';
    }

    // String variables
    await prefetchStrings('mod_bookit', ['calendar_addbooking', 'calendar_editevent', 'calendar_eventlist']);
    await prefetchStrings('calendar', ['day']);
    const strRequestBooking = await getString('calendar_addbooking', 'mod_bookit');
    const editevent = await getString('calendar_editevent', 'mod_bookit');
    const strToday = await getString('today');
    const strMonth = await getString('month');
    const strWeek = await getString('week');
    const strDay = await getString('day', 'calendar');
    const strList = await getString('calendar_eventlist', 'bookit');
    const strCollapse = await getString('calendar_collapsegroups', 'mod_bookit');

    // Layout per view: horizontal = time-grid (side by side), vertical = day-grid
    // (stacked per day). Month has no horizontal variant -> always dayGridMonth.
    const layoutView = (logical) => {
        const key = 'layout_' + logical;
        const vertical = Object.prototype.hasOwnProperty.call(config, key) && Number(config[key]) !== 0;
        if (logical === 'day') {
            return vertical ? 'dayGridDay' : 'timeGridDay';
        }
        if (logical === 'week') {
            return vertical ? 'dayGridWeek' : 'timeGridWeek';
        }
        return 'dayGridMonth';
    };
    const dayViewType = layoutView('day');
    const weekViewType = layoutView('week');
    const monthViewType = layoutView('month');

    // Day-grid day/week map to their time-grid key for summary/max-events lookups.
    const canonicalView = (viewtype) => {
        if (viewtype === 'dayGridDay') {
            return 'timeGridDay';
        }
        if (viewtype === 'dayGridWeek') {
            return 'timeGridWeek';
        }
        return viewtype;
    };

    // The standalone "Eventlist" (listWeek) button shows all events raw, no settings.
    const isRawList = (viewtype) =>
        viewtype === 'listDay' || viewtype === 'listWeek' || viewtype === 'listMonth';


    // Define viewtype
    let viewType = weekViewType;
    if (window.screen.width <= 1000) {
        viewType = 'listWeek';
    }

    // Summary (slot) mode per view, from admin settings. Maps the active view
    // type to whether the event feed should request server-side aggregation.
    const summaryEnabled = (view) => {
        const key = 'summary_' + view;
        return Object.prototype.hasOwnProperty.call(config, key) && Number(config[key]) !== 0;
    };
    const summaryDay = summaryEnabled('day');
    const summaryWeek = summaryEnabled('week');
    const summaryMonth = summaryEnabled('month');

    // Max events per slot before a "+N more" block (0 = off), per view.
    const maxEventsFor = (viewtype) => {
        let key = null;
        if (viewtype === 'timeGridDay') {
            key = 'maxevents_day';
        }
        if (viewtype === 'timeGridWeek' || viewtype === 'listWeek') {
            key = 'maxevents_week';
        }
        if (viewtype === 'dayGridMonth') {
            key = 'maxevents_month';
        }
        if (key && Object.prototype.hasOwnProperty.call(config, key)) {
            return Number(config[key]) || 0;
        }
        return 0;
    };
    if (summaryDay || summaryWeek || summaryMonth) {
        toolbarbuttons += ' collapseButton';
    }

    // Summary interaction mode per view: 'click' = expand, 'hover' = tooltip.
    const summaryModeFor = (viewtype) => {
        const c = canonicalView(viewtype);
        let raw = 0;
        if (c === 'timeGridDay') {
            raw = Number(config.summary_day) || 0;
        }
        else if (c === 'timeGridWeek' || c === 'listWeek') {
            raw = Number(config.summary_week) || 0;
        }
        else if (c === 'dayGridMonth') { 
            raw = Number(config.summary_month) || 0;
        }
        return raw === 2 ? 'hover' : 'click';
    };
    let currentSummaryMode = summaryModeFor(viewType);

    const summaryFor = (viewtype) => {
        if (viewtype === 'timeGridDay') {
            return summaryDay;
        }
        if (viewtype === 'timeGridWeek' || viewtype === 'listWeek') {
            return summaryWeek;
        }
        if (viewtype === 'dayGridMonth') {
            return summaryMonth;
        }
        return false;
    };
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
        const activeView = (window.bookitCalendar && window.bookitCalendar.getView)
            ? window.bookitCalendar.getView().type
            : viewType;
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
                aggregate: isRawList(activeView) ? false : summaryFor(canonicalView(activeView)),
                maxevents: isRawList(activeView) ? 0 : maxEventsFor(canonicalView(activeView)),
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

        // Summary hover: floating, clickable overlay panel (stays open while hovered).
    let summaryPanel = null;
    let summaryPanelTimer = null;

    const hideSummaryPanelNow = () => {
        if (summaryPanelTimer) {
            window.clearTimeout(summaryPanelTimer);
            summaryPanelTimer = null;
        }
        if (summaryPanel) {
            summaryPanel.style.display = 'none';
        }
    };

    const scheduleSummaryPanelHide = () => {
        if (summaryPanelTimer) {
            window.clearTimeout(summaryPanelTimer);
        }
        summaryPanelTimer = window.setTimeout(hideSummaryPanelNow, 250);
    };

    const ensureSummaryPanel = () => {
        if (summaryPanel) {
            return summaryPanel;
        }
        summaryPanel = document.createElement('div');
        summaryPanel.className = 'bookit-summary-panel';
        summaryPanel.style.display = 'none';
        summaryPanel.addEventListener('mouseenter', () => {
            if (summaryPanelTimer) {
                window.clearTimeout(summaryPanelTimer);
                summaryPanelTimer = null;
            }
        });
        summaryPanel.addEventListener('mouseleave', scheduleSummaryPanelHide);
        document.body.appendChild(summaryPanel);
        return summaryPanel;
    };

    const showSummaryPanel = (anchorEl, children) => {
        if (summaryPanelTimer) {
            window.clearTimeout(summaryPanelTimer);
            summaryPanelTimer = null;
        }
        const panel = ensureSummaryPanel();
        panel.innerHTML = '';
        children.forEach((child) => {
            const row = document.createElement('div');
            row.className = 'bookit-summary-panel-row';
            const ts = (child.start || '').slice(11, 16);
            const te = (child.end || '').slice(11, 16);
            let time = '';
            if (ts) {
                time = ts;
                if (te) {
                    time += '–' + te;
                }
            }
            let rowText = child.title || '';
            if (time) {
                rowText = time + '  ' + rowText;
            }
            row.textContent = rowText;

            const props = child.extendedProps || {};
            if (props.visibilitymode === 'reserved_projection') {
                row.classList.add('bookit-summary-panel-row-disabled');
            } else {
                row.addEventListener('click', () => {
                    hideSummaryPanelNow();
                    openEditEventModal({
                        cmid: cmid,
                        eventid: child.id,
                        title: editevent,
                        modalfootermode: props.modalfootermode || 'editable',
                        reloadOnSubmit: false,
                        onSubmitted: () => {
                            calendar.refetchEvents();
                        },
                    });
                });
            }
            panel.appendChild(row);
        });
        panel.style.display = 'block';
        const rect = anchorEl.getBoundingClientRect();
        let left = rect.right + 8 + window.scrollX;
        let top = rect.top + window.scrollY;
        const pw = panel.offsetWidth;
        const viewright = window.scrollX + document.documentElement.clientWidth;
        if (left + pw > viewright) {
            left = rect.left + window.scrollX - pw - 8;
            if (left < window.scrollX) {
                left = rect.left + window.scrollX;
                top = rect.bottom + window.scrollY + 8;
            }
        }
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
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
        lazyFetching: false,
        eventOrder: (a, b) => (a.start - b.start) || (a.end - b.end),

        viewDidMount: function(info) {
            const ec = document.getElementById('ec');
            if (!ec) {
                return;
            }
            const type = info && info.view ? info.view.type : '';
            // Events only overlap in a time-grid with overlap on; everywhere else
            // (abgegrenzt time-grid, day-grid, month, lists) they sit apart.
            let separated = true;
            if (type === 'timeGridDay' && dayOverlap) {
                separated = false;
            }
            if (type === 'timeGridWeek' && weekOverlap) {
                separated = false;
            }
            ec.classList.toggle('bookit-separated', separated);
            currentSummaryMode = summaryModeFor(type);
        },

        nowIndicator: true,
        hiddenDays: hiddenDays,
        selectable: false,
        displayEventEnd: true,
        eventTextColor: textcolor,
        eventBackgroundColor: '#035AA3',
        eventStartEditable: false,
        eventDurationEditable: false,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        buttonText: function(text) {
            text.today = strToday;
            text.dayGridMonth = strMonth;
            text.timeGridWeek = strWeek;
            text.dayGridWeek = strWeek;
            text.timeGridDay = strDay;
            text.dayGridDay = strDay;
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
            collapseButton: {
                text: strCollapse,
                click: function() {
                    if (window.bookitCalendar) {
                        window.bookitCalendar.refetchEvents();
                    }
                }
            },
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
            const weekday = info.date.getDay(); // 0=Sun … 6=Sat
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
        eventDidMount: function(info) {
            if (!info.event || !info.event.extendedProps || !info.event.extendedProps.issummary) {
                return;
            }
            if (currentSummaryMode !== 'hover') {
                return;
            }
            let children = [];
            try {
                children = JSON.parse(info.event.extendedProps.childrenjson || '[]');
            } catch (e) {
                children = [];
            }
            if (!info.el || !children.length) {
                return;
            }
            info.el.addEventListener('mouseenter', () => {
                showSummaryPanel(info.el, children);
            });
            info.el.addEventListener('mouseleave', scheduleSummaryPanelHide);
        },

        /* Event click (edit) */
        eventClick: function(info) {
            let id = info.event.id;
            // Summary block: expand into the individual exams of that slot, inline.
            if (info.event.extendedProps.issummary) {
                if (currentSummaryMode === 'hover') {
                    return;
                }
                let children = [];
                try {
                    children = JSON.parse(info.event.extendedProps.childrenjson || '[]');
                } catch (e) {
                    children = [];
                }
                children.forEach((child) => {
                    if (!calendar.getEventById(String(child.id))) {
                        calendar.addEvent(child);
                    }
                });
                calendar.removeEventById(id);
                return;
            }

            if (info.event.extendedProps.visibilitymode === 'reserved_projection') {
                return;
            }

            openEditEventModal({
                cmid: cmid,
                eventid: id,
                title: editevent,
                modalfootermode: info.event.extendedProps.modalfootermode || 'editable',
                reloadOnSubmit: false,
                onSubmitted: () => {
                    calendar.refetchEvents();
                },
            });
        },

        // Toolbar configuration
        headerToolbar: {
            start: toolbarbuttons,
            center: 'title',
            end: monthViewType + ',' + weekViewType + ',' + dayViewType + ',listWeek'
        },

        resources: [],

        // Feed with logged extra params
        eventSources: [{
            events: loadEvents,
        }],

        views: {
            timeGridDay: {slotEventOverlap: dayOverlap},
            timeGridWeek: {pointer: true, slotEventOverlap: weekOverlap},
            resourceTimeGridWeek: {pointer: true, slotEventOverlap: weekOverlap},
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
