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
 * Plugin administration pages are defined here.
 *
 * @package     mod_bookit
 * @category    admin
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/lib/formslib.php');


global $ADMIN, $CFG;

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_bookit_settings', new lang_string('pluginname', 'mod_bookit'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Zeitslot-Einstellungen
        $settings->add(new admin_setting_heading(
            'mod_bookit/timeslots',
            new lang_string('timeslots_settings', 'mod_bookit'),
            new lang_string('timeslots_settings_desc', 'mod_bookit')
        ));

        // Früheste Startzeit
        $settings->add(new admin_setting_configtime(
            'mod_bookit/min_time_hour',
            'mod_bookit/min_time_minute',
            new lang_string('min_time', 'mod_bookit'),
            new lang_string('min_time_desc', 'mod_bookit'),
            ['h' => 7, 'm' => 0]
        ));

        // Späteste Endzeit
        $settings->add(new admin_setting_configtime(
            'mod_bookit/max_time_hour',
            'mod_bookit/max_time_minute',
            new lang_string('max_time', 'mod_bookit'),
            new lang_string('max_time_desc', 'mod_bookit'),
            ['h' => 20, 'm' => 0]
        ));

        // Standard-Zeitslotdauer in Minuten
        $settings->add(new admin_setting_configtext(
            'mod_bookit/default_duration',
            new lang_string('default_duration', 'mod_bookit'),
            new lang_string('default_duration_desc', 'mod_bookit'),
            60,
            PARAM_INT
        ));

        // Minimale Zeitslotdauer in Minuten
        $settings->add(new admin_setting_configtext(
            'mod_bookit/min_duration',
            new lang_string('min_duration', 'mod_bookit'),
            new lang_string('min_duration_desc', 'mod_bookit'),
            30,
            PARAM_INT
        ));

        // Maximale Zeitslotdauer in Minuten
        $settings->add(new admin_setting_configtext(
            'mod_bookit/max_duration',
            new lang_string('max_duration', 'mod_bookit'),
            new lang_string('max_duration_desc', 'mod_bookit'),
            240,
            PARAM_INT
        ));
    }
}
