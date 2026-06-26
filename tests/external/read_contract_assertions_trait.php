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

namespace mod_bookit\tests;

/**
 * Reusable assertions for canonical Bookit read contracts.
 *
 * @package mod_bookit
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait read_contract_assertions_trait {
    /**
     * Assert that no forbidden keys are present.
     *
     * @param array $payload
     * @param array $forbiddenkeys
     * @return void
     */
    protected function assert_forbidden_keys_absent(array $payload, array $forbiddenkeys): void {
        foreach ($forbiddenkeys as $key) {
            $this->assertArrayNotHasKey($key, $payload, 'Forbidden key "' . $key . '" must be absent.');
        }
    }

    /**
     * Assert the canonical calendar event shape.
     *
     * @param array $event
     * @return void
     */
    protected function assert_is_canonical_calendar_event(array $event): void {
        $this->assertSame(
            ['id', 'title', 'start', 'end', 'backgroundColor', 'textColor', 'classNames', 'extendedProps'],
            array_keys($event)
        );
        $this->assertGreaterThan(0, (int)$event['id']);
        $this->assertIsString($event['title']);
        $this->assertIsString($event['start']);
        $this->assertIsString($event['end']);
        $this->assertIsString($event['backgroundColor']);
        $this->assertIsString($event['textColor']);
        $this->assertIsArray($event['classNames']);
        $this->assertIsArray($event['extendedProps']);

        $this->assert_forbidden_keys_absent($event, [
            'eventid',
            'titlehtml',
            'titleHTML',
            'bookingstatus',
            'semesterid',
            'visibilitymode',
            'room',
            'roomid',
            'roomname',
            'location',
            'shortname',
            'facultyid',
            'faculty',
            'department',
            'style',
            'extendedprops',
        ]);

        $extendedprops = $event['extendedProps'];
        $this->assertArrayHasKey('titlehtml', $extendedprops);
        $this->assertArrayHasKey('bookingstatus', $extendedprops);
        $this->assertArrayHasKey('semesterid', $extendedprops);
        $this->assertArrayHasKey('visibilitymode', $extendedprops);
        if (($extendedprops['visibilitymode'] ?? '') === 'full') {
            $this->assertArrayHasKey('modalfootermode', $extendedprops);
            $this->assertContains(
                $extendedprops['modalfootermode'],
                ['view_only', 'editable', ''],
                'modalfootermode must be view_only or editable when present on full events'
            );
        }
        $this->assertArrayHasKey('room', $extendedprops);
        $this->assertIsString($extendedprops['titlehtml']);
        $this->assertIsInt($extendedprops['bookingstatus']);
        $this->assertIsInt($extendedprops['semesterid']);
        $this->assertContains($extendedprops['visibilitymode'], ['full', 'reserved_projection']);
        $this->assertIsArray($extendedprops['room']);
        $this->assertSame(
            ['roomid', 'roomname', 'location', 'shortname'],
            array_keys($extendedprops['room'])
        );

        if (array_key_exists('faculty', $extendedprops)) {
            $this->assertSame(['facultyid', 'label'], array_keys($extendedprops['faculty']));
        }
    }

    /**
     * Assert the canonical room availability entry shape.
     *
     * @param array $entry
     * @return void
     */
    protected function assert_is_canonical_room_availability_entry(array $entry): void {
        $this->assertSame(
            ['id', 'title', 'start', 'end', 'backgroundColor', 'extendedProps'],
            array_keys($entry)
        );
        $this->assertIsString($entry['id']);
        $this->assertNotSame('', $entry['id']);
        $this->assertIsString($entry['title']);
        $this->assertIsString($entry['start']);
        $this->assertIsString($entry['end']);
        $this->assertIsString($entry['backgroundColor']);
        $this->assertIsArray($entry['extendedProps']);
        $this->assert_forbidden_keys_absent($entry, ['type', 'color', 'resourceid', 'meta']);
        $this->assertSame(['type', 'roomid'], array_keys($entry['extendedProps']));
        $this->assertContains($entry['extendedProps']['type'], ['slot', 'blocker', 'reservation']);
        $this->assertIsInt($entry['extendedProps']['roomid']);
    }
}
