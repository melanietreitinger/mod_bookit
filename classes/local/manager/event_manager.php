<?php

namespace mod_bookit\local\manager;

/**
 * Manager for accessing and fetching events.
 *
 * @package     mod_bookit
 * @copyright   2024 Justus Dieckmann, Universität Münster
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_manager
{

  public static function get_events_in_timerange($starttime, $endtime, $instanceid): array
  {
    global $DB;
    // TODO use instance id.
    $starttimestamp = \DateTime::createFromFormat('Y-m-d H:i', $starttime)->getTimestamp();
    $endtimestamp = \DateTime::createFromFormat('Y-m-d H:i', $endtime)->getTimestamp();
    $records = $DB->get_records_sql(
      'SELECT name, starttime, endtime FROM {bookit_event} ' .
        'WHERE endtime >= :starttime AND starttime <= :endtime',
      ['starttime' => $starttimestamp, 'endtime' => $endtimestamp]
    );
    $events = [];
    foreach ($records as $record) {
      $events[] = [
        'title' => $record->name,
        'start' => date('Y-m-d H:i', $record->starttime),
        'end' => date('Y-m-d H:i', $record->endtime),
      ];
    }
    return $events;
  }
}

