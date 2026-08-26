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
use mod_bookit\local\install_helper;

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

    /*$eventssettings = new admin_externalpage(
        'bookit_events_settings',
        get_string('events', 'mod_bookit'),
        new moodle_url('/mod/bookit/admin/events.php?id=events'),
        'mod/bookit:managebasics',
    );
    $ADMIN->add('bookit_settings_category', $eventssettings);*/

    // NOTE: real admin settings stay here - all other settings under /mod/bookit/admin/... .
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
    // XXX TODO: write some text as introduction to bookit.

    $settings->add(new admin_setting_heading(
        'mod_bookit_optional_parts_heading',
        get_string('optional_plugin_parts_heading', 'mod_bookit'),
        get_string('optional_plugin_parts_desc', 'mod_bookit')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_bookit/' . install_helper::CONFIG_RESOURCES_ENABLED,
        get_string('optional_resources_enabled', 'mod_bookit'),
        get_string('optional_resources_enabled_desc', 'mod_bookit'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_bookit/' . install_helper::CONFIG_CHECKLIST_ENABLED,
        get_string('optional_checklist_enabled', 'mod_bookit'),
        get_string('optional_checklist_enabled_desc', 'mod_bookit'),
        0
    ));
    $installhelperfinished = (int)get_config('mod_bookit', 'installhelperfinished');
    $installhelperstatus = optional_param('installhelperstatus', '', PARAM_ALPHAEXT);
    $rolesimported = optional_param('rolesimported', 0, PARAM_INT);
    $rolesskipped = optional_param('rolesskipped', 0, PARAM_INT);
    $baselinecreated = optional_param('baselinecreated', 0, PARAM_INT);
    $baselineverified = optional_param('baselineverified', 0, PARAM_INT);
    $installhelpererrors = optional_param('installhelpererrors', 0, PARAM_INT);
    $installhelperdetails = trim((string)optional_param('installhelperdetails', '', PARAM_TEXT));

    $settings->add(new admin_setting_heading(
        'mod_bookit_installation_heading',
        get_string('installation_heading', 'mod_bookit'),
        get_string('installation_heading_desc', 'mod_bookit')
    ));

    $statushtml = '';
    if ($installhelperstatus !== '') {
        $summarykey = 'runinstallhelper_result_' . $installhelperstatus;
        $summary = get_string($summarykey, 'mod_bookit', (object)[
            'rolesimported' => $rolesimported,
            'rolesskipped' => $rolesskipped,
            'baselinecreated' => $baselinecreated,
            'baselineverified' => $baselineverified,
            'errors' => $installhelpererrors,
        ]);
        $statushtml .= \core\output\html_writer::div(
            $summary,
            'alert ' . (
                $installhelperstatus === 'failed' ? 'alert-danger' :
                    ($installhelperstatus === 'partial' ? 'alert-warning' : 'alert-success')
            )
        );
        if ($installhelperdetails !== '') {
            $statushtml .= \core\output\html_writer::div(s($installhelperdetails), 'text-muted small');
        }
    } else if ($installhelperfinished !== 0) {
        $statushtml .= \core\output\html_writer::div(
            get_string('runinstallhelper_ready_state', 'mod_bookit'),
            'alert alert-success'
        );
    }

    $installurl = new moodle_url('/mod/bookit/admin/install_helper_run.php', ['sesskey' => sesskey()]);
    $rolearray = [];
    foreach (\mod_bookit\local\install_helper::get_default_role_preset_filenames() as $presetfile) {
        $url = new moodle_url('/mod/bookit/assets/roles/' . $presetfile);
        $link = \html_writer::link(
            $url,
            $presetfile,
            ['class' => 'font-weight-bold', 'download' => $presetfile]
        );
        // Extract role key from filename (e.g. "bookit_bookingperson.xml" -> "bookingperson").
        $rolekey = preg_replace('/^bookit_/', '', $presetfile);
        $rolekey = preg_replace('/\.xml$/', '', $rolekey);
        $helpicon = $OUTPUT->help_icon('rolepreset_' . $rolekey, 'mod_bookit');
        $rolearray[] = \html_writer::tag('li', $link . $helpicon);
    }

    $description = $statushtml;
    $description .= \core\output\html_writer::div(get_string('runinstallhelperinfo', 'mod_bookit'));
    $description .= \core\output\html_writer::link(
        $installurl,
        new lang_string('runinstallhelper', 'mod_bookit'),
        ['class' => 'btn btn-secondary mt-3 mb-3', 'role' => 'button']
    );
    $description .= \core\output\html_writer::tag(
        'div',
        get_string('rolepresetdownloads', 'mod_bookit') . ': '
            . \html_writer::tag('ul', \implode('', $rolearray), ['class' => 'mb-0']),
        ['class' => 'mt-3']
    );

    $settings->add(new admin_setting_heading(
        'mod_bookit/runinstallhelper',
        '',
        $description
    ));
}
