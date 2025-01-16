/**
 * Filter Manager for the calendar view
 */
export class FilterManager {
    constructor() {
        this.filters = {};
    }

    /**
     * Initialize the Filter Manager
     */
    init() {
        document.addEventListener('filterupdate', (event) => {
            this.filters = {
                ...event.detail,
                // Add start/end date if they exist
                start: this.filters.start || null,
                end: this.filters.end || null
            };

            // Dispatch filterchange event
            const filterChangeEvent = new Event('filterchange');
            document.dispatchEvent(filterChangeEvent);
        });
    }

    /**
     * Check if an event matches the filter criteria
     * @param {Object} event - The event object
     * @returns {boolean}
     */
    checkEventVisibility(event) {
        // Extract event data
        const eventData = {
            title: event.title || '',
            room: event.room || '',  // Directly from event object
            faculty: event.department || '',  // Use department instead of faculty
            status: event.status || '',  // Directly from event object
            startTime: event.start ? new Date(event.start) : null
        };

        // Check text search
        if (this.filters.search) {
            const searchTerm = this.filters.search.toLowerCase();
            const searchableText = [
                eventData.title,
                eventData.room,
                eventData.faculty
            ].join(' ').toLowerCase();

            if (!searchableText.includes(searchTerm)) {
                return false;
            }
        }

        // Check room filter
        if (this.filters.room && eventData.room !== this.filters.room) {
            return false;
        }

        // Check timeslot filter
        if (this.filters.timeslot && eventData.startTime) {
            const eventHour = eventData.startTime.getHours();
            const isVisible = this.checkTimeSlot(eventHour, this.filters.timeslot);
            if (!isVisible) {
                return false;
            }
        }

        // Check faculty filter
        if (this.filters.faculty && eventData.faculty !== this.filters.faculty) {
            return false;
        }

        // Check status filter
        if (this.filters.status && eventData.status !== this.filters.status) {
            return false;
        }

        return true;
    }

    /**
     * Check if a time falls within a timeslot
     * @param {number} hour - The hour (0-23)
     * @param {string} timeslot - The timeslot (morning|afternoon|evening)
     * @returns {boolean}
     */
    checkTimeSlot(hour, timeslot) {
        switch (timeslot) {
            case 'morning':
                return hour >= 7 && hour < 12;
            case 'afternoon':
                return hour >= 12 && hour < 17;
            case 'evening':
                return hour >= 17 && hour < 22;
            default:
                return true;
        }
    }
}