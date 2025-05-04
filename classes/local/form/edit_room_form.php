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
 * A moodle form for rooms.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_bookit\local\form;

use mod_bookit\local\formelement\colorpicker;
use mod_bookit\local\formelement\colorpicker_rule;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * A moodle form for rooms.
 *
 * @package    mod_bookit
 * @copyright  2025 Justus Dieckmann RUB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_room_form extends \core\form\persistent {

    /** @var string The related persistent class. */
    protected static $persistentclass = 'mod_bookit\\local\\persistent\\room';

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'));

        $mform->addElement('textarea', 'description', get_string('description'));

        colorpicker::register();
        colorpicker_rule::register();
        $mform->addElement('mod_bookit_colorpicker', 'eventcolor', get_string('color', 'mod_bookit'));
        $mform->addRule('eventcolor', get_string('validateerror', 'admin'), 'mod_bookit_colorpicker_rule');

        $mform->addElement('checkbox', 'active', get_string('active', 'mod_bookit'));

        $mform->addElement('select', 'roommode', get_string('roommode', 'mod_bookit'), [
            0 => get_string('roommode_free', 'mod_bookit'),
            1 => get_string('roommode_slots', 'mod_bookit'),
        ]);

        $this->add_action_buttons();
    }

}
