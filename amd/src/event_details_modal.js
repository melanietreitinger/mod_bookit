define([
    'jquery',
    'core_form/modalform',
    'mod_bookit/possible_slots_refresh',
    'mod_bookit/booking_form_resources'
], function($, ModalForm, PossibleSlotsRefresh, BookingFormResources) {
    return {
        init: function() {
            document.addEventListener('click', function(e) {
                const link = e.target.closest('.bookit-event-link');
                if (!link) {
                    return;
                }

                e.preventDefault();
                if (link.dataset.isReservedProjection === '1') {
                    return;
                }

                const cmid = link.dataset.cmid;
                const event = link.dataset.eventid;
                const saveButtonText = link.dataset.saveButtonText;
                const cancelButtonText = link.dataset.cancelButtonText;

                const modalConfig = {
                    formClass: 'mod_bookit\\form\\edit_event_form',
                    args: {cmid: cmid, id: event, readonly: 1},
                    modalConfig: {title: link.textContent.trim()}
                };
                if (saveButtonText) {
                    modalConfig.saveButtonText = saveButtonText;
                }

                const modal = new ModalForm(modalConfig);

                modal.addEventListener(modal.events.LOADED, function() {
                    PossibleSlotsRefresh.initPossibleStarttimesRefresh(cmid, event);
                    if (modal.modal) {
                        BookingFormResources.init(modal.modal.getRoot()[0]);
                    }

                    if (!cancelButtonText || !saveButtonText || !modal.modal) {
                        return;
                    }

                    const modalRoot = modal.modal.getRoot()[0];
                    const footer = modalRoot.querySelector('.modal-footer');
                    if (!footer) {
                        return;
                    }

                    const submitButton = footer.querySelector('[data-action="save"]');
                    const dismissButton = footer.querySelector('[data-action="cancel"]');
                    if (!submitButton || !dismissButton) {
                        return;
                    }

                    // Cancel-only mode: left grey submit (Cancel booking), right blue dismiss (Keep booking).
                    submitButton.textContent = saveButtonText;
                    submitButton.classList.remove('btn-primary');
                    submitButton.classList.add('btn-secondary');

                    dismissButton.textContent = cancelButtonText;
                    dismissButton.classList.remove('btn-secondary');
                    dismissButton.classList.add('btn-primary');

                    footer.insertBefore(submitButton, dismissButton);
                });

                modal.addEventListener(modal.events.FORM_SUBMITTED, function() {
                    window.location.reload();
                });

                modal.show();
            });
        }
    };
});
