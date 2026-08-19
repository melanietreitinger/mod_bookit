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
 * Unit tests for the support person candidate lookup.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use advanced_testcase;
use context_course;
use context_coursecat;
use context_system;

/**
 * Unit tests for event_manager::get_support_person_candidates().
 *
 * @covers \mod_bookit\local\manager\event_manager::get_support_person_candidates
 */
final class event_manager_support_persons_test extends advanced_testcase {
    /**
     * Get the ID of a role, creating it only if the install baseline did not already provide it.
     *
     * @param string $shortname
     * @param string $archetype
     * @return int
     */
    private function ensure_role(string $shortname, string $archetype): int {
        global $DB;

        $existing = $DB->get_field('role', 'id', ['shortname' => $shortname], IGNORE_MISSING);
        if ($existing) {
            return (int) $existing;
        }
        return (int) \create_role(ucfirst($shortname), $shortname, $archetype);
    }

    /**
     * Only holders of the BookIt support roles are offered as support persons.
     *
     * @return void
     */
    public function test_only_users_with_support_roles_are_returned(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $serviceroleid = $this->ensure_role('bookit_serviceteam', 'manager');
        $supportroleid = $this->ensure_role('bookit_supportonsite', 'student');
        $examinerroleid = $this->ensure_role('bookit_examiner', 'student');

        $service = $this->getDataGenerator()->create_user(['lastname' => 'Aaa', 'firstname' => 'Service']);
        $support = $this->getDataGenerator()->create_user(['lastname' => 'Bbb', 'firstname' => 'Support']);
        $examiner = $this->getDataGenerator()->create_user(['lastname' => 'Ccc', 'firstname' => 'Examiner']);
        $noroles = $this->getDataGenerator()->create_user(['lastname' => 'Ddd', 'firstname' => 'Nobody']);

        \role_assign($serviceroleid, $service->id, $context->id);
        \role_assign($supportroleid, $support->id, $context->id);
        \role_assign($examinerroleid, $examiner->id, $context->id);

        $candidates = event_manager::get_support_person_candidates($context);

        $this->assertArrayHasKey((int) $service->id, $candidates);
        $this->assertArrayHasKey((int) $support->id, $candidates);
        $this->assertArrayNotHasKey((int) $examiner->id, $candidates);
        $this->assertArrayNotHasKey((int) $noroles->id, $candidates);
        $this->assertSame(fullname($service), $candidates[(int) $service->id]);
    }

    /**
     * Role assignments made in a parent context count, assignments in a sibling course do not.
     *
     * @return void
     */
    public function test_parent_context_assignments_are_included(): void {
        $this->resetAfterTest(true);

        $categoryid = $this->getDataGenerator()->create_category()->id;
        $course = $this->getDataGenerator()->create_course(['category' => $categoryid]);
        $othercourse = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $serviceroleid = $this->ensure_role('bookit_serviceteam', 'manager');

        $systemuser = $this->getDataGenerator()->create_user(['lastname' => 'System']);
        $categoryuser = $this->getDataGenerator()->create_user(['lastname' => 'Category']);
        $courseuser = $this->getDataGenerator()->create_user(['lastname' => 'Course']);
        $siblinguser = $this->getDataGenerator()->create_user(['lastname' => 'Sibling']);

        \role_assign($serviceroleid, $systemuser->id, context_system::instance()->id);
        \role_assign($serviceroleid, $categoryuser->id, context_coursecat::instance($categoryid)->id);
        \role_assign($serviceroleid, $courseuser->id, $context->id);
        \role_assign($serviceroleid, $siblinguser->id, context_course::instance($othercourse->id)->id);

        $candidates = event_manager::get_support_person_candidates($context);

        $this->assertArrayHasKey((int) $systemuser->id, $candidates);
        $this->assertArrayHasKey((int) $categoryuser->id, $candidates);
        $this->assertArrayHasKey((int) $courseuser->id, $candidates);
        $this->assertArrayNotHasKey((int) $siblinguser->id, $candidates);
    }

    /**
     * Deleted and suspended role holders are not offered.
     *
     * @return void
     */
    public function test_deleted_and_suspended_users_are_excluded(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $serviceroleid = $this->ensure_role('bookit_serviceteam', 'manager');

        $active = $this->getDataGenerator()->create_user(['lastname' => 'Active']);
        $suspended = $this->getDataGenerator()->create_user(['lastname' => 'Suspended', 'suspended' => 1]);
        $deleted = $this->getDataGenerator()->create_user(['lastname' => 'Deleted', 'deleted' => 1]);

        \role_assign($serviceroleid, $active->id, $context->id);
        \role_assign($serviceroleid, $suspended->id, $context->id);
        \role_assign($serviceroleid, $deleted->id, $context->id);

        $candidates = event_manager::get_support_person_candidates($context);

        $this->assertArrayHasKey((int) $active->id, $candidates);
        $this->assertArrayNotHasKey((int) $suspended->id, $candidates);
        $this->assertArrayNotHasKey((int) $deleted->id, $candidates);
    }

    /**
     * Users already stored on the event stay selectable even without the role, so that editing the
     * event does not silently drop them. Deleted users are still filtered out.
     *
     * @return void
     */
    public function test_already_selected_users_are_kept(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $this->ensure_role('bookit_serviceteam', 'manager');

        $formerhelper = $this->getDataGenerator()->create_user(['lastname' => 'Former']);
        $suspendedhelper = $this->getDataGenerator()->create_user(['lastname' => 'Suspended', 'suspended' => 1]);
        $deletedhelper = $this->getDataGenerator()->create_user(['lastname' => 'Deleted', 'deleted' => 1]);

        $selected = implode(',', [$formerhelper->id, $suspendedhelper->id, $deletedhelper->id]);
        $candidates = event_manager::get_support_person_candidates($context, $selected);

        $this->assertArrayHasKey((int) $formerhelper->id, $candidates);
        $this->assertArrayHasKey((int) $suspendedhelper->id, $candidates);
        $this->assertArrayNotHasKey((int) $deletedhelper->id, $candidates);
    }

    /**
     * A user holding both roles in several contexts is returned once.
     *
     * Without DISTINCT the duplicated rows would make get_records_sql() emit a developer debugging
     * message, which fails this test case.
     *
     * @return void
     */
    public function test_users_with_multiple_assignments_are_not_duplicated(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        $serviceroleid = $this->ensure_role('bookit_serviceteam', 'manager');
        $supportroleid = $this->ensure_role('bookit_supportonsite', 'student');

        $user = $this->getDataGenerator()->create_user(['lastname' => 'Both']);
        \role_assign($serviceroleid, $user->id, $context->id);
        \role_assign($supportroleid, $user->id, $context->id);
        \role_assign($serviceroleid, $user->id, context_system::instance()->id);

        $candidates = event_manager::get_support_person_candidates($context);

        $this->assertArrayHasKey((int) $user->id, $candidates);
        $this->assertCount(1, $candidates);
    }

    /**
     * Without the support roles the lookup returns an empty list instead of every user on the site.
     *
     * @return void
     */
    public function test_missing_roles_return_empty_list(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_user();

        foreach (event_manager::SUPPORT_PERSON_ROLES as $shortname) {
            if ($roleid = $DB->get_field('role', 'id', ['shortname' => $shortname], IGNORE_MISSING)) {
                \delete_role((int) $roleid);
            }
        }

        $this->assertSame([], event_manager::get_support_person_candidates(context_course::instance($course->id)));
    }
}
