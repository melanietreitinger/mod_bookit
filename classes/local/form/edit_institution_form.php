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
class edit_institution_form extends \core\form\persistent {
    /** @var string The related persistent class. */
    protected static $persistentclass = 'mod_bookit\\local\\persistent\\institution';

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('institution_name', 'mod_bookit'));

        $mform->addElement('textarea', 'internalnotes', get_string('internalnotes', 'mod_bookit'));
        $mform->addHelpButton('internalnotes', 'internalnotes', 'mod_bookit');

        $mform->addElement('checkbox', 'active', get_string('institution_active', 'mod_bookit'));
        $mform->addHelpButton('active', 'institution_active', 'mod_bookit');

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
            // Unchecked checkboxes are not set at all by default.
            $data->active = $data->active ?? false;
        }
        return $data;
    }
}
