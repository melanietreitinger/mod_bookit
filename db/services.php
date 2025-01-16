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
 * External functions and service definitions.
 *
 * @package     mod_bookit
 * @category    external
 * @copyright   2025 Alexander Mikasch, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array_merge($functions ?? [], [
    'mod_bookit_get_config' => [
        'classname'     => 'mod_bookit\external\get_config',
        'methodname'    => 'execute',
        'description'   => 'Get BookIt module configuration',
        'type'         => 'read',
        'ajax'         => true,
        'loginrequired' => true,
        'capabilities' => 'mod/bookit:view'
    ]
]); 