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
 * Implementation of new BookIT admin settings overview - Structure:
 *   - Root entry shown under Plugins → Activity modules → BookIT
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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bookit/lib.php');

if ($hassiteconfig) {
    /*
     * Root entry as an external page.
     */
    $settings = new admin_externalpage(
        'mod_bookit', // Must match admin_externalpage_setup('mod_bookit') in settings_overview.php.
        get_string('pluginname', 'mod_bookit'),
        new moodle_url('/mod/bookit/admin/settings_overview.php')
    );

    /*
     * Hidden bucket to hold the real admin_settingpage instances so they don't
     * show up in the left-hand tree (or the search list) as separate entries.
     */
    $ADMIN->add(
        'modsettings',
        new admin_category('mod_bookit_hidden', get_string('pluginname', 'mod_bookit'), /*hidden*/ true)
    );

    /* --- Helper: render a heading-like select to jump between sub-pages. -------- */
    $buildbookitheadingselect = function (string $active): string {
        $defs = [
            'calendar'  => ['id' => 'mod_bookit_calendar', 'label' => get_string('calendar', 'mod_bookit')],
            'resources' => ['id' => 'mod_bookit_resources', 'label' => get_string('resources', 'mod_bookit')],
            'checklist' => ['id' => 'mod_bookit_checklist', 'label' => get_string('checklist', 'mod_bookit')],
        ];

        // Make a big, bold select that looks like the page heading.
        $select = html_writer::start_tag('select', [
            'class'      => 'form-select form-select-lg fw-bold border-0 p-0',
            'style'      => 'font-size:1.75rem;width:auto;display:inline-block;background-color:transparent;',
            'aria-label' => 'BookIT settings section',
            'onchange'   => 'if(this.value){window.location=this.value;}',
        ]);

        foreach ($defs as $key => $info) {
            $url  = (new moodle_url('/admin/settings.php', ['section' => $info['id']]))->out(false);
            $attr = ['value' => $url];
            if ($key === $active) {
                $attr['selected'] = 'selected';
            }
            $select .= html_writer::tag('option', format_string($info['label']), $attr);
        }
        $select .= html_writer::end_tag('select');

        // Container so it sits nicely under the default page title.
        return html_writer::div($select, 'mb-3');
    };


    /*
    * CALENDAR – event / calendar-behaviour settings
    */
    $calendar = new admin_settingpage('mod_bookit_calendar', get_string('calendar', 'mod_bookit'));

    // Top switcher (Calendar active).
    $calendar->add(new admin_setting_heading(
        'mod_bookit_nav_calendar',
        '',
        $buildbookitheadingselect('calendar')
    ));

    // Event setting eventmaxyears.
    $name        = 'mod_bookit/extratime';
    $title       = get_string('settings_extratime', 'mod_bookit');
    $description = get_string('settings_extratime_desc', 'mod_bookit');
    $calendar->add(new admin_setting_configtext($name, $title, $description, 30, PARAM_INT, 5));

    // Min / max selectable year.
    $thisyear = (int)date('Y');

    $yearlistmin = array_combine(range($thisyear, $thisyear - 10), range($thisyear, $thisyear - 10));
    $yearlistmax = array_combine(range($thisyear, $thisyear + 10), range($thisyear, $thisyear + 10));

    $calendar->add(new admin_setting_configselect(
        'mod_bookit/eventminyears',
        get_string('settings_eventminyears', 'mod_bookit'),
        get_string('settings_eventminyears_desc', 'mod_bookit'),
        $thisyear - 1,
        $yearlistmin
    ));
    $calendar->add(new admin_setting_configselect(
        'mod_bookit/eventmaxyears',
        get_string('settings_eventmaxyears', 'mod_bookit'),
        get_string('settings_eventmaxyears_desc', 'mod_bookit'),
        $thisyear + 1,
        $yearlistmax
    ));
    $calendar->add(new admin_setting_configtext(
            'mod_bookit/eventdefaultduration',
            get_string('settings_eventdefaultduration', 'mod_bookit'),
            null,
            60,
            PARAM_INT
    ));

    $calendar->add(new admin_setting_configtext(
            'mod_bookit/eventmaxduration',
            get_string('settings_eventmaxduration', 'mod_bookit'),
            null,
            480,
            PARAM_INT
    ));

    $calendar->add(new admin_setting_configselect(
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
    // Event setting extra time.
    $calendar->add(new admin_setting_configtext(
            'mod_bookit/extratimebefore',
            new lang_string('settings_extratime_before', 'mod_bookit'),
            new lang_string('settings_extratime_before_desc', 'mod_bookit'),
            15,
            PARAM_INT,
            5
    ));

    $calendar->add(new admin_setting_configtext(
            'mod_bookit/extratimeafter',
            new lang_string('settings_extratime_after', 'mod_bookit'),
            new lang_string('settings_extratime_after_desc', 'mod_bookit'),
            15,
            PARAM_INT,
            5
    ));

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
    $calendar->add(new admin_setting_configmulticheckbox(
        'mod_bookit/weekdaysvisible',
        get_string('settings_weekdaysvisible', 'mod_bookit'),
        get_string('settings_weekdaysvisible_desc', 'mod_bookit'),
        [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5], // Default Mon–Fri.
        $weekdaychoices
    ));

    // Register under hidden container.
    $ADMIN->add('mod_bookit_hidden', $calendar);

    // RESOURCES – colours & room related settings.
    $resources = new admin_settingpage('mod_bookit_resources', get_string('resources', 'mod_bookit'));

    // Top switcher (Resources active).
    $resources->add(new admin_setting_heading(
        'mod_bookit_nav_resources',
        '',
        $buildbookitheadingselect('resources')
    ));

    // Text colour (white / black).
    $resources->add(new admin_setting_configselect(
        'mod_bookit/textcolor',
        get_string('settings_textcolor', 'mod_bookit'),
        get_string('settings_textcolor_desc', 'mod_bookit'),
        '#ffffff',
        ['#ffffff' => 'white', '#000000' => 'black']
    ));

    // Room colour heading.
    $resources->add(new admin_setting_heading(
        'mod_bookit/roomcolorheading',
        get_string('settings_roomcolorheading', 'mod_bookit', null, true),
        ''
    ));

    // One colour-picker per room.
    $catresourceslist = resource_manager::get_resources();
    foreach ($catresourceslist['Rooms']['resources'] ?? [] as $rid => $catresource) {
        $resources->add(new admin_setting_configcolourpicker(
            'mod_bookit/roomcolor_' . $rid,
            get_string('settings_roomcolor', 'mod_bookit', $catresource['name'], true),
            get_string('settings_roomcolor_desc', 'mod_bookit', null, true),
            ''
        ));

        // WCAG helper.
        $fcolor = ltrim(get_config('mod_bookit', 'textcolor') ?: '#ffffff', '#');
        $bcolor = ltrim(get_config('mod_bookit', 'roomcolor_' . $rid) ?: '', '#');
        if ($bcolor !== '') {
            $checkhtml = printcolorevaluation($fcolor, $bcolor);
            $a         = (object)['fcolor' => $fcolor, 'bcolor' => $bcolor];
            $resources->add(new admin_setting_description(
                'mod_bookit/roomcolor_' . $rid . '_wcag',
                get_string('settings_roomcolor_wcagcheck', 'mod_bookit', $rid),
                get_string('settings_roomcolor_wcagcheck_desc', 'mod_bookit', $a) . $checkhtml
            ));
        }
    }

    // Register under hidden container.
    $ADMIN->add('mod_bookit_hidden', $resources);

    // CHECKLIST – checklist management.
    $checklist = new admin_settingpage('mod_bookit_checklist', get_string('checklist', 'mod_bookit'));

        // Top switcher (Checklist active).
    $checklist->add(new admin_setting_heading(
        'mod_bookit_nav_checklist',
        '',
        $buildbookitheadingselect('checklist')
    ));

        // Master checklist link as a description with a prominent link.
    $masterchecklisturl = new moodle_url('/mod/bookit/master_checklist.php');
    $masterchecklistlink = html_writer::link(
        $masterchecklisturl,
        get_string('master_checklist', 'mod_bookit'),
        ['class' => 'btn btn-primary btn-lg mb-3']
    );

    $checklist->add(new admin_setting_description(
        'mod_bookit_master_checklist_link',
        '',
        $masterchecklistlink
    ));

    $checklist->add(new admin_setting_heading(
        'mod_bookit/pdf_checklist_heading',
        get_string('settings_pdf_checklist_heading', 'mod_bookit'),
        ''
    ));

    $checklist->add(new admin_setting_configcheckbox(
        'mod_bookit/pdf_logo_enable',
        get_string('settings_pdf_logo_enable', 'mod_bookit'),
        get_string('settings_pdf_logo_enable_desc', 'mod_bookit'),
        1
    ));

    $logosourceoptions = [
        'site' => get_string('settings_pdf_logo_source_site', 'mod_bookit'),
        'custom' => get_string('settings_pdf_logo_source_custom', 'mod_bookit'),
    ];

    $themeboostunionpath = $CFG->dirroot . '/theme/boost_union';

    if (file_exists($themeboostunionpath) && is_dir($themeboostunionpath)) {
        $logosourceoptions['theme'] = get_string('settings_pdf_logo_source_theme', 'mod_bookit');
    }

    $checklist->add(new admin_setting_configselect(
        'mod_bookit/pdf_logo_source',
        get_string('settings_pdf_logo_source', 'mod_bookit'),
        get_string('settings_pdf_logo_source_desc', 'mod_bookit'),
        'site',
        $logosourceoptions
    ));

    $checklist->add(new admin_setting_configstoredfile(
        'mod_bookit/pdf_logo_custom',
        get_string('settings_pdf_logo_custom', 'mod_bookit'),
        get_string('settings_pdf_logo_custom_desc', 'mod_bookit'),
        'pdf_logo_custom',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg']]
    ));

    $checklist->hide_if('mod_bookit/pdf_logo_custom', 'mod_bookit/pdf_logo_source', 'neq', 'custom');

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

        $checklist->add($runinstallhelper);
    }

    // Register under hidden container.
    $ADMIN->add('mod_bookit_hidden', $checklist);
}
