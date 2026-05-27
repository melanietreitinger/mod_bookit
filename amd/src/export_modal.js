define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, str) {
    return {
        init: function(readConfig) {
            const cmId = Number(readConfig.cmid || 0);
            const methodName = readConfig.methodname || 'mod_bookit_get_calendar_events';

            const stringKeys = [
                {key: 'noevents', component: 'mod_bookit'},
                {key: 'chooseevent', component: 'mod_bookit'},
                {key: 'exportevents_selected', component: 'mod_bookit'},
                {key: 'event_bookingstatus_0', component: 'mod_bookit'},
                {key: 'event_bookingstatus_1', component: 'mod_bookit'},
                {key: 'event_bookingstatus_2', component: 'mod_bookit'},
                {key: 'event_bookingstatus_3', component: 'mod_bookit'},
                {key: 'event_bookingstatus_4', component: 'mod_bookit'}
            ];

            str.get_strings(stringKeys).done(function(strings) {
                const noEventsStr = strings[0];
                const chooseEventStr = strings[1];
                const selectedLabel = strings[2];
                const statusMap = {
                    '0': strings[3],
                    '1': strings[4],
                    '2': strings[5],
                    '3': strings[6],
                    '4': strings[7]
                };

                /**
                 * Render the localized selected-count label for the export modal.
                 *
                 * @param {Number} count Number of currently selected events.
                 * @returns {String}
                 */
                function formatSelectedCount(count) {
                    return selectedLabel + ': ' + String(count);
                }

                /**
                 * Synchronise the export button state and selected-count badge.
                 *
                 * @returns {void}
                 */
                function updateSelectionState() {
                    const selected = $('#bookit-export-list input[type=checkbox]:enabled:checked').length;
                    $('#bookit-export-selection-count').text(formatSelectedCount(selected));
                    $('#bookit-export-confirm').prop('disabled', selected === 0);
                }

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
                 * Get the current calendar year as a local `YYYY-MM-DD` range.
                 *
                 * @returns {{startDate: string, endDate: string}}
                 */
                function getCurrentCalendarYearRange() {
                    const now = new Date();
                    const year = now.getFullYear();

                    return {
                        startDate: toLocalDateValue(new Date(year, 0, 1)),
                        endDate: toLocalDateValue(new Date(year, 11, 31))
                    };
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
                 * Uses the modal date range if set, otherwise falls back to the current calendar year.
                 * Merges `window.currentFilterParams` except `start`/`end` (modal range wins).
                 *
                 * @returns {void}
                 */
                function fetchExportList() {
                    const qs = {id: cmId};

                    const startDate = ($('#bookit-export-start').val() || '').trim();
                    const endDate = ($('#bookit-export-end').val() || '').trim();
                    const fallback = getCurrentCalendarYearRange();

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

                    Ajax.call([{
                        methodname: methodName,
                        args: {
                            cmid: cmId,
                            start: qs.start + ':00',
                            end: qs.end + ':00',
                            roomids: (qs.room || '').split(',').filter(Boolean).map(Number),
                            facultyids: (qs.faculty || '').split(',').filter(Boolean).map(Number),
                            bookingstatuses: (qs.status || '').split(',').filter(Boolean).map(Number),
                            search: qs.search || '',
                            exportmode: true
                        }
                    }])[0].then(function(response) {
                        let data = response.events || [];
                        list.empty();

                        data = (data || []).filter(function(e) {
                            return e.extendedProps && e.extendedProps.visibilitymode !== 'reserved_projection';
                        });

                        if (!data.length) {
                            list.append('<div class="text-muted">' + noEventsStr + '</div>');
                            return null;
                        }
                        data.forEach(function(e) {
                            const room = e.extendedProps?.room || {};
                            const roomTxt = [room.roomname || '', room.location || '']
                                .filter(Boolean)
                                .join(' | ')
                                .trim();
                            const faculty = String(e.extendedProps?.faculty?.label || '').trim();
                            const statusTxt = statusMap[String(e.extendedProps?.bookingstatus ?? '')] || '';
                            const startStr = (e.start || '');
                            const dateTxt = startStr ? String(startStr).substr(0, 16).replace('T', ' ') : '';
                            const metaParts = [dateTxt];
                            if (roomTxt) {
                                metaParts.push(roomTxt);
                            }
                            if (faculty) {
                                metaParts.push(faculty);
                            }
                            const metaLine = metaParts.filter(Boolean).join(' | ');

                            const checkbox = '<span class="bookit-export-item-checkbox pr-2">' +
                                '<input class="form-check-input mt-1" type="checkbox" value="' +
                                e.id + '">' +
                                '</span>';
                            const statusBadge = statusTxt
                                ? '<span class="badge badge-light border ml-2">' + statusTxt + '</span>'
                                : '';

                            const row = $(
                                '<label class="list-group-item d-flex align-items-start bookit-export-item px-3 py-2" ' +
                                ' data-room="' + roomTxt.toLowerCase() + '" ' +
                                ' data-faculty="' + faculty.toLowerCase() + '" ' +
                                ' data-status="' + statusTxt.toLowerCase() + '">' +
                                    checkbox +
                                    '<span class="bookit-export-item-text">' +
                                        '<span class="bookit-export-item-title">' +
                                            (e.extendedProps?.titlehtml || e.title || '') +
                                            statusBadge +
                                        '</span>' +
                                        '<small class="text-muted d-block">' + metaLine + '</small>' +
                                    '</span>' +
                                '</label>'
                            );
                            list.append(row);
                        });

                        filterExportList();
                        updateSelectionState();
                        return null;
                    }).catch(function(xhr) {
                        list.empty();
                        list.append('<div class="text-danger">calendar read failed: ' +
                            (xhr.message || xhr.responseText || xhr.status || '') + '</div>');
                        updateSelectionState();
                    });
                }
                /**
                 * Open the export modal and load the initial list for the current calendar year.
                 */
                $(document).on('click', '#bookit-export', function() {
                    const r = getCurrentCalendarYearRange();
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
                 * Reset modal date range to the current calendar year and refresh list.
                 */
                $(document).on('click', '#bookit-export-reset-range', function() {
                    const r = getCurrentCalendarYearRange();
                    $('#bookit-export-start').val(r.startDate);
                    $('#bookit-export-end').val(r.endDate);
                    fetchExportList();
                });

                // Apply live text filter to the export list.
                $(document).on('input', '#bookit-modal-search', filterExportList);

                // Select all visible, enabled event checkboxes.
                $(document).on('click', '#bookit-check-all', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', true);
                    updateSelectionState();
                });

                // Deselect all visible, enabled event checkboxes.
                $(document).on('click', '#bookit-uncheck-all', function() {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', false);
                    updateSelectionState();
                });

                $(document).on('change', '#bookit-export-list input[type=checkbox]', updateSelectionState);

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
                        void Notification.alert('', chooseEventStr);
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

                updateSelectionState();
            });
        }
    };
});
