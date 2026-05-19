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

    if ($oldversion < 2025511306) {
        $table = new xmldb_table('bookit_event');

        $old = new xmldb_field('department', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'semester');

        $new = new xmldb_field('institutionid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'semester');

        if ($dbman->field_exists($table, $old) && !$dbman->field_exists($table, $new)) {
            $dbman->rename_field($table, $old, 'institutionid');
        }

        $historytable = new xmldb_table('bookit_event_history');
        if (!$dbman->table_exists($historytable)) {
            $historytable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $historytable->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $historytable->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $historytable->add_field('oldstatus', XMLDB_TYPE_INTEGER, '6', null, null, null, null);
            $historytable->add_field('newstatus', XMLDB_TYPE_INTEGER, '6', null, null, null, null);
            $historytable->add_field('changedfields', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $historytable->add_field('recoverymarker', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $historytable->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $historytable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $historytable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $historytable->add_key('eventid_fk', XMLDB_KEY_FOREIGN, ['eventid'], 'bookit_event', ['id']);
            $historytable->add_key('usermodified_fk', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
            $historytable->add_index('event_time_idx', XMLDB_INDEX_NOTUNIQUE, ['eventid', 'timecreated']);

            $dbman->create_table($historytable);
        }

        upgrade_mod_savepoint(true, 2025511306, 'bookit');
    }

    if ($oldversion < 2025511307) {
        \mod_bookit\local\install_helper::ensure_optional_part_defaults(true);
        \mod_bookit\local\install_helper::ensure_upgrade_baseline_backfill();

        upgrade_mod_savepoint(true, 2025511307, 'bookit');
    }
    return true;
}
