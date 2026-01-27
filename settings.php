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

use mod_bookit\local\tabs;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bookit/lib.php');

if ($hassiteconfig) {

    // Add hidden bookit category to general section.
    $ADMIN->add('root', new admin_category('bookit_settings_category', '', true));

    $calendarsettings = new admin_externalpage(
            'bookit_calendar_settings',
            get_string('pluginname', 'mod_bookit'),
            new moodle_url('/mod/bookit/admin/calendar.php?id=calendar'),
            'mod/bookit:managemasterchecklist',
    );
    $ADMIN->add('bookit_settings_category', $calendarsettings);

    // NOTE: real admin settings stay here - all other settings under /mod/bookit/admin/... ,
    $context = context_system::instance();
    $tabrow = tabs::get_tabrow($context);
    $id = optional_param('id', 'modsettingbookit', PARAM_TEXT);
    $tabs = [$tabrow];
    $tabsoutput = print_tabs($tabs, $id, null, null, true);

    $settings = new admin_settingpage('modsettingbookit', get_string('pluginname', 'mod_bookit'));
    // Tab row.
    $settings->add(new admin_setting_heading(
            'mod_bookit_tab_nav',
            '',
            $tabsoutput,
    ));
    // TODO: write some text as introduction to bookit.

    // Install helper.
    // TODO: remove next line!!
    set_config('installhelperfinished', 0, 'mod_bookit');
    $installhelperfinished = get_config('mod_bookit', 'installhelperfinished');

    if (empty($installhelperfinished)) {
        $installurl = new moodle_url('/mod/bookit/admin/install_helper_run.php', ['sesskey' => sesskey()]);
        $description = new lang_string('runinstallhelperinfo', 'mod_bookit');
        $description .= \core\output\html_writer::empty_tag('br');
        $description .= \core\output\html_writer::link(
                $installurl,
                new lang_string('runinstallhelper', 'mod_bookit'),
                ['class' => 'btn btn-secondary mt-3', 'role' => 'button']
        );

        $runinstallhelper = new admin_setting_heading(
                'mod_bookit/runinstallhelper',
                new lang_string('runinstallhelper', 'mod_bookit'),
                $description
        );

        $settings->add($runinstallhelper);
    }
}
