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

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

/**
 * Upgrade script.
 *
 * @param int $oldversion
 * @return true
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 */
function xmldb_bookit_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2024102204) {
        $dbman = $DB->get_manager();

        // Define field id to be added to bookit_event.
        $table = new xmldb_table('bookit_event');

        // Rename fields.
        $field1 =
                new xmldb_field('status', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, null, 'compensationfordisadvantages');
        if ($dbman->field_exists($table, $field1)) {
            $dbman->rename_field($table, $field1, 'bookingstatus');
        }

        // Drop fields.
        $field2 = new xmldb_field('personinchargename');
        if ($dbman->field_exists($table, $field2)) {
            $dbman->drop_field($table, $field2);
        }
        $field3 = new xmldb_field('personinchargeemail');
        if ($dbman->field_exists($table, $field3)) {
            $dbman->drop_field($table, $field3);
        }

        // New fields.
        $field4 = new xmldb_field('timecompensation', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'participantsamount');
        $dbman->add_field($table, $field4);
        $field5 = new xmldb_field('otherexaminers', XMLDB_TYPE_TEXT, null, null, null, null, null, 'personinchargeid');
        $dbman->add_field($table, $field5);

        // Rename table bookit_category to bookit_resource_categories.
        $table = new xmldb_table('bookit_category');
        $dbman->rename_table($table, 'bookit_resource_categories');

        // Bookit savepoint reached.
        upgrade_mod_savepoint(true, 2024102204, 'bookit');
    }
    return true;
}
