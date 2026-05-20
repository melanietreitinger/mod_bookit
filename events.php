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
 * Adapter-mode calendar feed that delegates to the governed read-model implementation.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\manager\event_access_manager;
use mod_bookit\local\manager\event_manager;

require_login();

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

header('Content-Type: application/json; charset=utf-8');

if (!has_capability('mod/bookit:view', $context)) {
    echo json_encode([]);
    exit;
}

$statusraw = optional_param('status', '', PARAM_RAW_TRIMMED);
if ($statusraw === '') {
    $statusraw = optional_param('bookingstatus', '', PARAM_RAW_TRIMMED);
}

$filters = event_access_manager::normalise_governed_read_filters([
    'start' => optional_param('start', '1970-01-01T00:00', PARAM_TEXT),
    'end' => optional_param('end', '2100-01-01T00:00', PARAM_TEXT),
    'roomids' => optional_param('room', '', PARAM_TEXT),
    'facultyids' => optional_param('faculty', '', PARAM_TEXT),
    'bookingstatuses' => has_capability('mod/bookit:filterstatus', $context) ? $statusraw : [],
    'search' => optional_param('search', '', PARAM_TEXT),
    'exportmode' => !empty(optional_param('export', 0, PARAM_INT)),
]);

$events = event_manager::get_governed_calendar_events(
    $context,
    (int)$USER->id,
    $filters['start'] ?? '1970-01-01 00:00',
    $filters['end'] ?? '2100-01-01 00:00',
    $filters
);

echo json_encode(array_values($events), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
