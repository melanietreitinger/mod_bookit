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
 * BookIt settings overview for non-admin users.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();

// Check if user has the required capability.
require_capability('mod/bookit:managemasterchecklist', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/bookit/settings_overview_nonadmin.php'));
$PAGE->set_title(get_string('pluginname', 'mod_bookit'));
$PAGE->set_heading(get_string('pluginname', 'mod_bookit'));

/* ----------------------------------------------------------------------
   Inline CSS to mimic Boost Union overview cards (neutral, compact)
   ---------------------------------------------------------------------- */
$css = '
.bookit-settings-overview{
  display:flex; flex-wrap:wrap; align-items:stretch;
  gap:.5rem !important; column-gap:.5rem !important; row-gap:.5rem !important;
}
.bookit-card{
  background:#f8f9fa;
  border:1px solid #e5e7eb;
  border-radius:.5rem;
  box-shadow:0 .125rem .25rem rgba(0,0,0,.04);
  width:20rem;
  margin:0;
  display:flex; flex-direction:column; justify-content:space-between;
}
.bookit-card-body{
  padding:1rem 1rem .75rem 1rem;
  text-align:center;
}
.bookit-card-body .card-title{
  margin-bottom:.5rem; font-weight:600; font-size:1.05rem;
}
.bookit-card-body .card-text{
  margin:0; color:#495057;
}
.bookit-card-footer{
  margin-top:auto; padding:.75rem 1rem 1rem 1rem;
}
';

/* ----------------------------------------------------------------------
   Card factory (neutral style; button at the bottom)
   ---------------------------------------------------------------------- */
$makecard = function (string $url, string $title, string $desc) {
    $card  = html_writer::start_div('bookit-card');
    $card .= html_writer::div(
        html_writer::div(format_string($title), 'card-title') .
        html_writer::tag('p', format_text($desc, FORMAT_HTML), ['class' => 'card-text']),
        'bookit-card-body'
    );
    $card .= html_writer::div(
        html_writer::link($url, format_string($title), ['class' => 'btn btn-primary w-100']),
        'bookit-card-footer'
    );
    $card .= html_writer::end_div();

    return $card;
};

echo $OUTPUT->header();

echo html_writer::tag('style', $css);

echo html_writer::tag('h2', get_string('settings_overview', 'mod_bookit'), ['class' => 'mb-3']);

echo html_writer::start_div('bookit-settings-overview');

// Calendar settings card - links to admin settings page.
$baseurl = new moodle_url('/admin/settings.php');
echo $makecard(
    $baseurl->out(false, ['section' => 'mod_bookit_calendar']),
    get_string('calendar', 'mod_bookit'),
    get_string('calendar_desc', 'mod_bookit')
);

// Resources settings card - links to admin settings page.
echo $makecard(
    $baseurl->out(false, ['section' => 'mod_bookit_resources']),
    get_string('resources', 'mod_bookit'),
    get_string('resources_desc', 'mod_bookit')
);

// Checklist settings card - links to admin settings page.
echo $makecard(
    $baseurl->out(false, ['section' => 'mod_bookit_checklist']),
    get_string('checklist', 'mod_bookit'),
    get_string('checklist_desc', 'mod_bookit')
);

// Master Checklist card - direct link to master checklist page.
echo $makecard(
    (new moodle_url('/mod/bookit/master_checklist.php'))->out(false),
    get_string('master_checklist', 'mod_bookit'),
    get_string('master_checklist', 'mod_bookit')
);

echo html_writer::end_div();

echo $OUTPUT->footer();
