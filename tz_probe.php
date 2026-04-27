<?php
require(__DIR__ . '/../../config.php');
require_login();
$ts = required_param('ts', PARAM_INT);

header('Content-Type: text/plain; charset=utf-8');

echo "PHP date.timezone ini: " . ini_get('date.timezone') . "\n";
echo "PHP date_default_timezone_get(): " . date_default_timezone_get() . "\n";
echo "Moodle CFG->timezone: " . ($CFG->timezone ?? '(not set)') . "\n";
echo "Server NOW (date()): " . date('Y-m-d H:i:s T') . "\n";
echo "Server NOW (gmdate()): " . gmdate('Y-m-d H:i:s') . " UTC\n";
echo "\n";
echo "Probe timestamp: $ts\n";
echo "  date():    " . date('Y-m-d H:i:s T', $ts) . "\n";
echo "  gmdate():  " . gmdate('Y-m-d H:i:s', $ts) . " UTC\n";
echo "  userdate(): " . userdate($ts) . "\n";
echo "  userdate(forcetz=Berlin): " . userdate($ts, '%Y-%m-%d %H:%M:%S', 'Europe/Berlin') . "\n";