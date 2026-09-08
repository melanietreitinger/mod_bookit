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
                '<code class="text-muted small">mod_bookit/weekdaysvisible</code>' . '<br><br>' .
                get_string('settings_weekdaysvisible_desc', 'mod_bookit'),
            $weekdaychoices,
        );
        $weekdaysvisible->setMultiple(true);
        // Default: Mon-Fri is selected.
        $mform->getElement('weekdaysvisible')->setSelected([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]);

        // Min / max selectable year relative to the current year (Implements #211)
        $yearlistmin = [
            0 => get_string('settings_eventyear_current', 'mod_bookit'),
            -1 => get_string('settings_eventyear_minus1', 'mod_bookit'),
            -2 => get_string('settings_eventyear_minus2', 'mod_bookit'),
        ];
        $yearlistmax = [
            0 => get_string('settings_eventyear_current', 'mod_bookit'),
            1 => get_string('settings_eventyear_plus1', 'mod_bookit'),
            2 => get_string('settings_eventyear_plus2', 'mod_bookit'),
        ];

        // Minimum year to select, default last year (service-team only).
        $mform->addElement(
            'select',
            'eventminyear',
            get_string('settings_eventminyear', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/eventminyear</code>' . '<br><br>' .
                get_string('settings_eventminyear_desc', 'mod_bookit'),
            $yearlistmin,
        );

        $mform->getElement('eventminyear')->setSelected(-1);
        
        // Maximum year to select, default next year (service-team only).
        $mform->addElement(
            'select',
            'eventmaxyear',
            get_string('settings_eventmaxyear', 'mod_bookit')
                . '<br>' .
                '<code class="text-muted small">mod_bookit/eventmaxyear</code>' . '<br><br>' .
                get_string('settings_eventmaxyear_desc', 'mod_bookit'),
            $yearlistmax,
        );
        $mform->getElement('eventmaxyear')->setSelected(1);

        // Event default duration, default 60 minutes.
        $mform->addElement(
            'text',
            'eventdefaultduration',
            get_string('settings_eventdefaultduration', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/eventdefaultduration</code>',
            ['size' => 4]
        );
        $mform->setType('eventdefaultduration', PARAM_INT);
        $mform->getElement('eventdefaultduration')->setValue(60);

        // Event max duration, default 480 minutes.
        $mform->addElement(
            'text',
            'eventmaxduration',
            get_string('settings_eventmaxduration', 'mod_bookit') . '<br>' .
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
            get_string('settings_eventdurationstepwidth', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/eventdurationstepwidth</code>',
            $steparray,
        );

        // Event startime step width, default 15 minutes.
        $mform->addElement(
            'select',
            'eventstartstepwidth',
            get_string('settings_eventstartstepwidth', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/eventstartstepwidth</code>',
            $steparray,
        );

        // Event extra time before.
        $mform->addElement(
            'text',
            'extratimebefore',
            get_string('settings_extratime_before_desc', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/extratimebefore</code>',
            ['size' => 4],
        );
        $mform->setType('extratimebefore', PARAM_INT);
        $mform->getElement('extratimebefore')->setValue(15);

        // Event extra time after.
        $mform->addElement(
            'text',
            'extratimeafter',
            get_string('settings_extratime_after_desc', 'mod_bookit') . '<br>' .
                '<code class="text-muted small">mod_bookit/extratimeafter</code>',
            ['size' => 4],
        );
        $mform->setType('extratimeafter', PARAM_INT);
        $mform->getElement('extratimeafter')->setValue(15);

                // ---- Calendar display per view (overlap + summary; placeholders for planned modes) ----
        $on      = \html_writer::span('&#10003;', 'text-success');
        $planned = \html_writer::span(get_string('settings_display_planned', 'mod_bookit'), 'text-muted');
        $na      = \html_writer::span(get_string('settings_display_na', 'mod_bookit'), 'text-muted');

        // At-a-glance matrix (rows = views, columns = display modes).
        $cols = [
            '',
            get_string('settings_overlap_label', 'mod_bookit'),
            get_string('settings_summary_label', 'mod_bookit'),
            get_string('settings_maxevents_label', 'mod_bookit'),
            get_string('settings_vertical_label', 'mod_bookit'),
        ];
        $matrix = [
            [get_string('settings_display_day', 'mod_bookit'),   $on, $on, $on, $planned],
            [get_string('settings_display_week', 'mod_bookit'),  $on, $on, $on, $planned],
            [get_string('settings_display_month', 'mod_bookit'), $na, $on, $on, $planned],
        ];
        $thead = '';
        foreach ($cols as $c) {
            $thead .= \html_writer::tag('th', $c, ['class' => 'small p-2']);
        }
        $tbody = '';
        foreach ($matrix as $row) {
            $cells = \html_writer::tag('th', $row[0], ['class' => 'small p-2 text-nowrap']);
            for ($i = 1; $i < count($row); $i++) {
                $cells .= \html_writer::tag('td', $row[$i], ['class' => 'small p-2 text-center']);
            }
            $tbody .= \html_writer::tag('tr', $cells);
        }
        $overviewtable = \html_writer::tag(
            'table',
            \html_writer::tag('thead', \html_writer::tag('tr', $thead)) . \html_writer::tag('tbody', $tbody),
            ['class' => 'table table-sm table-bordered w-auto mt-2 mb-3']
        );

        $mform->addElement(
            'static',
            'displayoverview',
            get_string('settings_display_heading', 'mod_bookit'),
            \html_writer::div(get_string('settings_display_heading_desc', 'mod_bookit'), 'mb-2') . $overviewtable
        );

        $overlapchoices = [
            1 => get_string('settings_overlap_overlapping', 'mod_bookit'),
            0 => get_string('settings_overlap_separated', 'mod_bookit'),
        ];
        $summarychoices = [
            0 => get_string('settings_summary_off', 'mod_bookit'),
            1 => get_string('settings_summary_on', 'mod_bookit'),
        ];

        // Day view.
        $mform->addElement('header', 'displayday', get_string('settings_display_day', 'mod_bookit'));
        $mform->setExpanded('displayday', true);
        $mform->addElement('select', 'eventoverlap_day', get_string('settings_overlap_label', 'mod_bookit'), $overlapchoices);
        $mform->getElement('eventoverlap_day')->setSelected(1);
        $mform->addElement('select', 'summary_day', get_string('settings_summary_label', 'mod_bookit'), $summarychoices);
        $mform->getElement('summary_day')->setSelected(0);
        $mform->addElement('text', 'maxevents_day', get_string('settings_maxevents_label', 'mod_bookit'), ['size' => 4]);
        $mform->setType('maxevents_day', PARAM_INT);
        $mform->getElement('maxevents_day')->setValue(0);

        // Week view.
        $mform->addElement('header', 'displayweek', get_string('settings_display_week', 'mod_bookit'));
        $mform->setExpanded('displayweek', true);
        $mform->addElement('select', 'eventoverlap_week', get_string('settings_overlap_label', 'mod_bookit'), $overlapchoices);
        $mform->getElement('eventoverlap_week')->setSelected(1);
        $mform->addElement('select', 'summary_week', get_string('settings_summary_label', 'mod_bookit'), $summarychoices);
        $mform->getElement('summary_week')->setSelected(0);
        $mform->addElement('text', 'maxevents_week', get_string('settings_maxevents_label', 'mod_bookit'), ['size' => 4]);
        $mform->setType('maxevents_week', PARAM_INT);
        $mform->getElement('maxevents_week')->setValue(0);

        // Month view (no time overlap in dayGrid).
        $mform->addElement('header', 'displaymonth', get_string('settings_display_month', 'mod_bookit'));
        $mform->setExpanded('displaymonth', true);
        $mform->addElement('static', 'overlap_month_na', get_string('settings_overlap_label', 'mod_bookit'),
            get_string('settings_display_na', 'mod_bookit'));
        $mform->addElement('select', 'summary_month', get_string('settings_summary_label', 'mod_bookit'), $summarychoices);
        $mform->getElement('summary_month')->setSelected(0);
        $mform->addElement('text', 'maxevents_month', get_string('settings_maxevents_label', 'mod_bookit'), ['size' => 4]);
        $mform->setType('maxevents_month', PARAM_INT);
        $mform->getElement('maxevents_month')->setValue(0);
        $this->add_action_buttons();
    }
}
