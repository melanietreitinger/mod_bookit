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

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

use moodleform;

/**
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2025 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings_calendar_form extends moodleform {
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
                get_string('settings_weekdaysvisible', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/weekdaysvisible</code>'. '<br><br>'.
                get_string('settings_weekdaysvisible_desc', 'mod_bookit'),
                $weekdaychoices,
        );
        $weekdaysvisible->setMultiple(true);
        // Default: Mon-Fri is selected.
        $mform->getElement('weekdaysvisible')->setSelected([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]);

        // Min / max selectable year +/- 10 years.
        $thisyear = (int) date('Y');
        $yearlistmin = array_combine(range($thisyear, $thisyear - 10), range($thisyear, $thisyear - 10));
        $yearlistmax = array_combine(range($thisyear, $thisyear + 10), range($thisyear, $thisyear + 10));

        // Minimum year to select, default last year (service-team only)
        $mform->addElement(
                'select',
                'eventminyear',
                get_string('settings_eventminyear', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/eventminyear</code>'.'<br><br>'.
                get_string('settings_eventminyear_desc', 'mod_bookit'),
                $yearlistmin,
        );
        $mform->getElement('eventminyear')->setSelected(($thisyear-1));

        // Maximum year to select, default next year (service-team only).
        $mform->addElement(
                'select',
                'eventmaxyear',
                get_string('settings_eventmaxyear', 'mod_bookit')
                .'<br>'.
                '<code class="text-muted small">mod_bookit/eventmaxyear</code>'.'<br><br>'.
                get_string('settings_eventmaxyear_desc', 'mod_bookit'),
                $yearlistmax,
        );
        $mform->getElement('eventmaxyear')->setSelected(($thisyear+1));

        // Event default duration, default 60 minutes.
        $mform->addElement(
                'text',
                'eventdefaultduration',
                get_string('settings_eventdefaultduration', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/eventdefaultduration</code>',
                ['size' => 4]
        );
        $mform->setType('eventdefaultduration', PARAM_INT);
        $mform->getElement('eventdefaultduration')->setValue(60);


        // Event max duration, default 480 minutes.
        $mform->addElement(
                'text',
                'eventmaxduration',
                get_string('settings_eventmaxduration', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/eventmaxduration</code>',
                ['size' => 4]
        );
        $mform->setType('eventmaxduration', PARAM_INT);
        $mform->getElement('eventmaxduration')->setValue(480);

        $steparray = [5 => '5', 10 => '10', 15 => '15', 30 => '30', 60 => '60'];

        // Event duration step width in minutes, default 15 minutes.
        $mform->addElement(
                'select',
                'eventdurationstepwidth',
                get_string('settings_eventdurationstepwidth', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/eventdurationstepwidth</code>',
                $steparray,
        );


        // Event startime step width, default 15 minutes.
        $mform->addElement(
                'select',
                'eventstartstepwidth',
                get_string('settings_eventstartstepwidth', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/eventstartstepwidth</code>',
                $steparray,
        );

        // Event extra time before.
        $mform->addElement(
                'text',
                'extratimebefore',
                get_string('settings_extratime_before_desc', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/extratimebefore</code>',
                ['size' => 4],
        );
        $mform->setType('extratimebefore', PARAM_INT);
        $mform->getElement('extratimebefore')->setValue(15);

        // Event extra time after.
        $mform->addElement(
                'text',
                'extratimeafter',
                get_string('settings_extratime_after_desc', 'mod_bookit').'<br>'.
                '<code class="text-muted small">mod_bookit/extratimeafter</code>',
                ['size' => 4],
        );
        $mform->setType('extratimeafter', PARAM_INT);
        $mform->getElement('extratimeafter')->setValue(15);

        $this->add_action_buttons();
    }
}