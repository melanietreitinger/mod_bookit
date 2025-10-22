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

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();

// Mark installation helper as finished first to avoid session mutation issues.
set_config('installhelperfinished', 1, 'mod_bookit');

// Run the installation helper.
$rolesimported = install_helper::import_default_roles(false, false);
$usersimported = install_helper::import_default_users(false, false);
$result = install_helper::create_default_checklists(false, false);

// Redirect back to settings.
$returnurl = new moodle_url('/admin/settings.php', ['section' => 'mod_bookit_settings']);
redirect($returnurl, 'Installation helper completed successfully.');