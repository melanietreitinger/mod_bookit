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
 * Resolves the configured examiner pool for booking forms and validation.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local;

use dml_exception;
use stdClass;

/**
 * Shared examiner-pool resolution for UI options and server-side validation.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class examiner_pool_resolver {
    /** @var string[] */
    private array $parsedusernames;

    /**
     * Create a resolver for an already parsed username list.
     *
     * @param string[] $parsedusernames Normalised username tokens from plugin config.
     */
    private function __construct(array $parsedusernames) {
        $this->parsedusernames = $parsedusernames;
    }

    /**
     * Build a resolver from the plugin config object.
     *
     * @param \stdClass $config
     * @return self
     */
    public static function from_config(stdClass $config): self {
        return self::from_raw_config((string)($config->examiner_pool_usernames ?? ''));
    }

    /**
     * Build a resolver from the raw admin textarea value.
     *
     * @param string $rawconfig
     * @return self
     */
    public static function from_raw_config(string $rawconfig): self {
        return new self(self::parse_usernames($rawconfig));
    }

    /**
     * Parse a comma/semicolon/whitespace separated username list.
     *
     * @param string $rawconfig
     * @return string[]
     */
    public static function parse_usernames(string $rawconfig): array {
        $tokens = preg_split('/[\s,;]+/', $rawconfig) ?: [];
        $usernames = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            $usernames[] = \core_text::strtolower($token);
        }

        return array_values(array_unique($usernames));
    }

    /**
     * Whether the pool restricts examiner selection.
     *
     * @return bool
     */
    public function is_restricted(): bool {
        return !empty($this->parsedusernames);
    }

    /**
     * Collect legacy examiner user ids already stored on an event.
     *
     * @param \stdClass|null $event
     * @return int[]
     */
    public static function get_legacy_user_ids_from_event(?stdClass $event): array {
        if ($event === null) {
            return [];
        }

        $legacyids = [];
        if (!empty($event->personinchargeid)) {
            $legacyids[] = (int)$event->personinchargeid;
        }

        if (!empty($event->otherexaminers)) {
            foreach (explode(',', (string)$event->otherexaminers) as $rawid) {
                $rawid = trim($rawid);
                if ($rawid === '' || !is_numeric($rawid)) {
                    continue;
                }
                $legacyids[] = (int)$rawid;
            }
        }

        return array_values(array_unique(array_filter($legacyids)));
    }

    /**
     * Resolve active pool member user ids.
     *
     * @return int[]
     * @throws dml_exception
     */
    public function get_pool_user_ids(): array {
        $users = $this->load_pool_users();
        return array_map('intval', array_keys($users));
    }

    /**
     * Build autocomplete options for examiner selectors.
     *
     * @param int[] $legacyuserids Additional ids that must remain visible on existing bookings.
     * @return array<int,string>
     * @throws dml_exception
     */
    public function build_options(array $legacyuserids = []): array {
        global $DB;

        $options = [];
        foreach ($this->load_pool_users() as $id => $user) {
            $options[(int)$id] = self::format_user_label($user);
        }

        $missinglegacyids = array_diff(array_map('intval', $legacyuserids), array_keys($options));
        if (!empty($missinglegacyids)) {
            $options += self::build_options_for_user_ids($missinglegacyids);
        }

        return $options;
    }

    /**
     * Build a readable selector label for a user record.
     *
     * @param \stdClass $user
     * @return string
     */
    public static function format_user_label(stdClass $user): string {
        $name = fullname($user);
        if (trim($name) !== '' && !empty($user->email)) {
            return $name . ' | ' . $user->email;
        }
        if (trim($name) !== '') {
            return $name;
        }
        if (!empty($user->username)) {
            return (string)$user->username;
        }

        return get_string('examiner_display_unknown_user', 'mod_bookit');
    }

    /**
     * Resolve readable autocomplete labels for the given user ids.
     *
     * Deleted or suspended users remain readable when their record still exists.
     * Missing records fall back to a neutral placeholder instead of a bare id.
     *
     * @param int[] $userids
     * @return array<int,string>
     */
    public static function build_options_for_user_ids(array $userids): array {
        global $DB;

        $userids = array_values(array_unique(array_filter(array_map('intval', $userids))));
        if ($userids === []) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $users = $DB->get_records_sql("SELECT u.* FROM {user} u WHERE u.id $insql", $params);

        $options = [];
        foreach ($userids as $userid) {
            if (isset($users[$userid])) {
                $options[$userid] = self::format_user_label($users[$userid]);
                continue;
            }

            $options[$userid] = self::format_fallback_label($userid);
        }

        return $options;
    }

    /**
     * Fallback label when a stored user id can no longer be resolved.
     *
     * @param int $userid
     * @return string
     */
    public static function format_fallback_label(int $userid): string {
        return get_string('examiner_display_unknown_user', 'mod_bookit', $userid);
    }

    /**
     * Validate submitted examiner assignments against the configured pool.
     *
     * @param string|null $personinchargeid
     * @param string|null $otherexaminers
     * @param int[] $legacyuserids
     * @return array<string,string> Field name to error message.
     */
    public function validate_assignments(
        ?string $personinchargeid,
        ?string $otherexaminers,
        array $legacyuserids = []
    ): array {
        if (!$this->is_restricted()) {
            return [];
        }

        $permitted = array_fill_keys(
            array_unique(array_merge($this->get_pool_user_ids(), array_map('intval', $legacyuserids))),
            true
        );
        $errors = [];

        $personid = (int)($personinchargeid ?? 0);
        if ($personid !== 0 && !isset($permitted[$personid])) {
            $errors['personinchargeid'] = get_string('examiner_pool_invalid_assignment', 'mod_bookit');
        }

        foreach (self::parse_user_id_list($otherexaminers) as $otherid) {
            if (!isset($permitted[$otherid])) {
                $errors['otherexaminers'] = get_string('examiner_pool_invalid_assignment', 'mod_bookit');
                break;
            }
        }

        return $errors;
    }

    /**
     * Parse a comma-separated user id list from form data.
     *
     * @param string|null $rawvalue
     * @return int[]
     */
    public static function parse_user_id_list(?string $rawvalue): array {
        if ($rawvalue === null || $rawvalue === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $rawvalue) as $rawid) {
            $rawid = trim($rawid);
            if ($rawid === '' || !is_numeric($rawid)) {
                continue;
            }
            $ids[] = (int)$rawid;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Load active users that match the configured pool usernames.
     *
     * @return array<int,\stdClass>
     * @throws dml_exception
     */
    private function load_pool_users(): array {
        global $DB;

        if (!$this->is_restricted()) {
            $sql = "SELECT DISTINCT u.*
                      FROM {user} u
                     WHERE u.deleted = 0 AND u.suspended = 0
                  ORDER BY lastname, firstname";
            return $DB->get_records_sql($sql, []);
        }

        [$insql, $params] = $DB->get_in_or_equal($this->parsedusernames, SQL_PARAMS_NAMED);
        foreach ($params as $key => $value) {
            $params[$key] = \core_text::strtolower($value);
        }

        $sql = "SELECT DISTINCT u.*
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND LOWER(" . $DB->sql_compare_text('u.username') . ") $insql
              ORDER BY lastname, firstname";

        return $DB->get_records_sql($sql, $params);
    }
}
