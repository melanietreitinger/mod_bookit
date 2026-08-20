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

        // Update new filed amounts of participants.
    $newversion = 2026080501;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('bookit_event_history');

        if (!$dbman->table_exists($table)) {
            $table->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE
            );
            $table->add_field(
                'eventid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL
            );
            $table->add_field(
                'action',
                XMLDB_TYPE_CHAR,
                '50',
                null,
                XMLDB_NOTNULL
            );
            $table->add_field(
                'oldstatus',
                XMLDB_TYPE_INTEGER,
                '6'
            );
            $table->add_field(
                'newstatus',
                XMLDB_TYPE_INTEGER,
                '6'
            );
            $table->add_field(
                'changedfields',
                XMLDB_TYPE_TEXT
            );
            $table->add_field(
                'recoverymarker',
                XMLDB_TYPE_INTEGER,
                '1',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );
            $table->add_field(
                'usermodified',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );
            $table->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );
            $table->add_key(
                'eventid_fk',
                XMLDB_KEY_FOREIGN,
                ['eventid'],
                'bookit_event',
                ['id']
            );
            $table->add_key(
                'usermodified_fk',
                XMLDB_KEY_FOREIGN,
                ['usermodified'],
                'user',
                ['id']
            );

            $table->add_index(
                'event_time_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['eventid', 'timecreated']
            );

            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, $newversion, 'bookit');
    }

    if ($oldversion < $newversion) {
        $table = new xmldb_table('bookit_event');

        $field = new xmldb_field(
            'totalparticipantsamount',
            XMLDB_TYPE_INTEGER,
            '11',
            null,
            null,
            null,
            null,
            'participantsamount'
        );

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, $newversion, 'bookit');
    }
    return true;
}
