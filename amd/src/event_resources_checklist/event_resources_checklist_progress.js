// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Reactive progress bar component for event resources checklist.
 *
 * Subscribes to the event resources checklist reactive store and updates
 * the Bootstrap progress bar whenever a resource status changes.
 * Progress = confirmed resources / total resources.
 *
 * @module mod_bookit/event_resources_checklist/event_resources_checklist_progress
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';

/**
 * Selectors for the progress bar elements.
 */
const SELECTORS = {
    PROGRESSBAR: '[data-region="event-resources-checklist-progressbar"]',
    PROGRESSTEXT: '[data-region="event-resources-checklist-progress-text"]',
};

/**
 * Reactive progress bar for the event resources checklist.
 *
 * Listens to any state change in the items map and recalculates
 * the confirmed/total ratio, updating the Bootstrap progress bar.
 */
export default class EventResourcesChecklistProgress extends BaseComponent {

    /**
     * Watch for any status update in the items map.
     *
     * @return {Array}
     */
    getWatchers() {
        return [
            {watch: 'items.status:updated', handler: this._updateProgressBar.bind(this)},
        ];
    }

    /**
     * Initial render after state is ready.
     */
    stateReady() {
        this._updateProgressBar();
    }

    /**
     * Recalculate and update the progress bar.
     */
    _updateProgressBar() {
        const state = this.reactive.stateManager.state;
        if (!state || !state.items) {
            return;
        }

        let total = 0;
        let confirmed = 0;
        state.items.forEach((item) => {
            total++;
            if (item.status === 'confirmed') {
                confirmed++;
            }
        });

        const percent = total > 0 ? Math.round((confirmed / total) * 100) : 0;
        const complete = total > 0 && confirmed === total;

        const bar = this.getElement(SELECTORS.PROGRESSBAR);
        const text = this.getElement(SELECTORS.PROGRESSTEXT);

        if (!bar) {
            return;
        }

        bar.style.width = percent + '%';
        bar.setAttribute('aria-valuenow', percent);
        bar.textContent = percent + '%';

        if (complete) {
            bar.classList.add('bg-success');
        } else {
            bar.classList.remove('bg-success');
        }

        if (text) {
            text.textContent = confirmed;
        }
    }
}
