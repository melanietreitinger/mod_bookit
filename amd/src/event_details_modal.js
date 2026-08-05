define([
    'mod_bookit/event_modal_opener'
], function(EventModalOpener) {
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

                EventModalOpener.openEditEventModal({
                    cmid: link.dataset.cmid,
                    eventid: link.dataset.eventid,
                    title: link.textContent.trim(),
                    modalfootermode: link.dataset.modalFooterMode || 'editable',
                });
            });
        }
    };
});
