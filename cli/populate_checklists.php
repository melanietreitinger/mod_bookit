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
 * CLI script to populate test data for BookIt checklists.
 *
 * @package    mod_bookit
 * @copyright  2025 ssystems GmbH <oss@ssystems.de>
 * @author     Andreas Rosenthal
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/bookit/classes/local/install_helper.php');

use mod_bookit\local\install_helper;

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'force' => false,
    ],
    [
        'h' => 'help',
        'f' => 'force',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "CLI script to populate BookIt checklists with test data.

Options:
-h, --help          Print this help.
-f, --force         Force execution even if test data already exists.

Example:
\$ php cli/populate_checklists.php
";
    cli_writeln($help);
    exit(0);
}

cli_heading('Creating test checklist data for BookIt');
cli_writeln('');

$result = install_helper::create_default_checklists($options['force'], true);

if ($result) {
    cli_writeln('Test data population completed successfully!');
} else {
    cli_writeln('Test data population skipped - data already exists.');
}
