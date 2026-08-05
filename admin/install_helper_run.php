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
 * Installation helper runner.
 *
 * @package     mod_bookit
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_bookit\local\install_helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_admin();
require_sesskey();

/**
 * Resolve the combined setup outcome across baseline and role import reports.
 *
 * @param string[] $statuses
 * @return string
 */
function mod_bookit_resolve_install_helper_status(array $statuses): string {
    $statuses = array_values(array_unique($statuses));
    if (in_array('failed', $statuses, true)) {
        return count($statuses) > 1 ? 'partial' : 'failed';
    }
    if (in_array('partial', $statuses, true)) {
        return 'partial';
    }
    if (in_array('success', $statuses, true)) {
        return 'success';
    }
    return 'idempotent';
}

$rolereport = install_helper::import_default_roles_with_report(false, false);
$baselinereport = install_helper::ensure_fresh_install_baseline(false);
$status = mod_bookit_resolve_install_helper_status([
    $rolereport['status'],
    $baselinereport['status'],
]);

if (in_array($baselinereport['status'], ['success', 'idempotent'], true)) {
    set_config('installhelperfinished', 1, 'mod_bookit');
} else {
    set_config('installhelperfinished', 0, 'mod_bookit');
}

$details = array_merge($rolereport['errors'], $baselinereport['errors']);
$baselinecreated = 0;
$baselineverified = 0;
foreach ($baselinereport['operations'] as $operation) {
    if (($operation['status'] ?? '') === 'created') {
        $baselinecreated++;
    } else if (($operation['status'] ?? '') === 'idempotent') {
        $baselineverified++;
    }
}

// Redirect back to settings.
$params = [
    'section' => 'modsettingbookit',
    'installhelperstatus' => $status,
    'rolesimported' => count($rolereport['imported']),
    'rolesskipped' => count($rolereport['skipped']),
    'baselinecreated' => $baselinecreated,
    'baselineverified' => $baselineverified,
    'installhelpererrors' => count($details),
];
if (!empty($details)) {
    $params['installhelperdetails'] = implode(' | ', $details);
}

$returnurl = new moodle_url('/admin/settings.php', $params);
redirect($returnurl);
