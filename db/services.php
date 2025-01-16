<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * BookIt external functions and service definitions.
 *
 * @package    mod_bookit
 * @category   external
 * @copyright  2025 Alexander Mikasch, Ruhr-Universität Bochum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_bookit_get_rooms' => [
        'classname'     => 'mod_bookit\external\get_rooms',
        'methodname'    => 'execute',
        'description'   => 'Ruft die verfügbaren Räume ab.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'mod/bookit:view'
    ],
    'mod_bookit_get_faculties' => [
        'classname'     => 'mod_bookit\external\get_faculties',
        'methodname'    => 'execute',
        'description'   => 'Ruft die verfügbaren Fakultäten/Abteilungen ab.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'mod/bookit:view'
    ],
    'mod_bookit_get_statuses' => [
        'classname'     => 'mod_bookit\external\get_statuses',
        'methodname'    => 'execute',
        'description'   => 'Ruft die verfügbaren Status ab.',
        'type'         => 'read',
        'ajax'         => true,
        'capabilities' => 'mod/bookit:view'
    ]
]; 