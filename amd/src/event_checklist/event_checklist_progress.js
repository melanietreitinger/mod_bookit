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
 * Reactive progress bar for event checklist.
 *
 * Listens to state changes and updates the Bootstrap progress bar.
 * Progress = done items / total items.
 *
 * @module mod_bookit/event_checklist/event_checklist_progress
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {BaseComponent} from 'core/reactive';

const SELECTORS = {
    PROGRESSBAR: '[data-region="event-checklist-progressbar"]',
    PROGRESSTEXT: '[data-region="event-checklist-progress-text"]',
};

/**
 * Reactive progress bar for the event checklist.
 */
export default class EventChecklistProgress extends BaseComponent {

    /**
     * State ready: initial calculation.
     */
    stateReady() {
        this._updateProgressBar();
    }

    /**
     * Watch for item done state changes.
     *
     * @return {Array}
     */
    getWatchers() {
        return [
            {watch: 'items.done:updated', handler: this._updateProgressBar.bind(this)},
        ];
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
        let done = 0;
        state.items.forEach((item) => {
            total++;
            if (item.done) {
                done++;
            }
        });

        const percent = total > 0 ? Math.round((done / total) * 100) : 0;
        const complete = total > 0 && done === total;

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
            text.textContent = done;
        }
    }
}
