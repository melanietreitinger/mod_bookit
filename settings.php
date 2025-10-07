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
use mod_bookit\local\install_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bookit/lib.php');

if ($hassiteconfig) {
    $ADMIN->add('modsettings', new admin_category('mod_bookit_category', new lang_string('pluginname', 'mod_bookit')));
    $settings = new admin_settingpage('mod_bookit_settings', new lang_string('general_settings', 'mod_bookit'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // ...TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.

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
    }

    $ADMIN->add('mod_bookit_category', new admin_externalpage(
        'mod_bookit_institutions',
        get_string('institutions', 'mod_bookit'),
        new moodle_url('/mod/bookit/institutions.php'),
        // TODO specify required capability.
    ));

    $ADMIN->add('mod_bookit_category', new admin_externalpage(
        'mod_bookit_rooms',
        get_string('rooms', 'mod_bookit'),
        new moodle_url('/mod/bookit/rooms.php'),
        // TODO specify required capability.
    ));

    $ADMIN->add('mod_bookit_category', new admin_externalpage(
        'mod_bookit_weekplans',
        get_string('weekplans', 'mod_bookit'),
        new moodle_url('/mod/bookit/weekplans.php'),
        // TODO specify required capability.
    ));

    $ADMIN->add('mod_bookit_category', $settings);

    $installhelperfinished = get_config('mod_bookit', 'installhelperfinished');

    if (empty($installhelperfinished)) {
        $runinstallhelper = new admin_setting_configcheckbox(
            'mod_bookit/runinstallhelper',
            new lang_string('runinstallhelper', 'mod_bookit'),
            new lang_string('runinstallhelperinfo', 'mod_bookit'),
            1
        );

        $runinstallhelper->set_updatedcallback(function () {

            $settingstate = get_config('mod_bookit', 'runinstallhelper');

            if (!empty($settingstate)) {
                debugging('Importing default roles for BookIt...');
                $rolesimported = install_helper::import_default_roles(false, false);

                if ($rolesimported) {
                    debugging('Default roles were imported successfully.');
                } else {
                    debugging('No default roles were imported (may already exist).');
                }

                debugging('Setting up default checklist data for BookIt...');
                $result = install_helper::create_default_checklists(false, false);

                if ($result) {
                    debugging('Default checklist data was created successfully.');
                } else {
                    debugging('Default checklist data was not created (may already exist).');
                }
                set_config('installhelperfinished', 1, 'mod_bookit');
                        }
                });

            $settings->add($runinstallhelper);
        }
    }

    if (is_siteadmin() || has_capability('mod/bookit:managemasterchecklist', context_system::instance())) {
        $ADMIN->add('mod_bookit_category', new admin_externalpage(
            'mod_bookit_master_checklist',
            get_string('master_checklist', 'mod_bookit'),
            new moodle_url('/mod/bookit/master_checklist.php')
        ));
    }


    $ADMIN->add('mod_bookit_category', $settings);

    $ADMIN->add('mod_bookit_category', new admin_externalpage(
        'mod_bookit_institutions',
        get_string('institutions', 'mod_bookit'),
        new moodle_url('/mod/bookit/institutions.php'),
        // TODO specify required capability.
    ));

    $ADMIN->add('mod_bookit_category', new admin_externalpage(
        'mod_bookit_rooms',
        get_string('rooms', 'mod_bookit'),
        new moodle_url('/mod/bookit/rooms.php'),
        // TODO specify required capability.
    ));

    $ADMIN->add('mod_bookit_category', new admin_externalpage(
        'mod_bookit_weekplans',
        get_string('weekplans', 'mod_bookit'),
        new moodle_url('/mod/bookit/weekplans.php'),
        // TODO specify required capability.
    ));

    $settings = null;
