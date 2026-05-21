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
 * Upgrade script.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script.
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 */
function xmldb_bookit_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    // Set this to the SAME value you set in mod/bookit/version.php ($plugin->version).
    $newversion = 2025411305;

    if ($oldversion < $newversion) {
        $table = new xmldb_table('bookit_event');

        $old = new xmldb_field('department', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'semester');

        $new = new xmldb_field('institutionid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'semester');

        if ($dbman->field_exists($table, $old) && !$dbman->field_exists($table, $new)) {
            $dbman->rename_field($table, $old, 'institutionid');
        }
        upgrade_mod_savepoint(true, $newversion, 'bookit');
    }
    return true;
}
