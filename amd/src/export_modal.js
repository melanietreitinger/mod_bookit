define(['jquery', 'core/str'], function($, str) {
    return {
        init: function(cmId) {

            const stringKeys = [
                {key: 'noevents', component: 'mod_bookit'},
                {key: 'chooseevent', component: 'mod_bookit'}
            ];

            str.get_strings(stringKeys).done(function(strings) {
                const noEventsStr = strings[0];

                /**
                 * Convert a date-like value to a local (browser timezone) `YYYY-MM-DD` string.
                 *
                 * This normalises the date to local time before formatting, avoiding the common
                 * off-by-one issue caused by `toISOString()` using UTC.
                 *
                 * @param {Date|string|number} dateObj A `Date` instance or any value accepted by `new Date(...)`.
                 * @returns {string} Date formatted as `YYYY-MM-DD` in local time.
                 */
                function toLocalDateValue(dateObj) {
                    // Date -> 'YYYY-MM-DD' in local time.
                    const d = new Date(dateObj);
                    const offMin = d.getTimezoneOffset();
                    const local = new Date(d.getTime() - offMin * 60000);
                    return local.toISOString().slice(0, 10);
                }

                /**
                 * Get the currently visible calendar date range from the global Bookit calendar.
                 *
                 * If the calendar instance is not available, returns a wide fallback range so
                 * the export modal can still function (and the backend can apply its own limits).
                 *
                 * Notes:
                 * - `activeStart` is inclusive.
                 * - `activeEnd` is typically exclusive, so we convert it to an inclusive end date
                 *   by subtracting 1ms.
                 *
                 * @returns {{startDate: string, endDate: string}} Object with `YYYY-MM-DD` start and end dates.
                 */
                function getCalendarDateRangeOrFallback() {
                    if (window.bookitCalendar && window.bookitCalendar.view) {
                        const view = window.bookitCalendar.view;

                        const startDate = toLocalDateValue(view.activeStart);

                        const endInclusive = new Date(view.activeEnd.getTime() - 1);
                        const endDate = toLocalDateValue(endInclusive);

                        return {startDate, endDate};
                    }
                    return {startDate: '1970-01-01', endDate: '2100-01-01'};
                }

                /**
                 * Filter the export list entries by the export modal search input.
                 *
                 * This performs a case-insensitive substring match against each label's text and
                 * toggles visibility using Bootstrap utility classes (`d-flex` / `d-none`).
                 *
                 * Expects:
                 * - `#bookit-modal-search` input to exist.
                 * - Each export entry to be represented by a `label` element within
                 *   `#bookit-export-list`.
                 *
                 * @returns {void}
                 */
                function filterExportList() {
                    const val = ($('#bookit-modal-search').val() || '').toLowerCase().trim();
                    $('#bookit-export-list label').each(function() {
                        const $row = $(this);
                        const show = $row.text().toLowerCase().includes(val);
                        $row.toggleClass('d-flex', show).toggleClass('d-none', !show);
                    });
                }


                /**
                 * Fetch events for the export modal and render them as a checkbox list.
                 *
                 * Uses the modal date range if set, otherwise falls back to the current calendar view range.
                 * Merges `window.currentFilterParams` except `start`/`end` (modal range wins).
                 *
                 * @returns {void}
                 */
                function fetchExportList() {
                    const qs = {id: cmId};

                    const startDate = ($('#bookit-export-start').val() || '').trim();
                    const endDate = ($('#bookit-export-end').val() || '').trim();
                    const fallback = getCalendarDateRangeOrFallback();

                    const s = startDate || fallback.startDate;
                    const e = endDate || fallback.endDate;

                    qs.start = s + 'T00:00';
                    qs.end = e + 'T23:59';

                    if (window.currentFilterParams) {
                        Object.keys(window.currentFilterParams).forEach(function(k) {
                            if (k === 'start' || k === 'end') {
                                return; // Never allow filters to override modal range.
                            }
                            qs[k] = window.currentFilterParams[k];
                        });
                    }


                    const list = $('#bookit-export-list');
                    list.html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i></div>');

                    $.getJSON(M.cfg.wwwroot + '/mod/bookit/events.php', qs, function(data) {
                        list.empty();

                        data = (data || []).filter(e => !(e.extendedProps &&
                            (e.extendedProps.reserved === true || e.extendedProps.reserved === 1)));

                        if (!data.length) {
                            list.append('<div class="text-muted">' + noEventsStr + '</div>');
                            return;
                        }

                        const statusMap = {'0': 'New', '1': 'In progress', '2': 'Accepted', '3': 'Cancelled', '4': 'Rejected'};

                        data.forEach(function(e) {
                            const roomTxt = (e.location || e.room || e.roomname || '').trim();
                            const faculty = (e.department || e.faculty || '').trim();
                            const statusTxt = statusMap[String(e.bookingstatus ?? '')] || '';
                            const startStr = (e.start || '');
                            const dateTxt = startStr ? String(startStr).substr(0, 16).replace('T', ' ') : '';
                            const metaLine = roomTxt ? (roomTxt + ' ' + dateTxt) : dateTxt;

                            const checkbox = '<input class="form-check-input mt-1" type="checkbox" value="' + e.id + '">';

                            const row = $(
                                '<label class="list-group-item d-flex gap-2 align-items-start" ' +
                                ' data-room="' + roomTxt.toLowerCase() + '" ' +
                                ' data-faculty="' + faculty.toLowerCase() + '" ' +
                                ' data-status="' + statusTxt.toLowerCase() + '">' +
                                    checkbox +
                                    '<span>' + (e.title?.html || e.title || '') +
                                    ' <small class="text-muted">(' + metaLine + ')</small></span>' +
                                '</label>'
                            );
                            list.append(row);
                        });

                        filterExportList();
                    }).fail(function(xhr) {
                        list.empty();
                        list.append('<div class="text-danger">events.php failed: ' + (xhr.responseText || xhr.status) + '</div>');
                    });
                }
                /**
                 * Open the export modal and load the initial list for the current calendar range.
                 */
                $(document).on('click', '#bookit-export', function() {
                    const r = getCalendarDateRangeOrFallback();
                    $('#bookit-export-start').val(r.startDate);
                    $('#bookit-export-end').val(r.endDate);

                    $('#bookit-export-modal').modal('show');
                    fetchExportList();
                });

                /**
                 * Refresh export list when the modal date range changes (only while modal is open).
                 */
                $(document).on('change input', '#bookit-export-start, #bookit-export-end', function() {
                    if ($('#bookit-export-modal').hasClass('show')) {
                        fetchExportList();
                    }
                });

                /**
                 * Reset modal date range to the current calendar view and refresh list.
                 */
                $(document).on('click', '#bookit-export-reset-range', function() {
                    const r = getCalendarDateRangeOrFallback();
                    $('#bookit-export-start').val(r.startDate);
                    $('#bookit-export-end').val(r.endDate);
                    fetchExportList();
                });

                // Apply live text filter to the export list.
                $(document).on('input', '#bookit-modal-search', filterExportList);

                // Select all visible, enabled event checkboxes.
                $(document).on('click', '#bookit-check-all', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', true);
                });

                // Deselect all visible, enabled event checkboxes.
                $(document).on('click', '#bookit-uncheck-all', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', false);
                });

                /**
                 * Start export for selected event ids and close the modal.
                 *
                 * Builds a query containing `ids[]` plus the current filter params and navigates to export endpoint.
                 */
                $(document).on('click', '#bookit-export-confirm', function() {
                    const ids = $('#bookit-export-list input[type=checkbox]:enabled:checked')
                        .map(function() {
                            return this.value;
                        }).get();

                    if (!ids.length) {
                        return;
                    }

                    const qs = new URLSearchParams({id: cmId});
                    if (window.currentFilterParams) {
                        Object.entries(window.currentFilterParams).forEach(([k, v]) => qs.append(k, v));
                    }
                    ids.forEach(id => qs.append('ids[]', id));

                    window.location = M.cfg.wwwroot + '/mod/bookit/export_events.php?' + qs.toString();
                    $('#bookit-export-modal').modal('hide');
                });
            });
        }
    };
});
