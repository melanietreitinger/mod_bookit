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
 * Adapter-mode room availability feed that delegates to the governed read-model implementation.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use mod_bookit\local\manager\event_manager;

require_login();

$context = context_system::instance();
header('Content-Type: application/json; charset=utf-8');

if (!has_capability('mod/bookit:managebasics', $context)) {
    echo json_encode([]);
    exit;
}

$roomid = required_param('roomid', PARAM_INT);
$start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);

$starttimestamp = (new DateTime($start))->getTimestamp();
$endtimestamp = (new DateTime($end))->getTimestamp();

echo json_encode(
    event_manager::get_governed_room_availability($starttimestamp, $endtimestamp, $roomid),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
exit;
