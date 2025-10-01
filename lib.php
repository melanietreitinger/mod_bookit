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
 * @return bool|string|null True if the feature is supported, null otherwise.
 */
function bookit_supports(string $feature): bool|string|null {
    return match ($feature) {
        FEATURE_MOD_INTRO, FEATURE_BACKUP_MOODLE2 => true,
        // Note: do not define FEATURE_MOD_PURPOSE or icon background will be colored,
        // @see https://moodledev.io/docs/4.1/devupdate#activity-icons.
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
function bookit_add_instance(object $moduleinstance, mod_bookit_mod_form|null $mform = null): int {
    global $DB;

    $moduleinstance->timecreated = time();

    return $DB->insert_record('bookit', $moduleinstance);
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

/**
 * Calculates the luminosity of an given RGB color.
 * The color code must be in the format of RRGGBB.
 * The luminosity equations are from the WCAG 2 requirements.
 * https://www.w3.org/TR/WCAG22/#dfn-relative-luminance
 *
 * @copyright gdkraus https://github.com/gdkraus/wcag2-color-contrast
 *
 * @param string $color
 * @return float
 */
function calculateluminosity(string $color): float {

    $r = hexdec(substr($color, 0, 2)) / 255; // Red value.
    $g = hexdec(substr($color, 2, 2)) / 255; // Green value.
    $b = hexdec(substr($color, 4, 2)) / 255; // Blue value.
    if ($r <= 0.03928) {
        $r = $r / 12.92;
    } else {
        $r = pow((($r + 0.055) / 1.055), 2.4);
    }

    if ($g <= 0.03928) {
        $g = $g / 12.92;
    } else {
        $g = pow((($g + 0.055) / 1.055), 2.4);
    }

    if ($b <= 0.03928) {
        $b = $b / 12.92;
    } else {
        $b = pow((($b + 0.055) / 1.055), 2.4);
    }

    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Calculates the luminosity ratio of two colors.
 * The luminosity ratio equations are from the WCAG 2 requirements.
 * https://www.w3.org/TR/WCAG22/#dfn-contrast-ratio
 *
 * @copyright gdkraus https://github.com/gdkraus/wcag2-color-contrast
 *
 * @param string $color1
 * @param string $color2
 * @return float
 */
function calculateluminosityratio(string $color1, string $color2): float {
    $l1 = calculateluminosity($color1);
    $l2 = calculateluminosity($color2);

    if ($l1 > $l2) {
        $ratio = (($l1 + 0.05) / ($l2 + 0.05));
    } else {
        $ratio = (($l2 + 0.05) / ($l1 + 0.05));
    }
    return $ratio;
}

/**
 * Returns an array with the results of the color contrast analysis
 * It returns akey for each level (AA and AAA, both for normal and large or bold text).
 * It also returns the calculated contrast ratio.
 * The ratio levels are from the WCAG 2 requirements.
 * https://www.w3.org/WAI/WCAG22/quickref/#contrast-minimum
 * https://www.w3.org/WAI/WCAG22/quickref/#contrast-enhanced
 *
 * @copyright gdkraus https://github.com/gdkraus/wcag2-color-contrast
 *
 * @param string $color1
 * @param string $color2
 * @return array
 */
function evaluatecolorcontrast(string $color1, string $color2): array {
    $ratio = calculateluminosityratio($color1, $color2);

    $colorevaluation["AA normal"] = ($ratio >= 4.5 ? 'pass' : 'fail');
    $colorevaluation["AA large"] = ($ratio >= 3 ? 'pass' : 'fail');
    $colorevaluation["AA medium bold"] = ($ratio >= 3 ? 'pass' : 'fail');
    $colorevaluation["AAA normal"] = ($ratio >= 7 ? 'pass' : 'fail');
    $colorevaluation["AAA large"] = ($ratio >= 4.5 ? 'pass' : 'fail');
    $colorevaluation["AAA medium bold"] = ($ratio >= 4.5 ? 'pass' : 'fail');
    $colorevaluation["Ratio"] = round($ratio, 2);

    return $colorevaluation;
}

/**
 * Print color evaluation for normal text.
 * @param string $color
 * @param string $color2
 * @return string
 */
function printcolorevaluation(string $color, string $color2): string {
    $checkstring = '';
    $check = evaluatecolorcontrast($color, $color2);
    foreach ($check as $key => $value) {
        if (str_contains($key, 'normal') || 'Ratio' == $key) {
            if ('pass' == $value) {
                $value = '<i class="fa fa-circle-check text-success" aria-hidden="true"></i> ';
            } else if ('fail' == $value) {
                $value = '<i class="fa fa-circle-xmark text-danger" aria-hidden="true"></i> ';
            }

            $checkstring .= $key . ': <strong>' . $value . '</strong> ';
        }
    }
    return $checkstring . ' <br>' . '<i class="fa fa-circle-info text-info"></i>
            WCAG 2.2 <a href="https://www.w3.org/TR/WCAG22/#contrast-minimum">AA</a> /
            <a href="https://www.w3.org/TR/WCAG22/#contrast-enhanced">AAA</a>.<br><br>';
}
/*
This function adds settings navigation. 
(has to be nullable, sorry!)
*/
function bookit_extend_settings_navigation(settings_navigation $settingsnav,
                                           navigation_node $modnode = null) {
    global $PAGE;

    if (!$modnode) {
        return;                 // Safety: we are not inside an activity page.
    }

    $context = $PAGE->cm->context;
    if (has_capability('mod/bookit:viewownoverview', $context)) {
        $url = new moodle_url('/mod/bookit/overview.php', ['id' => $PAGE->cm->id]);

        // THIS is the line that puts the entry under the current Bookit node.
        $modnode->add(get_string('overview', 'bookit'), $url,
                      navigation_node::TYPE_SETTING, null, 'bookitoverview',
                      new pix_icon('i/calendar', ''));
    }
}


/**
 * Return array of allowed weekday numbers (0 = Sunday … 6 = Saturday).
 *
 * @return int[]
 */
function bookit_allowed_weekdays(): array {
    $raw = get_config('mod_bookit', 'weekdaysvisible');
    if ($raw === false || $raw === '') {
        // Default: Monday-Friday.
        return [1, 2, 3, 4, 5];
    }
    return array_map('intval', array_filter(explode(',', $raw), 'strlen'));
}

/**
 * Return array of allowed weekday numbers (0=Sun … 6=Sat).
 *
 * @return int[]
 */
function bookit_get_allowed_weekdays(): array {
    return bookit_allowed_weekdays();
}

