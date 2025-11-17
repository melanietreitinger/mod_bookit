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
 * Export endpoint for checklist data.
 *
 * @package     mod_bookit
 * @copyright   2025 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/bookit/lib.php');

use mod_bookit\local\manager\sharing_manager;

require_login();

$context = context_system::instance();
require_capability('mod/bookit:managemasterchecklist', $context);


$masterid = required_param('masterid', PARAM_INT);
$format = required_param('format', PARAM_ALPHA);
$title = optional_param('title', '', PARAM_TEXT);

if (!in_array($format, ['csv', 'pdf'])) {
    throw new moodle_exception('invalidformat', 'mod_bookit');
}

try {
    switch ($format) {
        case 'csv':
            sharing_manager::export_master_checklist_csv($masterid);
            break;
        case 'pdf':
            sharing_manager::export_master_checklist_pdf($masterid, '', $title);
            break;
    }
} catch (Exception $e) {
    throw new moodle_exception('exportfailed', 'mod_bookit', '', $e->getMessage());
}
