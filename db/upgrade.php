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

    /*
     * 2024102204 – rename event fields, drop legacy fields, add new fields, rename table
     */
    if ($oldversion < 2024102204) {
        // Table: bookit_event.
        $table = new xmldb_table('bookit_event');

        // Rename fields: status -> bookingstatus.
        $field1 = new xmldb_field(
            'status',
            XMLDB_TYPE_INTEGER,
            '6',
            null,
            XMLDB_NOTNULL,
            null,
            null,
            'compensationfordisadvantages'
        );

        if ($dbman->field_exists($table, $field1)) {
            $dbman->rename_field($table, $field1, 'bookingstatus');
        }

        // Drop fields (if present).
        $field2 = new xmldb_field('personinchargename');
        if ($dbman->field_exists($table, $field2)) {
            $dbman->drop_field($table, $field2);
        }
        $field3 = new xmldb_field('personinchargeemail');
        if ($dbman->field_exists($table, $field3)) {
            $dbman->drop_field($table, $field3);
        }

        // Add new fields (guarded).
        $field4 = new xmldb_field('timecompensation', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'participantsamount');
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }
        $field5 = new xmldb_field('otherexaminers', XMLDB_TYPE_TEXT, null, null, null, null, null, 'personinchargeid');
        if (!$dbman->field_exists($table, $field5)) {
            $dbman->add_field($table, $field5);
        }

        // Rename table bookit_category -> bookit_resource_categories (only if needed).
        $oldtable = new xmldb_table('bookit_category');
        if ($dbman->table_exists($oldtable) && !$dbman->table_exists(new xmldb_table('bookit_resource_categories'))) {
            $dbman->rename_table($oldtable, 'bookit_resource_categories');
        }

        // Bookit savepoint reached.
        upgrade_mod_savepoint(true, 2024102204, 'bookit');
    }

    /*
     * 2025050500 – create checklist tables
     */
    if ($oldversion < 2025050500) {
        // Table: bookit_checklist_master.
        $table = new xmldb_table('bookit_checklist_master');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('checklistcategories', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

            $dbman->create_table($table);
        }

        // Table: bookit_checklist_category.
        $table = new xmldb_table('bookit_checklist_category');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('masterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('checklistitems', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('masterid', XMLDB_KEY_FOREIGN, ['masterid'], 'bookit_checklist_master', ['id']);
            $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

            $table->add_index('masterid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['masterid', 'sortorder']);

            $dbman->create_table($table);
        }

        // Table: bookit_checklist_item.
        $table = new xmldb_table('bookit_checklist_item');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('masterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('roomid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('title', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('itemtype', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('options', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('isrequired', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('defaultvalue', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('duedaysoffset', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('duedaysrelation', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('masterid', XMLDB_KEY_FOREIGN, ['masterid'], 'bookit_checklist_master', ['id']);
            $table->add_key('categoryid', XMLDB_KEY_FOREIGN, ['categoryid'], 'bookit_checklist_category', ['id']);
            $table->add_key('parentid', XMLDB_KEY_FOREIGN, ['parentid'], 'bookit_checklist_item', ['id']);
            $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

            $table->add_index('masterid_sortorder', XMLDB_INDEX_NOTUNIQUE, ['masterid', 'sortorder']);

            $dbman->create_table($table);
        }

        // Table: bookit_notification_slots.
        $table = new xmldb_table('bookit_notification_slots');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('checklistitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('type', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('roleids', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('duedaysoffset', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('duedaysrelation', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('isactive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('messagetext', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('checklistitemid', XMLDB_KEY_FOREIGN, ['checklistitemid'], 'bookit_checklist_item', ['id']);
            $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2025050500, 'bookit');
    }

    /*
     * 2025050600 – add examinerid to bookit_event
     */
    if ($oldversion < 2025050600) {
        $table = new xmldb_table('bookit_event');
        $field = new xmldb_field('examinerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'bookingstatus');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        debugging('Added examinerid field to bookit_event', DEBUG_DEVELOPER);

        upgrade_mod_savepoint(true, 2025050600, 'bookit');
    }

    /*
     * 2025060100 – backfill examinerid from personinchargeid
     */
    if ($oldversion < 2025060100) {
        $table = new xmldb_table('bookit_event');
        $field = new xmldb_field('examinerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'bookingstatus');

        // Ensure the column exists even if a site skipped the previous step.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Populate examinerid from legacy personinchargeid where empty.
        $DB->execute("
            UPDATE {bookit_event}
               SET examinerid = personinchargeid
             WHERE examinerid = 0 OR examinerid IS NULL
        ");

        upgrade_mod_savepoint(true, 2025060100, 'bookit');
    }
    return true;
}
