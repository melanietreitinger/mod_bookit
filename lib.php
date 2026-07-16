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
 * Library file of mod_bookit.
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
    unset($mform);

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
 * Add overview page to module menu.
 *
 * @param settings_navigation $settingsnav
 * @param navigation_node|null $modnode
 */
function bookit_extend_settings_navigation(
    settings_navigation $settingsnav,
    ?navigation_node $modnode = null
) {
    global $PAGE;
    unset($settingsnav);

    if (!$modnode) {
        return; // Safety: we are not inside an activity page.
    }

    $context = $PAGE->cm->context;
    $pageisoverview = $PAGE->url->compare(new moodle_url('/mod/bookit/overview.php'), URL_MATCH_BASE);
    $tab = optional_param('tab', 'myevents', PARAM_ALPHA);
    $canmanagerequests = \mod_bookit\local\manager\event_access_manager::can_manage_open_requests($context);
    $canviewrequestworkspace = \mod_bookit\local\manager\event_access_manager::can_view_request_workspace($context);
    $workspacetabs = ['allrequests', 'openrequests', 'confirmedrequests', 'rejectedcancelled', 'history'];
    $isinrequestworkspace = $pageisoverview && $canviewrequestworkspace && (
        in_array($tab, $workspacetabs, true)
        || in_array($tab, ['rejectedrequests', 'myevents'], true)
    );

    if (has_capability('mod/bookit:viewownoverview', $context) && !$canviewrequestworkspace) {
        $url = new moodle_url('/mod/bookit/overview.php', ['id' => $PAGE->cm->id, 'tab' => 'myevents']);
        $overviewlabel = get_string('overview', 'bookit');

        $overviewnode = $modnode->add(
            $overviewlabel,
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'bookitoverview',
            new pix_icon('i/calendar', '')
        );

        if ($pageisoverview && !$isinrequestworkspace) {
            $PAGE->set_secondary_active_tab('bookitoverview');
            $overviewnode->make_active();
        }
    }

    if ($canviewrequestworkspace) {
        $newcount = \mod_bookit\local\manager\event_manager::count_new_requests();
        $inprogresscount = \mod_bookit\local\manager\event_manager::count_in_progress_requests();
        $badgeshtml = html_writer::tag(
            'span',
            (string)$newcount,
            [
                'class' => 'bookit-nav-badge bookit-nav-badge--new',
                'aria-label' => get_string('overview_nav_badge_new', 'mod_bookit', $newcount),
            ]
        ) . html_writer::tag(
            'span',
            (string)$inprogresscount,
            [
                'class' => 'bookit-nav-badge bookit-nav-badge--inprogress',
                'aria-label' => get_string('overview_nav_badge_inprogress', 'mod_bookit', $inprogresscount),
            ]
        );
        $label = get_string('overview_request_workspace', 'mod_bookit') . ' ' . $badgeshtml;
        $url = new moodle_url('/mod/bookit/overview.php', ['id' => $PAGE->cm->id, 'tab' => 'allrequests']);
        $requestworkspacenode = $modnode->add(
            $label,
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'bookitrequestworkspace',
            new pix_icon('i/report', '')
        );

        if ($isinrequestworkspace) {
            $PAGE->set_secondary_active_tab('bookitrequestworkspace');
            $requestworkspacenode->make_active();
        }
    }
}

/**
 * Return array of allowed weekday numbers (0 = Sunday … 6 = Saturday).
 * @return array|int[]
 * @throws dml_exception
 */
function bookit_allowed_weekdays(): array {
    $raw = get_config('mod_bookit', 'weekdaysvisible');
    if ($raw === false || $raw === '') {
        // Default: Monday-Friday.
        return [1, 2, 3, 4, 5];
    }
    return array_map('intval', array_filter(explode(',', $raw), 'strlen'));
}
