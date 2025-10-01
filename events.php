<?php
// This file is part of Moodle - https://moodle.org/.
//
// GNU GPL v3 or later.

/**
 * JSON event feed for the BookIT calendar (with optional filters).
 *
 * GET params:
 *   id       (int)   course-module id   [REQUIRED]
 *   start    (ISO)   e.g. 2025-09-01T00:00
 *   end      (ISO)   e.g. 2025-10-01T00:00
 *   room     (int)   room/resource id
 *   faculty  (text)  department/faculty (exact match)
 *   status   (int)   bookingstatus (0..4)
 *   search   (text)  substring in title OR department (case-insensitive)
 *   debug    (int)   1 to enable server-side logging and debug headers
 *
 * Output items: id, title, start, end, location, room, department, bookingstatus, reserved
 *
 * @package     mod_bookit
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_bookit\local\manager\event_manager;

// -------- Debug helpers -------------------------------------------------.

$debug     = optional_param('debug', 0, PARAM_INT);
$dbgprefix = '[bookit/events.php] ';
$DBG = function(string $msg) use ($debug, $dbgprefix) {
    if ($debug) {
        error_log($dbgprefix . $msg);
    }
};

// -------- Params & access control --------------------------------------.

$id    = required_param('id', PARAM_INT); // Make cmid mandatory.
$start = optional_param('start', '1970-01-01T00:00', PARAM_TEXT);
$end   = optional_param('end',   '2100-01-01T00:00', PARAM_TEXT);

// Filters.
$roomid  = optional_param('room',    0,  PARAM_INT);
$faculty = trim(optional_param('faculty', '', PARAM_TEXT));
$status  = optional_param('status',  -1, PARAM_INT); // -1 = no filter, 0..4 valid.

// Accept either ?status= or ?bookingstatus= ; treat empty string as "no filter".
$statusraw = optional_param('status', null, PARAM_RAW_TRIMMED);
if ($statusraw === null) {
    $statusraw = optional_param('bookingstatus', null, PARAM_RAW_TRIMMED);
}
$status = ($statusraw === null || $statusraw === '') ? -1 : clean_param($statusraw, PARAM_INT);

$search  = trim(optional_param('search',  '', PARAM_TEXT));

$DBG("params id=$id start='$start' end='$end' roomid=$roomid faculty='$faculty' status=$status search='$search'");

$cm     = get_coursemodule_from_id('bookit', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
// If you have a specific view cap, enforce it.
if (get_capability_info('mod/bookit:view')) {
    require_capability('mod/bookit:view', $context);
}

// -------- Date parsing (accept "YYYY-MM-DDTHH:MM" or "YYYY-MM-DD HH:MM") ----.

$fmtIn  = 'Y-m-d H:i';
$fmtOut = 'Y-m-d\TH:i';

try {
    $s = new DateTime(str_replace('T', ' ', $start));
    $e = new DateTime(str_replace('T', ' ', $end));
    $startSql = $s->format($fmtIn);
    $endSql   = $e->format($fmtIn);
    $DBG("timerange SQL '$startSql' → '$endSql'");
} catch (Exception $ex) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid date format for "start" or "end".';
    exit(1);
}

// -------- Fetch events --------------------------------------------------.

$events = event_manager::get_events_in_timerange($startSql, $endSql, $id);

/* ---- Attach rooms from bookit_event_resources → bookit_resource ------- */
if (!empty($events)) {
    // Collect event IDs (supports array or object items).
    $eventids = [];
    foreach ($events as $ev) {
        $eid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
        if ($eid) {
            $eventids[] = (int)$eid;
        }
    }
    $eventids = array_values(array_unique(array_filter($eventids)));

    if ($debug) {
        $DBG('room augment: have ' . count($eventids) . ' ids');
    }

    if ($eventids) {
        list($in, $p) = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED, 'e');
        $sql = "SELECT er.eventid, er.resourceid, r.name
                  FROM {bookit_event_resources} er
                  JOIN {bookit_resource} r ON r.id = er.resourceid
                 WHERE er.eventid $in";
        $rows = $DB->get_records_sql($sql, $p);

        // Build event → {ids:[], names:[]} map.
        $evRooms = [];
        foreach ($rows as $row) {
            $eid = (int)$row->eventid;
            if (!isset($evRooms[$eid])) {
                $evRooms[$eid] = ['ids' => [], 'names' => []];
            }
            $evRooms[$eid]['ids'][]   = (int)$row->resourceid;
            if (!empty($row->name)) {
                $evRooms[$eid]['names'][] = (string)$row->name;
            }
        }

        // Attach to each event (roomids[], roomid, roomname, location).
        foreach ($events as &$ev) {
            $eid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
            if (!$eid || empty($evRooms[(int)$eid])) {
                continue;
            }

            $ids   = $evRooms[(int)$eid]['ids'];
            $names = $evRooms[(int)$eid]['names'];
            $primaryName = $names ? $names[0] : '';

            if (is_array($ev)) {
                $ev['roomids']   = $ids;
                $ev['roomid']    = $ids ? $ids[0] : null;
                $ev['roomname']  = $primaryName;
                if (empty($ev['location'])) {
                    $ev['location'] = $primaryName;
                }
            } else { // Object.
                $ev->roomids   = $ids;
                $ev->roomid    = $ids ? $ids[0] : null;
                $ev->roomname  = $primaryName;
                if (empty($ev->location)) {
                    $ev->location = $primaryName;
                }
            }
        }
        unset($ev);

        if ($debug) {
            $DBG('room augment: mapped ' . count($evRooms) . ' events');
        }
    }
}

/* ---- Attach department + bookingstatus from {bookit_event} ----------- */
/* Reuse $eventids if the rooms block created it; otherwise build it.     */
if (!isset($eventids) || !$eventids) {
    $eventids = [];
    foreach ($events as $ev) {
        $eid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
        if ($eid) {
            $eventids[] = (int)$eid;
        }
    }
    $eventids = array_values(array_unique(array_filter($eventids)));
}

if ($eventids) {
    list($in2, $p2) = $DB->get_in_or_equal($eventids, SQL_PARAMS_NAMED, 'd');
    $sql2 = "SELECT id, TRIM(COALESCE(department, '')) AS department, bookingstatus
               FROM {bookit_event}
              WHERE id $in2";
    $rows2 = $DB->get_records_sql($sql2, $p2);

    foreach ($events as &$ev) {
        $eid = is_array($ev) ? ($ev['id'] ?? null) : ($ev->id ?? null);
        if (!$eid || !isset($rows2[$eid])) {
            continue;
        }
        $dept     = (string)($rows2[$eid]->department ?? '');
        $dbstatus = $rows2[$eid]->bookingstatus;

        if (is_array($ev)) {
            if (empty($ev['department'])) {
                $ev['department'] = $dept;
            }
            if (!array_key_exists('bookingstatus', $ev)) {
                $ev['bookingstatus'] = $dbstatus;
            }
        } else {
            if (empty($ev->department)) {
                $ev->department = $dept;
            }
            if (!isset($ev->bookingstatus)) {
                $ev->bookingstatus = $dbstatus;
            }
        }
    }
    unset($ev);

    if (!empty($debug)) {
        $DBG('dept/status augment: mapped ' . count($rows2) . ' rows');
    }
}

$DBG('fetched events (pre-filter) count=' . count($events));
if ($debug && !empty($events)) {
    $one = reset($events);
    $keys = is_array($one) ? array_keys($one) : array_keys(get_object_vars($one));
    $DBG('first event keys: ' . implode(',', $keys));
}

$DBG('fetched events (pre-filter) count=' . count($events));
if ($debug && !empty($events)) {
    $one = reset($events);
    $DBG(
        'sample pre-filter: id=' . ($one->id ?? 'null') .
        ' title=' . ($one->title ?? $one->name ?? 'null') .
        ' roomid=' . ($one->roomid ?? 'null') .
        ' dept=' . ($one->department ?? 'null') .
        ' status=' . ($one->bookingstatus ?? 'null') .
        ' timestart=' . ($one->timestart ?? $one->start ?? 'null') .
        ' timeend=' . ($one->timeend ?? $one->end ?? 'null')
    );
}

// --- Helpers to read array OR object and coerce dates -------------------.
/**
 * Get the first non-empty field found in $src for the given $keys.
 * Supports both arrays and objects.
 *
 * @param array|stdClass $src Source.
 * @param array $keys Keys to check.
 * @return mixed|null
 */
$evget = function($src, array $keys) {
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

/**
 * Turn various time formats into DateTime (user tz).
 * Accepts unix seconds, unix ms, or date strings.
 *
 * @param mixed $val Value to convert.
 * @return DateTime|null
 */
$toDT = function($val) {
    if ($val === null || $val === '') {
        return null;
    }
    $tz = core_date::get_user_timezone_object();
    if (is_numeric($val)) {
        // ms → s if it looks too large.
        $sec = (int)$val;
        if ($sec > 2000000000) {
            $sec = (int)round($sec / 1000);
        }
        return (new DateTime('@' . $sec))->setTimezone($tz);
    }
    try {
        return (new DateTime(str_replace('T', ' ', (string)$val)))->setTimezone($tz);
    } catch (Exception $e) {
        return null;
    }
};

// Helpful response header (will be overwritten later with final count too).
header('X-Bookit-PreFilter-Count: ' . (isset($events) ? count($events) : 0));

// -------- Apply filters in memory --------------------------------------.
$events = array_filter($events, function ($ev) use ($roomid, $faculty, $status, $search, $debug, $dbgprefix) {
    // Tiny helper to read from array or object.
    $get = function($src, array $keys) {
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

    /* --- ROOM filter (by resource id; supports multiple roomids) ------ */
    if ($roomid) {
        $evRoomIds = $get($ev, ['roomids']);                   // Array of ints (from JOIN).
        $evRoomId  = $get($ev, ['roomid', 'resourceid', 'rid']); // Single id if only one.

        $hasMatch = false;
        if (is_array($evRoomIds) && !empty($evRoomIds)) {
            $hasMatch = in_array((int)$roomid, array_map('intval', $evRoomIds), true);
        } else if ($evRoomId !== null && $evRoomId !== '') {
            $hasMatch = ((int)$evRoomId === (int)$roomid);
        }

        if (!$hasMatch) {
            if ($debug) {
                error_log(
                    $dbgprefix . 'DROP room (by id): want=' . (int)$roomid
                    . ' ev.roomid=' . var_export($evRoomId, true)
                    . ' ev.roomids=' . json_encode($evRoomIds)
                );
            }
            return false;
        }
    }

    /* --- FACULTY filter (trim + case-insensitive) -------------------- */
    if ($faculty !== '') {
        $evDept = (string)($get($ev, ['department', 'faculty', 'dept']) ?? '');
        $evDeptNorm = mb_strtolower(trim($evDept));
        $wantNorm   = mb_strtolower(trim((string)$faculty));

        if ($evDeptNorm === '') {
            if ($debug) {
                error_log($dbgprefix . 'DROP faculty: event has empty department');
            }
            return false;
        }
        if ($evDeptNorm !== $wantNorm) {
            if ($debug) {
                error_log($dbgprefix . 'DROP faculty mismatch want=' . $wantNorm . ' ev=' . $evDeptNorm);
            }
            return false;
        }
    }

    /* --- STATUS filter (strict, same as overview.php) ---------------- */
    if ($status > -1) {
        $evStatus = $get($ev, ['bookingstatus']); // Use DB field directly.
        if ($evStatus === null || $evStatus === '') {
            if ($debug) {
                error_log($dbgprefix . 'DROP status: event has no bookingstatus');
            }
            return false;
        }
        if ((int)$evStatus !== (int)$status) {
            if ($debug) {
                error_log($dbgprefix . 'DROP status mismatch want=' . (int)$status . ' ev=' . (int)$evStatus);
            }
            return false;
        }
    }

    /* --- SEARCH (case-insensitive in title + department) -------------- */
    if ($search !== '') {
        $needle   = mb_strtolower($search);
        $title    = (string)($get($ev, ['title', 'name', 'summary']) ?? '');
        $dept     = (string)($get($ev, ['department', 'faculty', 'dept']) ?? '');
        $haystack = mb_strtolower($title . ' ' . $dept);
        if (mb_strpos($haystack, $needle) === false) {
            if ($debug) {
                error_log($dbgprefix . 'DROP search miss');
            }
            return false;
        }
    }

    return true;
});

$DBG('events after filter count=' . count($events));
if ($debug && !empty($events)) {
    $ids = array_slice(array_map(fn($e) => $e->id ?? null, $events), 0, 10);
    $DBG('post-filter sample ids=' . implode(',', array_filter($ids, fn($x) => $x !== null)));
}

// ---------- Normalise & serialise JSON shape expected by JS ------------.
$out = [];
foreach ($events as $ev) {
    $id     = $evget($ev, ['id', 'eventid', 'eid', 'bookingid']);
    $title  = $evget($ev, ['title', 'name', 'eventname', 'summary']);

    // Times: accept many common variants.
    $startRaw = $evget($ev, ['timestart', 'start', 'starttime', 'start_date', 'datestart', 'date_start', 'from', 'begin', 'dtstart']);
    $endRaw   = $evget($ev, ['timeend', 'end', 'endtime', 'end_date', 'dateend', 'date_end', 'to', 'finish', 'dtend']);

    $dtStart = $toDT($startRaw);
    $dtEnd   = $toDT($endRaw);

    $startIso = $dtStart ? $dtStart->format('Y-m-d\TH:i') : null;
    $endIso   = $dtEnd   ? $dtEnd->format('Y-m-d\TH:i')   : null;

    // Room / location / department / status / reserved.
    $roomname = $evget($ev, ['roomname', 'room', 'location', 'locationname', 'resource', 'resourcename']) ?? '';
    $location = $evget($ev, ['location', 'locationname']) ?? $roomname;
    $dept     = $evget($ev, ['department', 'faculty', 'dept', 'organization', 'org']) ?? '';
    $status   = $evget($ev, ['bookingstatus', 'status', 'state', 'approvalstatus']);
    $reserved = (bool)($evget($ev, ['reserved', 'readonly', 'locked', 'isreserved']) ?? false);

    // Extra debug if something still looks off.
    if ($debug && (!$id || !$startIso)) {
        $DBG('WARN normalize: id=' . var_export($id, true) . ' start=' . var_export($startRaw, true) . ' → ' . $startIso);
    }

    $out[] = (object)[
        'id'            => (int)($id ?? 0),
        'title'         => (string)($title ?? ''),
        'start'         => $startIso,
        'end'           => $endIso,
        'location'      => (string)$location,
        'room'          => (string)$roomname,
        'department'    => (string)$dept,
        'bookingstatus' => $status !== null ? (int)$status : null,
        'reserved'      => $reserved,
    ];
}

// -------- Output --------------------------------------------------------.

header('X-Bookit-PostFilter-Count: ' . count($out));
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE);