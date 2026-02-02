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
 * Form for creating and editing an event.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * WORK IN PROGRESS by vadym (user story event filters)
 * Event feed for the BookIt calendar -- now with optional filters
 *
 * When no filter parameter is passed the behaviour is identical to the
 * original file: all* events in the requested time-range are returned.
 *
 * Optional GET parameters (all of them can be omitted):
 * room     (int)    → resource id of the room
 * faculty  (string) →institutionID (exact match)
 * status   (int)    → bookingstatus 0-4
 * search   (string) → free-text search in event name OR faculty
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\manager\event_manager;

require_login();           // User must be logged-in.
// ...@TODO: capability check, check for sesskey!
// ...@TODO: The id of the instance should become required in future!


$id     = required_param('id', PARAM_INT);      // Course-module id (required).
$start  = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end    = optional_param('end', '2100-01-01T00:00', PARAM_TEXT);
$export = optional_param('export', 0, PARAM_INT);

$cm      = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$canfilterstatus = has_capability('mod/bookit:filterstatus', $context);

// Validate and convert start and end times.
try {
    $start = new DateTime($start);
    $start = $start->format('Y-m-d H:i');
    $end = new DateTime($end);
    $end = $end->format('Y-m-d H:i');
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
    exit(1);
}

// New optional filter parameters (for filter user story).
$roomid  = optional_param('room', 0, PARAM_INT);
$faculty = optional_param('faculty', '', PARAM_TEXT);
$search  = optional_param('search', '', PARAM_TEXT);

// Status: accept ?status= or ?bookingstatus=. Empty → -1 (no filter).
$statusraw = optional_param('status', null, PARAM_RAW_TRIMMED);
if ($statusraw === null) {
    $statusraw = optional_param('bookingstatus', null, PARAM_RAW_TRIMMED);
}
$status = ($statusraw === null || $statusraw === '') ? -1 : clean_param($statusraw, PARAM_INT);

if (!$canfilterstatus) {
    $status = -1; // Ignore status filter if not allowed.
}


// Fetch events using the helper.
$events = event_manager::get_events_in_timerange($start, $end, $id);

// If this is an export request and the user is NOT service team,
// remove reserved events completely from the response.
if ($export && !has_capability('mod/bookit:viewalldetailsofevent', $context)) {
    $events = array_filter($events, static function ($ev) {
        // Works for both array and object events.
        $extended = null;
        if (is_array($ev) && isset($ev['extendedProps'])) {
            $extended = $ev['extendedProps'];
        } else if (is_object($ev) && isset($ev->extendedProps)) {
            $extended = $ev->extendedProps;
        }

        $reserved = false;
        if (is_object($extended) && property_exists($extended, 'reserved')) {
            $reserved = (bool)$extended->reserved;
        }

        // Keep only non-reserved events.
        return !$reserved;
    });
    // Reindex after filtering.
    $events = array_values($events);
}

// Access helpers that work for arrays and objects.
$aget = static function ($src, array $keys) {
    foreach ($keys as $k) {
        if (is_array($src) && array_key_exists($k, $src) && $src[$k] !== '' && $src[$k] !== null) {
            return $src[$k];
        }
        if (is_object($src) && isset($src->$k) && $src->$k !== '' && $src->$k !== null) {
            return $src->$k;
        }
    }
    return null;
};
$aset = static function (&$dst, $key, $val) {
    if (is_array($dst)) {
        $dst[$key] = $val;
    } else {
        $dst->$key = $val;
    }
};

// ...TODO outsource?
global $DB;

foreach ($events as &$ev) {
    // Works for array or object.
    $evid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
    if (!$evid) {
        continue;
    }

    // Fetch a single enrichment row.
    $row = $DB->get_record_sql("
        SELECT e.bookingstatus,
               e.institutionid,
               r.id   AS roomid,
               r.name AS roomname
          FROM {bookit_event} e
     LEFT JOIN {bookit_event_resources} er ON er.eventid = e.id
     LEFT JOIN {bookit_resource}        r  ON r.id       = er.resourceid
         WHERE e.id = ?
      LIMIT 1", [$evid]);

    // Skip if nothing found.
    if (!$row) {
        continue;
    }

    // Assign values safely for array or object.
    if (is_array($ev)) {
        $ev['bookingstatus'] = (int)($row->bookingstatus ?? 0);
        $ev['institutionid']    = (string)($row->institutionid ?? '');
        $ev['roomid']        = (int)($row->roomid ?? 0);
        $ev['roomname']      = (string)($row->roomname ?? '');
    } else {
        $ev->bookingstatus = (int)($row->bookingstatus ?? 0);
        $ev->institutionid    = (string)($row->institutionid ?? '');
        $ev->roomid        = (int)($row->roomid ?? 0);
        $ev->roomname      = (string)($row->roomname ?? '');
    }
}
unset($ev);




// Apply in-memory filters (only if parameter present). For Filter user story.
$events = array_filter($events, function ($ev) use ($roomid, $faculty, $status, $search) {
    // Helper to read from array or object.
    $get = function ($src, array $keys) {
        foreach ($keys as $k) {
            if (is_array($src) && array_key_exists($k, $src) && $src[$k] !== '' && $src[$k] !== null) {
                return $src[$k];
            }
            if (is_object($src) && isset($src->$k) && $src->$k !== '' && $src->$k !== null) {
                return $src->$k;
            }
        }
        return null;
    };
    // ROOM filter (by resource id; supports multiple roomids).
    if ($roomid) {
        $eventroomids = $get($ev, ['roomids']);
        $eventroomid  = $get($ev, ['roomid', 'resourceid', 'rid']);

        $hasmatch = false;
        if (is_array($eventroomids) && !empty($eventroomids)) {
            $hasmatch = in_array((int)$roomid, array_map('intval', $eventroomids), true);
        } else if ($eventroomid !== null && $eventroomid !== '') {
            $hasmatch = ((int)$eventroomid === (int)$roomid);
        }

        if (!$hasmatch) {
            return false;
        }
    }

    // FACULTY filter (trim + case-insensitive exact match).
    if ($faculty !== '') {
        $evdept = (string)($get($ev, ['institutionid', 'faculty', 'dept']) ?? '');
        $evdeptnorm = mb_strtolower(trim($evdept));
        $wantnorm   = mb_strtolower(trim((string)$faculty));

        if ($evdeptnorm === '' || $evdeptnorm !== $wantnorm) {
            return false;
        }
    }

    // STATUS filter (strict equality).
    if ($status > -1) {
        $evstatus = $get($ev, ['bookingstatus']);
        if ($evstatus === null || (int)$evstatus !== (int)$status) {
            return false;
        }
    }

    // SEARCH filter (substring in title + institutionid, case-insensitive).
    if ($search !== '') {
        $needle = mb_strtolower($search);
        $title = (string) ($get($ev, ['title', 'name', 'summary']) ?? '');
        $dept = (string) ($get($ev, ['institutionid', 'faculty', 'dept']) ?? '');
        $haystack = mb_strtolower($title . ' ' . $dept);
        if (mb_strpos($haystack, $needle) === false) {
            return false;
        }
    }
    return true;
});

// Normalize times to ISO 8601 so week/day views render them.
$events = array_values(array_map(function ($e) {
    if (isset($e->start)) {
        $e->start = str_replace(' ', 'T', $e->start) . ':00';
    }
    if (isset($e->end)) {
        $e->end = str_replace(' ', 'T', $e->end) . ':00';
    }
    return $e;
}, $events));

// Output JSON. Debug block – visible only if ?debug=1 is passed.
header('Content-Type: application/json; charset=utf-8');

$debug = optional_param('debug', 0, PARAM_INT);

if ($debug) {
    // Debug info is added, but still output plain events array for compatibility.
    $debuginfo = [
        'input' => [
            'roomid'  => $roomid,
            'faculty' => $faculty,
            'status'  => $status,
            'search'  => $search,
            'start'   => $start,
            'end'     => $end,
        ],
        'event_count' => count($events),
    ];
    // Send headers for developer (browser console).
    header('X-BookIt-Debug: ' . json_encode($debuginfo));
}

// Always return only the event array itself.
echo json_encode(array_values($events), JSON_UNESCAPED_UNICODE);
exit;
