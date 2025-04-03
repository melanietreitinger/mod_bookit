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
 * A moodle form for institutions.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\form;


use core\param;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * A moodle form for institutions.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_institution_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('institution_name', 'mod_bookit'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('textarea', 'internalnotes', get_string('internalnotes', 'mod_bookit'));
        $mform->setType('internalnotes', PARAM_TEXT);
        $mform->addHelpButton('internalnotes', 'internalnotes', 'mod_bookit');

        $mform->addElement('checkbox', 'hidden', get_string('institution_hidden', 'mod_bookit'));
        $mform->addHelpButton('hidden', 'institution_hidden', 'mod_bookit');

        $this->add_action_buttons();
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return \stdClass|null submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            $data->hidden = $data->hidden ?? false;
        }
        return $data;
    }

}
