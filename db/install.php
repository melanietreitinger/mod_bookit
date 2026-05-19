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
 * Plugin installation callbacks.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Seed the required calendar-only baseline for fresh installs.
 *
 * @return void
 */
function xmldb_bookit_install(): void {
    $report = \mod_bookit\local\install_helper::ensure_fresh_install_baseline();
    \mod_bookit\local\install_helper::ensure_optional_part_defaults(false);
    \mod_bookit\local\install_helper::ensure_booking_status_notification_defaults();

    set_config(
        'installhelperfinished',
        in_array($report['status'], ['success', 'idempotent'], true) ? 1 : 0,
        'mod_bookit'
    );
}
