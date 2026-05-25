define(['jquery'], function($) {
    return {
        init: function(errorMessage) {
            var ERR_ID = 'bookit-past-time-error';

            function checkPastTime() {
                var sel = $('select[name="starttime"]');
                if (!sel.length) { return; }
                var val = parseInt(sel.val(), 10);
                var now = Math.floor(Date.now() / 1000);
                var isPast = !isNaN(val) && val > 0 && val < now;

                $('#' + ERR_ID).remove();
                if (isPast) {
                    sel.closest('.form-group, .fitem')
                        .append('<div id="' + ERR_ID
                        + '" class="text-danger small mt-1 mb-0">' + errorMessage + '</div>');
                }

                var modal = sel.closest('.modal');
                var form  = sel.closest('form');
                var btns  = modal.find('.modal-footer [data-action="save"]')
                                .add(form.find('input[name="submitbutton"]'));
                btns.prop('disabled', isPast);
            }

            $(document).on('change', 'select[name="starttime"]', checkPastTime);
            setInterval(checkPastTime, 1000);
        }
    };
});