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

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_bookit_settings', new lang_string('pluginname', 'mod_bookit'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // ...TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.

        // Room colors heading.
        $name = 'mod_bookit/roomcolorheading';
        $title = get_string('roomcolorheading', 'mod_bookit', null, true);
        $setting = new admin_setting_heading($name, $title, null);
        $settings->add($setting);

        // Set a color for each room defined in resources - at least one.
        // Get the ressources.
        $catresourceslist = resource_manager::get_resources();
        foreach ($catresourceslist as $category => $value) {
            if ($category === 'Rooms') {
                foreach ($value['resources'] as $rid => $catresource) {
                    // $rooms[$rid] = $catresource['name'];
                    $name = 'mod_bookit/roomcolor_'.$rid;
                    $title = get_string('roomcolor', 'mod_bookit', $catresource['name'], true);
                    $description = get_string('roomcolor_desc', 'mod_bookit', null, true);
                    $setting = new admin_setting_configcolourpicker($name, $title, $description, '');
                    $settings->add($setting);
                }
            }
        }



    }
}
