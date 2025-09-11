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
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\manager\resource_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bookit/lib.php');

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_bookit_settings', new lang_string('pluginname', 'mod_bookit'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // ...TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.

        // Event setting extra time.
        $name = 'mod_bookit/extratime';
        $title = get_string('settings_extratime', 'mod_bookit');
        $description = get_string('settings_extratime_desc', 'mod_bookit');
        $setting = new admin_setting_configtext($name, $title, $description, 30, PARAM_INT, 5);
        $settings->add($setting);

        // Event setting eventminyears.
        $name = 'mod_bookit/eventminyears';
        $title = get_string('settings_eventminyears', 'mod_bookit');
        $description = get_string('settings_eventminyears_desc', 'mod_bookit');
        $minyearlist = [];
        for ($i = date('Y'); $i >= date('Y', strtotime('-10 year')); $i--) {
            $minyearlist[$i] = $i;
        }
        $setting = new admin_setting_configselect($name, $title, $description, date('Y', strtotime('-1 year')), $minyearlist);
        $settings->add($setting);

        // Event setting eventmaxyears.
        $name = 'mod_bookit/eventmaxyears';
        $title = get_string('settings_eventmaxyears', 'mod_bookit');
        $description = get_string('settings_eventmaxyears_desc', 'mod_bookit');
        $minyearlist = [];
        for ($i = date('Y'); $i <= date('Y', strtotime('+10 year')); $i++) {
            $minyearlist[$i] = $i;
        }
        $setting = new admin_setting_configselect($name, $title, $description, date('Y', strtotime('+1 year')), $minyearlist);
        $settings->add($setting);

                //NEU ANFANG
                $weekdaychoices = [
                    1 => get_string('monday',    'calendar'),
                    2 => get_string('tuesday',   'calendar'),
                    3 => get_string('wednesday', 'calendar'),
                    4 => get_string('thursday',  'calendar'),
                    5 => get_string('friday',    'calendar'),
                    6 => get_string('saturday',  'calendar'),
                    0 => get_string('sunday',    'calendar'),
                ];
                $settings->add(new admin_setting_configmulticheckbox(
                    'mod_bookit/weekdaysvisible',
                    get_string('config_weekdaysvisible', 'mod_bookit'),
                    get_string('config_weekdaysvisible_desc', 'mod_bookit'),
                    [  // Default: Monday‑Friday
                        1 => 1,
                        2 => 2,
                        3 => 3,
                        4 => 4,
                        5 => 5
                    ],
                    $weekdaychoices
                ));
                
                //NEU ENDE
               

        // Room colors heading.
        $name = 'mod_bookit/roomcolorheading';
        $title = get_string('settings_roomcolorheading', 'mod_bookit', null, true);
        $setting = new admin_setting_heading($name, $title, null);
        $settings->add($setting);

        // Set text color to black or white (default).
        $name = 'mod_bookit/textcolor';
        $title = get_string('settings_textcolor', 'mod_bookit');
        $description = get_string('settings_textcolor_desc', 'mod_bookit');
        $choices = ['#ffffff' => 'white', '#000000' => 'black'];
        $setting = new admin_setting_configselect($name, $title, $description, '#ffffff', $choices);
        $settings->add($setting);


        // Set a color for each room defined in resources - at least one.
        // Get the ressources.
        $catresourceslist = resource_manager::get_resources();
       

       
        foreach ($catresourceslist as $category => $value) {
            if ($category === 'Rooms') {
                foreach ($value['resources'] as $rid => $catresource) {
                    $name = 'mod_bookit/roomcolor_' . $rid;
                    $title = get_string('settings_roomcolor', 'mod_bookit', $catresource['name'], true);
                    $description = get_string('settings_roomcolor_desc', 'mod_bookit', null, true);
                    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
                    $settings->add($setting);

                    // Add color contrast check.
                    $fcolor = get_config('mod_bookit', 'textcolor');
                    $fcolor = (!empty($fcolor) ? substr($fcolor, 1) : 'FFFFFF');
                    $bcolor = get_config('mod_bookit', 'roomcolor_' . $rid);
                    $bcolor = (!empty($bcolor) ? substr($bcolor, 1) : false);
                    if (!empty($bcolor)) {
                        $check = printcolorevaluation($fcolor, $bcolor);
                        $a = new StdClass();
                        $a->fcolor = $fcolor;
                        $a->bcolor = $bcolor;
                        $setting = new admin_setting_description($name . '_wcag',
                                get_string('settings_roomcolor_wcagcheck', 'mod_bookit', $rid),
                                get_string('settings_roomcolor_wcagcheck_desc', 'mod_bookit', $a) . $check);
                        $settings->add($setting);
                    }
                }
            }
        }

    }
}
