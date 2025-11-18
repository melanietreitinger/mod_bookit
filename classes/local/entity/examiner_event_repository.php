<?php
namespace mod_bookit\local\entity;

use mod_bookit\local\manager\resource_manager;

defined('MOODLE_INTERNAL') || die();

class examiner_event_repository {

    public static function get_events_for_examiner(int $userid): array {
        global $DB;

        // -------------------------------
        // 1. Get all room resource IDs
        // -------------------------------
        $resources = resource_manager::get_resources();

        $roomids = [];
        if (!empty($resources['Rooms']['resources'])) {
            $roomids = array_keys($resources['Rooms']['resources']);
        }

        // If no room resources exist → fallback: return events but room = null
        $roomidssql = '';
        if (!empty($roomids)) {
            list($inSql, $paramsRoom) = $DB->get_in_or_equal($roomids, SQL_PARAMS_NAMED, 'roomid');
            $roomidssql = "AND er.resourceid $inSql";
        } else {
            $roomidssql = "AND 1 = 0"; // no room resources exist
            $paramsRoom = [];
        }

        // -------------------------------
        // 2. SQL query
        // -------------------------------
        $sql = "
            SELECT
                e.id,
                e.name,
                e.bookingstatus,
                e.starttime,
                e.personinchargeid,
                e.otherexaminers,
                e.supportpersons,
                e.usermodified,
                r.name AS room

            FROM {bookit_event} e

            LEFT JOIN {bookit_event_resources} er
                   ON er.eventid = e.id
            LEFT JOIN {bookit_resource} r
                   ON r.id = er.resourceid
                  $roomidssql

            WHERE
                   e.personinchargeid = :uid1
                OR e.usermodified    = :uid2
                OR (e.otherexaminers <> '' AND FIND_IN_SET(:uid3, e.otherexaminers) > 0)
                OR (e.supportpersons <> '' AND FIND_IN_SET(:uid4, e.supportpersons) > 0)

            GROUP BY e.id

            ORDER BY e.starttime ASC
        ";

        $params = array_merge([
            'uid1' => $userid,
            'uid2' => $userid,
            'uid3' => $userid,
            'uid4' => $userid
        ], $paramsRoom);

        return $DB->get_records_sql($sql, $params);
    }
}
