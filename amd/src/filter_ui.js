import {call as fetchMany} from 'core/ajax';
import {getStrings} from 'core/str';
import Templates from 'core/templates';

/**
 * FilterUI class to manage the filter interface
 */
export class FilterUI {
    constructor() {
        this.strings = {};
        this.filters = {
            search: '',
            room: '',
            timeslot: '',
            faculty: '',
            status: ''
        };
        this.hasAdminAccess = false;
    }

    /**
     * Initialize the filter UI
     * @param {boolean} hasAdminAccess - Whether the user has admin access
     * @returns {Promise}
     */
    async init(hasAdminAccess = false) {
        this.hasAdminAccess = hasAdminAccess;

        // Load strings first
        await this.loadStrings();

        // Render the UI
        await this.render();

        // Load filter data
        await this.loadFilterData();

        // Attach event listeners
        this.attachEventListeners();
    }

    /**
     * Load filter data from server
     * @returns {Promise}
     */
    async loadFilterData() {
        const [rooms, faculties, statuses] = await Promise.all([
            this.fetchRooms(),
            this.fetchFaculties(),
            this.fetchStatuses()
        ]);

        const timeslots = [
            {id: 'morning', name: this.strings.morning},
            {id: 'afternoon', name: this.strings.afternoon},
            {id: 'evening', name: this.strings.evening}
        ];

        this.updateOptions({
            room: rooms || [],
            timeslot: timeslots,
            faculty: faculties || [],
            status: statuses || []
        });
    }

    /**
     * Fetch rooms from server
     * @returns {Promise}
     */
    async fetchRooms() {
        const response = await fetchMany([{
            methodname: 'mod_bookit_get_rooms',
            args: {}
        }]);
        return response[0];
    }

    /**
     * Fetch faculties from server
     * @returns {Promise}
     */
    async fetchFaculties() {
        const response = await fetchMany([{
            methodname: 'mod_bookit_get_faculties',
            args: {}
        }]);
        return response[0];
    }

    /**
     * Fetch statuses from server
     * @returns {Promise}
     */
    async fetchStatuses() {
        try {
            const response = await fetchMany([{
                methodname: 'mod_bookit_get_statuses',
                args: {}
            }]);
            return response[0];
        } catch (error) {
            return [];
        }
    }

    /**
     * Load required strings
     * @returns {Promise}
     */
    async loadStrings() {
        const requiredStrings = [
            {key: 'filter_search', component: 'mod_bookit'},
            {key: 'filter_search_placeholder', component: 'mod_bookit'},
            {key: 'filter_room', component: 'mod_bookit'},
            {key: 'filter_timeslot', component: 'mod_bookit'},
            {key: 'filter_faculty', component: 'mod_bookit'},
            {key: 'filter_status', component: 'mod_bookit'},
            {key: 'morning', component: 'mod_bookit'},
            {key: 'afternoon', component: 'mod_bookit'},
            {key: 'evening', component: 'mod_bookit'},
            {key: 'all_entries', component: 'mod_bookit'}
        ];

        const strings = await getStrings(requiredStrings);

        this.strings = {
            search: strings[0],
            searchPlaceholder: strings[1],
            room: strings[2],
            timeslot: strings[3],
            faculty: strings[4],
            status: strings[5],
            morning: strings[6],
            afternoon: strings[7],
            evening: strings[8],
            select: strings[9]
        };
    }

    /**
     * Render the filter UI
     * @returns {Promise}
     */
    async render() {
        const templateContext = {
            strings: this.strings || {},
            hasAdminAccess: this.hasAdminAccess || false
        };

        const html = await Templates.render('mod_bookit/filter_ui', templateContext);

        let container = document.querySelector('.filter-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'filter-container';
            document.querySelector('#ec')?.insertAdjacentElement('beforebegin', container);
        }

        container.outerHTML = html;
    }

    /**
     * Update filter options
     * @param {Object} options - Filter options for all filter types
     */
    updateOptions(options) {
        Object.entries(options).forEach(([filterType, values]) => {
            const select = document.querySelector(`select[name="${filterType}"]`);
            if (!select) {
                return;
            }

            // Clear existing options
            select.innerHTML = '';

            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = this.strings.select || '---';
            select.appendChild(defaultOption);

            // Add new options
            if (Array.isArray(values)) {
                values.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.id || option;
                    optionElement.textContent = option.name || option;
                    select.appendChild(optionElement);
                });
            }
        });
    }

    /**
     * Attach event listeners to filter elements
     */
    attachEventListeners() {
        // Search input
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.dispatchFilterUpdate();
            });
        }

        // Select filters
        ['room', 'timeslot', 'faculty', 'status'].forEach(filterType => {
            const select = document.querySelector(`select[name="${filterType}"]`);
            if (select) {
                select.addEventListener('change', () => {
                    this.dispatchFilterUpdate();
                });
            }
        });
    }

    /**
     * Dispatch filter update event
     */
    dispatchFilterUpdate() {
        const filters = {
            search: document.querySelector('input[name="search"]')?.value || '',
            room: document.querySelector('select[name="room"]')?.value || '',
            timeslot: document.querySelector('select[name="timeslot"]')?.value || '',
            faculty: document.querySelector('select[name="faculty"]')?.value || '',
            status: document.querySelector('select[name="status"]')?.value || ''
        };

        const event = new CustomEvent('filterupdate', {
            detail: filters
        });
        document.dispatchEvent(event);
    }
}