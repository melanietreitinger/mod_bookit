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
 * Plugin capabilities are defined here.
 *
 * @package     mod_bookit
 * @category    access
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
       // Add a new event.
        'mod/bookit:addevent' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // Add course module (mandatory capability).
        'mod/bookit:addinstance' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
                'clonepermissionsfrom' => 'moodle/course:manageactivities',
        ],
        // Add a new resource.
        'mod/bookit:addresource' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // Edit an existing event.
        'mod/bookit:editevent' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // Edit an internal field.
        'mod/bookit:editinternal' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],

        ],
        // Edit an existing resource.
        'mod/bookit:editresource' => [
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // View course module.
        'mod/bookit:view' => [
                'captype' => 'view',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // View all details of event.
        'mod/bookit:viewalldetailsofevent' => [
                'captype' => 'view',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // View all details of own event.
        'mod/bookit:viewalldetailsofownevent' => [
                'captype' => 'view',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                ],
        ],
        // New: Implements the selected view.
        'mod/bookit:viewownoverview' => [
                'captype'      => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes'   => [
                ],
        ],
        'mod/bookit:managemasterchecklist' => [
                'riskbitmask' => RISK_CONFIG,
                'captype' => 'write',
                'contextlevel' => CONTEXT_SYSTEM,
                'archetypes' => [
                'manager' => CAP_ALLOW
                ]
        ]
];
