define(['jquery'], function($) {
    return {
        init: function(errorMessage) {
            var ERR_ID = 'bookit-past-time-error';
            /*
            This is an AMD/Moodle JavaScript module that prevents users from submitting a booking form if the selected start time is in the past. 
            It runs a check every second (and on every dropdown change) comparing the selected timestamp against the current time, showing an error message and disabling the save/submit button when the time has already passed.
            */
            function checkPastTime() {
                var sel = $('select[name="starttime"]');
                if (!sel.length) { 
                    return; 
                }
                var val = parseInt(sel.val(), 10);
                var now = Math.floor(Date.now() / 1000);
                var isPast = !isNaN(val) && val > 0 && val < now;

                $('#' + ERR_ID).remove();
                if (isPast) {
                    sel.closest('.felement, .col-md-9')
                        .append('<div id="' + ERR_ID
                        + '" class="text-danger small mt-1 mb-0">' + errorMessage + '</div>');
                }
                var modal = sel.closest('.modal');
                var form = sel.closest('form');
                var btns = modal.find('.modal-footer [data-action="save"]')
                                .add(form.find('input[name="submitbutton"]'));
                btns.prop('disabled', isPast);
            }

            $(document).on('change', 'select[name="starttime"]', checkPastTime);
            setInterval(checkPastTime, 1000);
        }
    };
});