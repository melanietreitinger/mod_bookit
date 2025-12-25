define(['jquery', 'core/str'], function($, str) {
    return {
        init: function(cmId) {

            const stringKeys = [
                {key: 'noevents', component: 'mod_bookit'},
                {key: 'chooseevent', component: 'mod_bookit'}
            ];

            str.get_strings(stringKeys).done(function(strings) {
                const noEventsStr = strings[0];

                function toLocalDateValue(dateObj) {
                    // Date -> 'YYYY-MM-DD' in local time.
                    const d = new Date(dateObj);
                    const offMin = d.getTimezoneOffset();
                    const local = new Date(d.getTime() - offMin * 60000);
                    return local.toISOString().slice(0, 10);
                }

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

                    const startDate = ($('#bookit-export-start').val() || '').trim();
                    const endDate   = ($('#bookit-export-end').val() || '').trim();
                    const fallback  = getCalendarDateRangeOrFallback();

                    const s = startDate || fallback.startDate;
                    const e = endDate   || fallback.endDate;

                    qs.start = s + 'T00:00';
                    qs.end   = e + 'T23:59';

                    if (window.currentFilterParams) {
                        Object.keys(window.currentFilterParams).forEach(function(k) {
                            if (k === 'start' || k === 'end') {
                                return; // never allow filters to override modal range
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

                $(document).on('click', '#bookit-export', function() {
                    const r = getCalendarDateRangeOrFallback();
                    $('#bookit-export-start').val(r.startDate);
                    $('#bookit-export-end').val(r.endDate);

                    $('#bookit-export-modal').modal('show');
                    fetchExportList();
                });

                $(document).on('change input', '#bookit-export-start, #bookit-export-end', function() {
                    if ($('#bookit-export-modal').hasClass('show')) {
                        fetchExportList();
                    }
                });

                $(document).on('click', '#bookit-export-reset-range', function() {
                    const r = getCalendarDateRangeOrFallback();
                    $('#bookit-export-start').val(r.startDate);
                    $('#bookit-export-end').val(r.endDate);
                    fetchExportList();
                });

                $(document).on('input', '#bookit-modal-search', filterExportList);

                $(document).on('click', '#bookit-check-all', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', true);
                });

                $(document).on('click', '#bookit-uncheck-all', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', false);
                });

                $(document).on('click', '#bookit-export-confirm', function() {
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
