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
 *  ┌────────────────┐         ┌──────────────────┐
 *  │  Overview card │ ─▶ … ─▶ │  Sub-settings    │
 *  └────────────────┘         └──────────────────┘
 *
 *  Three cards  →  three sub-pages
 *  ─ Calendar
 *  ─ Resources
 *  ─ Checklist  (placeholder for the optional checklist add-on)
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_bookit\local\manager\resource_manager;

require_once($CFG->dirroot . '/mod/bookit/lib.php');

if ($hassiteconfig) {

    /**********************************************************************
     * 0.  Create a parent category “BookIT” 
     *********************************************************************/
    $ADMIN->add(
        'modsettings',
        new admin_category('mod_bookit', get_string('pluginname', 'mod_bookit'))
    );

    /**********************************************************************
     * 1.  Nice looking overview page with three coloured cards
     *********************************************************************/
    $overview = new admin_settingpage('mod_bookit_overview', get_string('overview', 'mod_bookit'));

    // ------------------------------------------------------------------
    // Inline HTML for the cards.  Colours are only for orientation.
    // ------------------------------------------------------------------
    $baseurl = new moodle_url('/admin/settings.php');

    $cardshtml = html_writer::start_div('d-flex flex-wrap gap-4');

    // Card helper -------------------------------------------------------
    $makecard = function(string $id, string $title, string $desc, string $bg) use ($baseurl) {
        $url  = $baseurl->out(false, ['section' => $id]);
        $card = html_writer::start_div('card shadow-sm', ['style' => "width:18rem"]);
        $card .= html_writer::div(
            html_writer::div(format_string($title), 'h5 card-title') .
            html_writer::tag('p', format_text($desc, FORMAT_HTML), ['class' => 'card-text']) .
            html_writer::link($url, format_string($title), ['class' => "btn btn-primary w-100"]),
            "card-body text-center",
            ['style' => "background:$bg;color:#fff;border-radius:.5rem"]
        );
        $card .= html_writer::end_div();
        return $card;
    };

    $cardshtml .= $makecard(
        'mod_bookit_calendar',
        get_string('calendar',  'mod_bookit'),
        get_string('calendar_desc', 'mod_bookit'),
        '#0d6efd'
    );
    $cardshtml .= $makecard(
        'mod_bookit_resources',
        get_string('resources', 'mod_bookit'),
        get_string('resources_desc', 'mod_bookit'),
        '#198754'
    );
    $cardshtml .= $makecard(
        'mod_bookit_checklist',
        get_string('checklist', 'mod_bookit'),
        get_string('checklist_desc', 'mod_bookit'),
        '#dc3545'
    );

    $cardshtml .= html_writer::end_div();

    // Push the heading with HTML into the overview page -----------------
    $overview->add(new admin_setting_heading('mod_bookit_cards', '', $cardshtml));

    // Finally add the overview page to the category ---------------------
    $ADMIN->add('mod_bookit', $overview);

    /**********************************************************************
     * 2.  CALENDAR   ──  all event / calendar-behaviour settings
     *********************************************************************/
    $calendar = new admin_settingpage('mod_bookit_calendar', get_string('calendar', 'mod_bookit'));

    // Event extra time --------------------------------------------------
    $name        = 'mod_bookit/extratime';
    $title       = get_string('settings_extratime', 'mod_bookit');
    $description = get_string('settings_extratime_desc', 'mod_bookit');
    $calendar->add(new admin_setting_configtext($name, $title, $description, 30, PARAM_INT, 5));

    // Min / max selectable year ----------------------------------------
    $thisyear = (int)date('Y');

    $yearlistmin = array_combine(
        range($thisyear, $thisyear - 10),
        range($thisyear, $thisyear - 10)
    );
    $yearlistmax = array_combine(
        range($thisyear, $thisyear + 10),
        range($thisyear, $thisyear + 10)
    );

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

    // Weekday visibility -----------------------------------------------
    $weekdaychoices = [
        1 => get_string('monday',    'calendar'),
        2 => get_string('tuesday',   'calendar'),
        3 => get_string('wednesday', 'calendar'),
        4 => get_string('thursday',  'calendar'),
        5 => get_string('friday',    'calendar'),
        6 => get_string('saturday',  'calendar'),
        0 => get_string('sunday',    'calendar'),
    ];
    $calendar->add(new admin_setting_configmulticheckbox(
        'mod_bookit/weekdaysvisible',
        get_string('config_weekdaysvisible',       'mod_bookit'),
        get_string('config_weekdaysvisible_desc',  'mod_bookit'),
        [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5],          // default Mon-Fri
        $weekdaychoices
    ));

    // Add calendar page to category ------------------------------------
    $ADMIN->add('mod_bookit', $calendar);

    /**********************************************************************
     * 3.  RESOURCES  ──  colours & room related settings
     *********************************************************************/
    $resources = new admin_settingpage('mod_bookit_resources', get_string('resources', 'mod_bookit'));

    // Text colour (white / black) --------------------------------------
    $resources->add(new admin_setting_configselect(
        'mod_bookit/textcolor',
        get_string('settings_textcolor',      'mod_bookit'),
        get_string('settings_textcolor_desc', 'mod_bookit'),
        '#ffffff',
        ['#ffffff' => 'white', '#000000' => 'black']
    ));

    // Room colour heading ----------------------------------------------
    $resources->add(new admin_setting_heading(
        'mod_bookit/roomcolorheading',
        get_string('settings_roomcolorheading', 'mod_bookit', null, true),
        ''
    ));

    // One colour-picker per room (all existing logic kept) --------------
    $catresourceslist = resource_manager::get_resources();
    foreach ($catresourceslist['Rooms']['resources'] ?? [] as $rid => $catresource) {
        $resources->add(new admin_setting_configcolourpicker(
            'mod_bookit/roomcolor_' . $rid,
            get_string('settings_roomcolor',      'mod_bookit', $catresource['name'], true),
            get_string('settings_roomcolor_desc', 'mod_bookit', null, true),
            ''
        ));

        // WCAG helper (unchanged) ---------------------------------------
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

    // Add resources page to category -----------------------------------
    $ADMIN->add('mod_bookit', $resources);

    /**********************************************************************
     * 4.  CHECKLIST  ──  placeholder (optional add-on)
     *********************************************************************/
    $checklist = new admin_settingpage('mod_bookit_checklist', get_string('checklist', 'mod_bookit'));

    $checklist->add(new admin_setting_heading(
        'mod_bookit_checklist_info',
        '',
        get_string('checklist_placeholder', 'mod_bookit')
    ));

    $ADMIN->add('mod_bookit', $checklist);
}
