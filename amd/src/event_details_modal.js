define(['jquery', 'core_form/modalform'], function($, ModalForm) {
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
                    if (!cancelButtonText || !modal.modal) {
                        return;
                    }

                    const modalRoot = modal.modal.getRoot()[0];
                    const cancelButton = modalRoot.querySelector('[data-action="cancel"]');
                    if (cancelButton) {
                        cancelButton.textContent = cancelButtonText;
                    }
                });

                modal.addEventListener(modal.events.FORM_SUBMITTED, function() {
                    window.location.reload();
                });

                modal.show();
            });
        }
    };
});
