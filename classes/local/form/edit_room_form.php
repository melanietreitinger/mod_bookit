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
use mod_bookit\local\persistent\room;
use stdClass;

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
        $mform->addRule('name', get_string('required'), 'required');

        $mform->addElement('text', 'shortname', get_string('room_shortname', 'mod_bookit'));
        $mform->addHelpButton('shortname', 'room_shortname', 'mod_bookit');

        $mform->addElement('text', 'seats', get_string('room_seats', 'mod_bookit'));
        $mform->addRule('seats', get_string('required'), 'required');
        $mform->addRule(
            'seats',
            get_string('err_numeric', 'form'),
            'numeric',
            null,
            'client'
        );

        $mform->addElement('textarea', 'description', get_string('description'));

        $mform->addElement('text', 'location', get_string('room_location', 'mod_bookit'));

        colorpicker::register();
        colorpicker_rule::register();
        $mform->addElement('mod_bookit_colorpicker', 'eventcolor', get_string('room_color', 'mod_bookit'));
        $mform->addRule(
            'eventcolor',
            get_string(
                'validateerror',
                'admin'
            ),
            'mod_bookit_colorpicker_rule'
        );

        $mform->addElement('select', 'roommode', get_string('room_mode', 'mod_bookit'), [
                room::MODE_FREE => get_string('room_mode_free', 'mod_bookit'),
                room::MODE_SLOTS => get_string('room_mode_slots', 'mod_bookit'),
                room::MODE_TOP_TO_BOTTOM => get_string('room_mode_top_to_bottom', 'mod_bookit'),
        ]);

        $mform->addElement('select', 'preventoverlap', get_string('room_overlapping_mode', 'mod_bookit'), [
                room::OVERLAPPING_ALLOW_ALL => get_string('room_overlapping_allow_all', 'mod_bookit'),
                room::OVERLAPPING_ALLOW_NON_CONFIRMED => get_string('room_overlapping_non_confirmed', 'mod_bookit'),
                room::OVERLAPPING_ALLOW_NONE => get_string('room_overlapping_allow_none', 'mod_bookit'),
        ]);

        $mform->addElement(
            'checkbox',
            'overwrite_extratimebefore',
            get_string('room_overwrite_extratimebefore', 'mod_bookit')
        );
        $mform->addElement(
            'text',
            'extratimebefore',
            get_string('settings_extratime_before', 'mod_bookit')
        );
        $mform->setDefault('extratimebefore', get_config('bookit', 'extratimebefore'));
        $mform->hideIf('extratimebefore', 'overwrite_extratimebefore');

        $mform->addElement(
            'checkbox',
            'overwrite_extratimeafter',
            get_string('room_overwrite_extratimeafter', 'mod_bookit')
        );
        $mform->addElement(
            'text',
            'extratimeafter',
            get_string('settings_extratime_after', 'mod_bookit')
        );
        $mform->setDefault('extratimeafter', get_config('bookit', 'extratimeafter'));
        $mform->hideIf('extratimeafter', 'overwrite_extratimeafter');

        $mform->addElement('checkbox', 'active', get_string('room_active', 'mod_bookit'));

        $this->add_action_buttons();
    }

    #[\Override]
    protected function get_default_data() {
        $data = parent::get_default_data();

        $record = $this->get_persistent()->to_record();
        $data->overwrite_extratimebefore = $record->extratimebefore != null;
        if ($record->extratimebefore == null) {
            $data->extratimebefore = get_config('mod_bookit', 'extratimebefore');
        }

        $data->overwrite_extratimeafter = $record->extratimeafter != null;
        if ($record->extratimeafter == null) {
            $data->extratimeafter = get_config('mod_bookit', 'extratimeafter');
        }

        return $data;
    }

    #[\Override]
    protected static function convert_fields(stdClass $data) {
        if (empty($data->overwrite_extratimebefore)) {
            $data->extratimebefore = null;
        }
        if (empty($data->overwrite_extratimeafter)) {
            $data->extratimeafter = null;
        }
        // Ensure active is converted to boolean-like int.
        $data->active = empty($data->active) ? 0 : 1;
        return parent::convert_fields($data);
    }
}
