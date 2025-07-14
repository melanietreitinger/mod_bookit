<?php
// mod/bookit/events.php
//
// Event feed for the BookIT calendar -- now with optional filters
//
// When no filter parameter is passed the behaviour is identical to the
// original file: all* events in the requested time-range are returned.
//
// Optional GET parameters (all of them can be omitted):
//   room     (int)    → resource id of the room
//   faculty  (string) → department / faculty  (exact match)
//   status   (int)    → bookingstatus 0-4
//   search   (string) → free-text search in event name OR faculty
//
// ---------------------------------------------------------------------

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\manager\event_manager;

require_login();           // user must be logged-in

/* ------------------------------------------------------------------ */
/* 1. Mandatory / original parameters                                 */
/* ------------------------------------------------------------------ */
$id     = optional_param('id',    0,          PARAM_INT);   // course-module id
$start  = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end    = optional_param('end',   '2100-01-01T00:00', PARAM_TEXT);

/* ------------------------------------------------------------------ */
/* 2. NEW optional filter parameters                                  */
/* ------------------------------------------------------------------ */
$roomid  = optional_param('room',    0,          PARAM_INT);
$faculty = optional_param('faculty', '',         PARAM_TEXT);
$status  = optional_param('status',  '',         PARAM_INT);
$search  = optional_param('search',  '',         PARAM_TEXT);

/* ------------------------------------------------------------------ */
/* 3. Validate / convert start- & end-times          */
/* ------------------------------------------------------------------ */
try {
    $start = (new DateTime($start))->format('Y-m-d H:i');
    $end   = (new DateTime($end))  ->format('Y-m-d H:i');
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit(1);
}

/* ------------------------------------------------------------------ */
/* 4. Fetch events using the helper                        */
/* ------------------------------------------------------------------ */
$events = event_manager::get_events_in_timerange($start, $end, $id);

/* ------------------------------------------------------------------ */
/* 5. Apply in-memory filters (only if parameter present)              */
/* ------------------------------------------------------------------ */
$events = array_filter($events, function($ev) use ($roomid, $faculty, $status, $search) {

    // room filter (resource id – note: $ev->roomid comes from event_manager)
    if ($roomid && (!isset($ev->roomid) || (int)$ev->roomid !== $roomid)) {
        return false;
    }

    // faculty / department filter (exact match)
    if ($faculty !== '' && (!isset($ev->department) || $ev->department !== $faculty)) {
        return false;
    }

    // status filter (0 … 4)
    if ($status !== '' && (int)$ev->bookingstatus !== (int)$status) {
        return false;
    }

    // free-text search (case-insensitive) in name OR department
    if ($search !== '') {
        $haystack  = strtolower(($ev->name        ?? '') . ' ' .
                                ($ev->department  ?? ''));
        if (!str_contains($haystack, strtolower($search))) {
            return false;
        }
    }

    return true;   // passes all active filters
});

/* ------------------------------------------------------------------ */
/* 6. Output JSON                       */
/* ------------------------------------------------------------------ */
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array_values($events));