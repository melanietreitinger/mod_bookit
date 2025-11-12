<?php
namespace mod_bookit\local\entity;

defined('MOODLE_INTERNAL') || die();

/**
 * Repository for fetching events for examiners.
 *
 * @package   mod_bookit
 */
class examiner_event_repository {

    /**
     * Returns all events created or owned by the examiner (personinchargeid = $userid).
     *
     * @param int $userid
     * @return array
     */
    public static function get_events_for_examiner(int $userid): array {
        global $DB;

        $sql = "SELECT e.id,
                       e.name,
                       e.bookingstatus,
                       e.starttime,
                       r.name AS room
                  FROM {bookit_event} e
             LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
             LEFT JOIN {bookit_resource}        r  ON r.id       = er.resourceid
                 WHERE e.personinchargeid = ?
              GROUP BY e.id";

        return $DB->get_records_sql($sql, [$userid]);
    }
}
