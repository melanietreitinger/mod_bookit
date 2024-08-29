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
 * Library of interface functions and constants.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function bookit_supports(string $feature): ?bool {
    return match ($feature) {
        FEATURE_MOD_INTRO, FEATURE_BACKUP_MOODLE2 => true,
        default => null,
    };
}

/**
 * Saves a new instance of the mod_bookit into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_bookit_mod_form|null $mform The form.
 * @return int The id of the newly inserted record.
 * @throws dml_exception
 */
function bookit_add_instance(object $moduleinstance, mod_bookit_mod_form $mform = null): int {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('bookit', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_bookit in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @return bool True if successful, false otherwise.
 * @throws dml_exception
 */
function bookit_update_instance(object $moduleinstance): bool {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('bookit', $moduleinstance);
}

/**
 * Removes an instance of the mod_bookit from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 * @throws dml_exception
 */
function bookit_delete_instance(int $id): bool {
    global $DB;

    $exists = $DB->get_record('bookit', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $DB->delete_records('bookit', ['id' => $id]);

    return true;
}
