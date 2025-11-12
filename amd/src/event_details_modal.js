define(['jquery', 'core_form/modalform'], function($, ModalForm) {
    return {
        init: function() {
            document.addEventListener('click', function(e) {
                const link = e.target.closest('.bookit-event-link');
                if (!link){
                    return;
                }

                e.preventDefault();
                const cmid  = link.dataset.cmid;
                const event = link.dataset.eventid;

                const modal = new ModalForm({
                    formClass : 'mod_bookit\\form\\edit_event_form',
                    args : {cmid: cmid, id: event, readonly: 1},
                    modalConfig: {title: link.textContent.trim()}
                });

                modal.addEventListener(modal.events.FORM_SUBMITTED, function() {
                    window.location.reload();
                });

                modal.show();
            });
        }
    };
});
