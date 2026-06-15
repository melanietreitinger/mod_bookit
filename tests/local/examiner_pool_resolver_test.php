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
 * Unit tests for examiner pool resolution.
 *
 * @package     mod_bookit
 * @category    test
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

use advanced_testcase;

/**
 * Tests for {@see examiner_pool_resolver}.
 *
 * @covers \mod_bookit\local\examiner_pool_resolver
 */
final class examiner_pool_resolver_test extends advanced_testcase {
    /**
     * Username parsing should tolerate separators and duplicates.
     *
     * @return void
     */
    public function test_parse_usernames_normalises_input(): void {
        $parsed = examiner_pool_resolver::parse_usernames(" alpha, beta ; gamma\nbeta ");
        $this->assertSame(['alpha', 'beta', 'gamma'], $parsed);
    }

    /**
     * Empty config means unrestricted pool.
     *
     * @return void
     */
    public function test_empty_pool_is_unrestricted(): void {
        $resolver = examiner_pool_resolver::from_raw_config('');
        $this->assertFalse($resolver->is_restricted());
    }

    /**
     * Restricted pool should only resolve configured active users.
     *
     * @return void
     */
    public function test_restricted_pool_resolves_only_configured_users(): void {
        $this->resetAfterTest(true);

        $allowed = $this->getDataGenerator()->create_user(['username' => 'pool.examiner']);
        $this->getDataGenerator()->create_user(['username' => 'other.examiner']);

        $resolver = examiner_pool_resolver::from_raw_config('pool.examiner');
        $options = $resolver->build_options();

        $this->assertTrue($resolver->is_restricted());
        $this->assertArrayHasKey($allowed->id, $options);
        $this->assertCount(1, $options);
    }

    /**
     * Parsed usernames should be normalised to lowercase for matching.
     *
     * @return void
     */
    public function test_parse_usernames_normalises_case_for_matching(): void {
        $parsed = examiner_pool_resolver::parse_usernames('Pool.Examiner, POOL.second');
        $this->assertSame(['pool.examiner', 'pool.second'], $parsed);
    }

    /**
     * Restricted validation should reject out-of-pool assignments.
     *
     * @return void
     */
    public function test_validate_assignments_rejects_out_of_pool_users(): void {
        $this->resetAfterTest(true);

        $allowed = $this->getDataGenerator()->create_user(['username' => 'pool.examiner']);
        $blocked = $this->getDataGenerator()->create_user(['username' => 'blocked.examiner']);
        $resolver = examiner_pool_resolver::from_raw_config('pool.examiner');

        $errors = $resolver->validate_assignments((string)$blocked->id, (string)$blocked->id, []);
        $this->assertArrayHasKey('personinchargeid', $errors);
        $this->assertArrayHasKey('otherexaminers', $errors);

        $this->assertSame([], $resolver->validate_assignments((string)$allowed->id, '', []));
    }

    /**
     * Unrestricted pool should accept any submitted assignment ids.
     *
     * @return void
     */
    public function test_validate_assignments_allows_any_user_when_unrestricted(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $resolver = examiner_pool_resolver::from_raw_config('');

        $this->assertSame([], $resolver->validate_assignments((string)$user->id, (string)$user->id, []));
    }

    /**
     * Legacy assignments should remain permitted after the pool is narrowed.
     *
     * @return void
     */
    public function test_legacy_assignments_remain_permitted_and_visible(): void {
        $this->resetAfterTest(true);

        $legacy = $this->getDataGenerator()->create_user(['username' => 'legacy.examiner']);
        $resolver = examiner_pool_resolver::from_raw_config('new.examiner');

        $options = $resolver->build_options([$legacy->id]);
        $this->assertArrayHasKey($legacy->id, $options);

        $errors = $resolver->validate_assignments((string)$legacy->id, (string)$legacy->id, [$legacy->id]);
        $this->assertSame([], $errors);

        $errors = $resolver->validate_assignments((string)$legacy->id, '', []);
        $this->assertArrayHasKey('personinchargeid', $errors);
    }

    /**
     * Legacy user ids should be collected from stored booking fields.
     *
     * @return void
     */
    public function test_get_legacy_user_ids_from_event(): void {
        $legacyids = examiner_pool_resolver::get_legacy_user_ids_from_event((object)[
            'personinchargeid' => 11,
            'otherexaminers' => '12, 13',
        ]);

        $this->assertSame([11, 12, 13], $legacyids);
    }
}
