<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Form for the calendar admin settings.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

use moodleform;

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
/**
 * @SuppressWarnings(PHPMD)
 */
class settings_calendar_form extends moodleform {
// phpcs:enable moodle.Commenting.ValidTags.Invalid,moodle.Commenting.DocblockDescription.Missing
    /**
     * Define the form
     */
    public function definition(): void {
        $mform =& $this->_form;

        // Weekday visibility in calendar.
        $weekdaychoices = [
                1 => get_string('monday', 'calendar'),
                2 => get_string('tuesday', 'calendar'),
                3 => get_string('wednesday', 'calendar'),
                4 => get_string('thursday', 'calendar'),
                5 => get_string('friday', 'calendar'),
                6 => get_string('saturday', 'calendar'),
                0 => get_string('sunday', 'calendar'),
        ];

        $weekdaysvisible = $mform->addElement(
            'select',
            'weekdaysvisible',
            get_string('settings_weekdaysvisible', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/weekdaysvisible</code>',
            $weekdaychoices,
        );
        $weekdaysvisible->setMultiple(true);
        $mform->addElement(
            'static',
            'weekdaysvisible_desc',
            '',
            \html_writer::div(get_string('settings_weekdaysvisible_desc', 'mod_bookit'), 'mb-0')
        );
        // Default: Mon-Fri is selected.
        $mform->getElement('weekdaysvisible')->setSelected([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]);

        $this->add_action_buttons();
    }
}
