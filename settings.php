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
 * Implementation of new BookIt admin settings overview - Structure:
 *   - Root entry shown under Plugins → Activity modules → BookIt
 *       => external page: /mod/bookit/admin/settings_overview.php (cards only)
 *   - Actual settings pages (Calendar, Resources, Checklist)
 *       => placed into a hidden admin category so they don't appear in the tree
 *       => will be shown after first click in the settings_overview.php
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\manager\resource_manager;
use mod_bookit\local\install_helper;
use mod_bookit\local\tabs;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bookit/lib.php');

if ($hassiteconfig) {

    // Invisible admin category for bookit to register the settings tabs.
    $ADMIN->add('modsettings', new admin_category('bookit_settings', '', true));

    $settings = new admin_settingpage('bookit', get_string('pluginname', 'mod_bookit'));

    $context = context_system::instance();
    $tabrow = tabs::get_tabrow($context);
    $id = optional_param('id', 'settings', PARAM_TEXT);
    $tabs = [$tabrow];
    $tabsoutput = print_tabs($tabs, $id, null, null, true);


    // Tab row.
    $settings->add(new admin_setting_heading(
        'mod_bookit_nav_calendar',
        '',
        $tabsoutput,
    ));

    // Install helper.
    // TODO: remove next line.
    set_config('installhelperfinished', 0, 'mod_bookit');
    $installhelperfinished = get_config('mod_bookit', 'installhelperfinished');

    if (empty($installhelperfinished)) {
        $installurl = new moodle_url('/mod/bookit/install_helper_run.php', ['sesskey' => sesskey()]);
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

    // Calendar heading.
    $settings->add(new admin_setting_heading('mod_bookit_calendar', get_string('calendar', 'mod_bookit'), ''));

    // Weekday visibility.
    $weekdaychoices = [
            1 => get_string('monday', 'calendar'),
            2 => get_string('tuesday', 'calendar'),
            3 => get_string('wednesday', 'calendar'),
            4 => get_string('thursday', 'calendar'),
            5 => get_string('friday', 'calendar'),
            6 => get_string('saturday', 'calendar'),
            0 => get_string('sunday', 'calendar'),
    ];

    $settings->add(new admin_setting_configmulticheckbox(
            'mod_bookit/weekdaysvisible',
            get_string('settings_weekdaysvisible', 'mod_bookit'),
            get_string('settings_weekdaysvisible_desc', 'mod_bookit'),
            [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], // Default Mon–Fri.
            $weekdaychoices
    ));

    // Min / max selectable year.
    $thisyear = (int) date('Y');
    $yearlistmin = array_combine(range($thisyear, $thisyear - 10), range($thisyear, $thisyear - 10));
    $yearlistmax = array_combine(range($thisyear, $thisyear + 10), range($thisyear, $thisyear + 10));

    $settings->add(new admin_setting_configselect(
        'mod_bookit/eventminyears',
        get_string('settings_eventminyears', 'mod_bookit'),
        get_string('settings_eventminyears_desc', 'mod_bookit'),
        $thisyear - 1,
        $yearlistmin
    ));

    $settings->add(new admin_setting_configselect(
        'mod_bookit/eventmaxyears',
        get_string('settings_eventmaxyears', 'mod_bookit'),
        get_string('settings_eventmaxyears_desc', 'mod_bookit'),
        $thisyear + 1,
        $yearlistmax
    ));

    $settings->add(new admin_setting_configtext(
        'mod_bookit/eventdefaultduration',
        get_string('settings_eventdefaultduration', 'mod_bookit'),
        null,
        60,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_bookit/eventmaxduration',
        get_string('settings_eventmaxduration', 'mod_bookit'),
        null,
        480,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configselect(
        'mod_bookit/eventdurationstepwidth',
        get_string('settings_eventdurationstepwidth', 'mod_bookit'),
        null,
        15,
        [
                5 => '5',
                10 => '10',
                15 => '15',
                30 => '30',
                60 => '60',
        ],
    ));

    $settings->add(new admin_setting_configselect(
        'mod_bookit/eventstartstepwidth',
        get_string('settings_eventstartstepwidth', 'mod_bookit'),
        null,
        15,
        [
                5 => '5',
                10 => '10',
                15 => '15',
                30 => '30',
                60 => '60',
            ],
    ));

    // Event setting extra time.
    $settings->add(new admin_setting_configtext(
        'mod_bookit/extratimebefore',
        new lang_string('settings_extratime_before', 'mod_bookit'),
        new lang_string('settings_extratime_before_desc', 'mod_bookit'),
        15,
        PARAM_INT,
        5
    ));

    $settings->add(new admin_setting_configtext(
        'mod_bookit/extratimeafter',
        new lang_string('settings_extratime_after', 'mod_bookit'),
        new lang_string('settings_extratime_after_desc', 'mod_bookit'),
        15,
        PARAM_INT,
        5
    ));

}
