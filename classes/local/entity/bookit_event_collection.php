<?php
namespace mod_bookit\local\entity;

defined('MOODLE_INTERNAL') || die();

class bookit_event_collection {

    /**
     * Returns distinct departments (faculties) from bookit_event.
     * @return array
     */
    public static function get_faculties(): array {
        global $DB;
        return $DB->get_fieldset_sql("
            SELECT DISTINCT department
              FROM {bookit_event}
             WHERE department <> ''
          ORDER BY department
        ");
    }

    /**
     * Returns room list from resource manager.
     * @return array [id => name]
     */
    public static function get_rooms(): array {
        $rooms = [];
        $resources = \mod_bookit\local\manager\resource_manager::get_resources();
        foreach ($resources['Rooms']['resources'] ?? [] as $rid => $r) {
            $rooms[$rid] = $r['name'];
        }
        return $rooms;
    }
}
