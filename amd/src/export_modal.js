// File: mod/bookit/amd/src/export_modal.js
define(['jquery', 'core/str'], function($, str) {

    return {
        init: function(cmId) {

            // preload needed strings
            const stringKeys = [
                {key: 'noevents', component: 'mod_bookit'},
                {key: 'chooseevent', component: 'mod_bookit'}
            ];

            str.get_strings(stringKeys).done(function(strings) {
                const noEventsStr = strings[0];
                const chooseEventStr = strings[1];

                function filterExportList() {
                    const val = ($('#bookit-modal-search').val() || '').toLowerCase().trim();
                    $('#bookit-export-list label').each(function () {
                        const $row = $(this);
                        const show = $row.text().toLowerCase().includes(val);
                        $row.toggleClass('d-flex', show)
                             .toggleClass('d-none', !show);
                    });
                }

                $('#bookit-export').on('click', function () {
                    const qs = { id: cmId, start:'1970-01-01T00:00', end:'2100-01-01T00:00' };
                    if (window.currentFilterParams) Object.assign(qs, window.currentFilterParams);

                    const list = $('#bookit-export-list');
                    list.html('<div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i></div>');
                    $('#bookit-export-modal').modal('show');

                    $.getJSON(M.cfg.wwwroot + '/mod/bookit/events.php', qs, function(data){
                        list.empty();

                        // remove reserved events entirely (Fix for Issue #102)
                        data = data.filter(e => !(e.extendedProps && (e.extendedProps.reserved === true || e.extendedProps.reserved === 1)));

                        if (!data.length) {
                            list.append('<div class="text-muted">' + noEventsStr + '</div>');
                            return;
                        }

                        const statusMap = {0:'New',1:'In progress',2:'Accepted',3:'Cancelled',4:'Rejected'};

                        data.forEach(function (e) {
                            const roomTxt = (e.location || e.room || '').trim();
                            const faculty = (e.department || '').trim();
                            const statusTxt = statusMap[e.bookingstatus] || '';
                            const startStr = (e.start || '');
                            const dateTxt = startStr ? startStr.substr(0,16).replace('T',' ') : '';
                            const metaLine = roomTxt ? (roomTxt + ' ' + dateTxt) : dateTxt;

                            const checkbox = '<input class="form-check-input mt-1" type="checkbox" value="'+ e.id +'">';

                            const row = $(
                                '<label class="list-group-item d-flex gap-2 align-items-start" ' +
                                ' data-room="'+ roomTxt.toLowerCase() +'" ' +
                                ' data-faculty="'+ faculty.toLowerCase() +'" ' +
                                ' data-status="'+ statusTxt.toLowerCase() +'">' +
                                    checkbox +
                                    '<span>'+ (e.title || '') +' <small class="text-muted">(' + metaLine + ')</small></span>' +
                                '</label>'
                            );
                            list.append(row);
                        });

                        filterExportList();
                    });
                });

                $('#bookit-modal-search').on('input', filterExportList);

                $('#bookit-check-all').on('click', function () {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', true);
                });
                $('#bookit-uncheck-all').on('click', function () {
                    $('#bookit-export-list label:visible input[type=checkbox]:enabled').prop('checked', false);
                });

                $('#bookit-export-confirm').on('click', function () {
                    const ids = $('#bookit-export-list input[type=checkbox]:enabled:checked')
                        .map(function(){ return this.value; }).get();
                    if (!ids.length) { alert(chooseEventStr); return; }

                    const qs = new URLSearchParams({id: cmId});
                    if (window.currentFilterParams) {
                        Object.entries(window.currentFilterParams).forEach(([k,v]) => qs.append(k, v));
                    }
                    ids.forEach(id => qs.append('ids[]', id));

                    window.location = M.cfg.wwwroot + '/mod/bookit/export_events.php?' + qs.toString();
                    $('#bookit-export-modal').modal('hide');
                });
            });
        }
    };
});
