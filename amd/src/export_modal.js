define(['jquery', 'core/str'], function($, str) {

    return {
        init: function(cmId) {

            const stringKeys = [
                {key: 'noevents', component: 'mod_bookit'},
                {key: 'chooseevent', component: 'mod_bookit'}
            ];

            str.get_strings(stringKeys).done(function(strings) {
                const noEventsStr = strings[0];

                function toLocalInputValue(dateObj) {
                    // Convert Date -> 'YYYY-MM-DDTHH:MM' in *local* time (for datetime-local input).
                    const d = new Date(dateObj);
                    const offMin = d.getTimezoneOffset();
                    const local = new Date(d.getTime() - offMin * 60000);
                    return local.toISOString().slice(0, 16);
                }

                function getCalendarRangeOrFallback() {
                    if (window.bookitCalendar && window.bookitCalendar.view) {
                        const view = window.bookitCalendar.view;
                        return {
                            start: toLocalInputValue(view.activeStart),
                            end: toLocalInputValue(view.activeEnd),
                        };
                    }
                    return {start: '1970-01-01T00:00', end: '2100-01-01T00:00'};
                }

                function filterExportList() {
                    const val = ($('#bookit-modal-search').val() || '').toLowerCase().trim();
                    $('#bookit-export-list label').each(function() {
                        const $row = $(this);
                        const show = $row.text().toLowerCase().includes(val);
                        $row.toggleClass('d-flex', show).toggleClass('d-none', !show);
                    });
                }

                function fetchExportList() {
                    const qs = {id: cmId};

                    // Read from modal inputs (user-editable).
                    const start = ($('#bookit-export-start').val() || '').trim();
                    const end = ($('#bookit-export-end').val() || '').trim();
                    qs.start = start || getCalendarRangeOrFallback().start;
                    qs.end = end || getCalendarRangeOrFallback().end;

                    // Apply current calendar filters (room/faculty/status/search).
                    if (window.currentFilterParams) {
                        Object.assign(qs, window.currentFilterParams);
                    }

                    const list = $('#bookit-export-list');
                    list.html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i></div>');

                    $.getJSON(M.cfg.wwwroot + '/mod/bookit/events.php', qs, function(data) {
                        list.empty();

                        // Remove reserved events from the export list.
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

                // Set defaults from calendar view every time, then load.
                $('#bookit-export').on('click', function() {
                    const range = getCalendarRangeOrFallback();
                    $('#bookit-export-start').val(range.start);
                    $('#bookit-export-end').val(range.end);

                    $('#bookit-export-modal').modal('show');
                    fetchExportList();
                });

                // Instant refresh when user changes dates.
                $('#bookit-export-start, #bookit-export-end').on('input change', function() {
                    if ($('#bookit-export-modal').hasClass('show')) {
                        fetchExportList();
                    }
                });

                // Reset button.
                $('#bookit-export-reset-range').on('click', function() {
                    const range = getCalendarRangeOrFallback();
                    $('#bookit-export-start').val(range.start);
                    $('#bookit-export-end').val(range.end);
                    fetchExportList();
                });

                // Search filter inside modal (client-side).
                $('#bookit-modal-search').on('input', filterExportList);

                $('#bookit-check-all').on('click', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', true);
                });
                $('#bookit-uncheck-all').on('click', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', false);
                });

                // Confirm export (exports selected IDs).
                $('#bookit-export-confirm').on('click', function() {
                    const ids = $('#bookit-export-list input[type=checkbox]:enabled:checked')
                        .map(function() { return this.value; }).get();

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