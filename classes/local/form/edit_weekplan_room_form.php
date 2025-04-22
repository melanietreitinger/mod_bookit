<?php
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
 * A moodle form for weekplan assignments to rooms.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\form;

use core\form\persistent;
use mod_bookit\local\persistent\weekplan;
use mod_bookit\local\persistent\weekplan_room;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * A moodle form for weekplan assignments to rooms.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_weekplan_room_form extends persistent {


    /** @var string The related persistent class. */
    protected static $persistentclass = 'mod_bookit\local\persistent\weekplan_room';

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'roomid');
        $mform->setType('roomid', PARAM_INT);
        $mform->setConstant('roomid', $this->_customdata['room']->get('id'));

        $weekplans = weekplan::get_records();
        $options = [];
        foreach ($weekplans as $weekplan) {
            $options[$weekplan->get('id')] = $weekplan->get('name');
        }

        $mform->addElement(
            'select',
            'weekplanid',
            get_string('weekplan', 'mod_bookit'),
            $options,
        );

        $mform->addElement('date_selector', 'starttime', get_string('start_of_period', 'mod_bookit'));

        $mform->addElement('date_selector', 'endtime', get_string('end_of_period', 'mod_bookit'));

        $this->add_action_buttons();
    }

    /**
     * Extra validation for persistent form.
     * @param $data
     * @param $files
     * @param array $errors
     */
    protected function extra_validation($data, $files, array &$errors) {
        if ($data->endtime < $data->starttime) {
            $errors['endtime'] = get_string('end_before_start', 'mod_bookit');
        }

        $collision = (new weekplan_room($this->get_persistent()?->get('id') ?? 0, $data))->check_for_collision();
        if ($collision) {
            $errors['endtime'] = get_string('weekplan_assignment_overlaps', 'mod_bookit');
        }
    }
}
