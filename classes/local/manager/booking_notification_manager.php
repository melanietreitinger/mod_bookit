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
 * Notification manager for booking status changes.
 *
 * @package     mod_bookit
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @author      Andreas Rosenthal
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bookit\local\manager;

use context_module;
use core_user;
use dml_exception;
use moodle_url;
use mod_bookit\local\install_helper;
use mod_bookit\local\manager\event_manager;
use stdClass;

/**
 * Sends Moodle messages for booking status changes.
 */
class booking_notification_manager {
    /**
     * Notify configured recipients after a booking status change.
     *
     * @param int $cmid
     * @param int $eventid
     * @param int $oldstatus
     * @param int $newstatus
     * @return int
     * @throws dml_exception
     */
    public static function notify_status_changed(int $cmid, int $eventid, int $oldstatus, int $newstatus): int {
        global $DB;

        $event = $DB->get_record('bookit_event', ['id' => $eventid]);
        if (!$event) {
            return 0;
        }

        $notificationstatus = install_helper::get_booking_status_notification_status_by_id($newstatus);
        if ($notificationstatus === null || !self::is_notification_enabled($notificationstatus)) {
            return 0;
        }

        $recipientids = self::collect_recipient_ids($event, $cmid);
        $template = self::get_template_data($event, $cmid, $oldstatus, $newstatus, $notificationstatus);
        $sent = 0;

        if (!empty($recipientids)) {
            $sent += self::send_user_notifications($recipientids, $template, $cmid);
        }

        $serviceaddresses = self::get_service_addresses();
        if (!empty($serviceaddresses)) {
            $sent += self::send_service_notifications($serviceaddresses, $template);
        }

        return $sent;
    }

    /**
     * Collect all configured user recipients from the event.
     *
     * @param stdClass $event
     * @param int $cmid
     * @return int[]
     */
    private static function collect_recipient_ids(stdClass $event, int $cmid): array {
        $recipientids = array_merge(
            self::collect_participant_recipient_ids($event),
            self::collect_service_team_recipient_ids($cmid)
        );

        return array_values(array_unique(array_filter(array_map('intval', $recipientids))));
    }

    /**
     * Collect all participant-based user recipients from the event.
     *
     * @param stdClass $event
     * @return int[]
     */
    private static function collect_participant_recipient_ids(stdClass $event): array {
        $recipientids = [];
        $requesterid = self::resolve_requester_id($event);

        if ((int)get_config('mod_bookit', 'bookingstatus_notify_bookingperson') !== 0 && $requesterid > 0) {
            $recipientids[] = $requesterid;
        }

        if ((int)get_config('mod_bookit', 'bookingstatus_notify_personincharge') !== 0 && !empty($event->personinchargeid)) {
            $recipientids[] = (int)$event->personinchargeid;
        }

        if ((int)get_config('mod_bookit', 'bookingstatus_notify_otherexaminers') !== 0 && !empty($event->otherexaminers)) {
            foreach (explode(',', $event->otherexaminers) as $rawid) {
                $id = (int)trim($rawid);
                if ($id > 0) {
                    $recipientids[] = $id;
                }
            }
        }

        return $recipientids;
    }

    /**
     * Collect service-team users resolved from the module context capability.
     *
     * @param int $cmid
     * @return int[]
     */
    private static function collect_service_team_recipient_ids(int $cmid): array {
        if ((int)get_config('mod_bookit', 'bookingstatus_notify_serviceteam') !== 1) {
            return [];
        }

        $context = context_module::instance($cmid, IGNORE_MISSING);
        if (!$context) {
            return [];
        }

        $users = get_users_by_capability(
            $context,
            'mod/bookit:managebasics',
            'u.id',
            '',
            '',
            '',
            '',
            '',
            false
        );

        return array_map(static fn(stdClass $user): int => (int)$user->id, array_values($users));
    }

    /**
     * Resolve the stable requester for a booking request.
     *
     * @param stdClass $event
     * @return int
     * @throws dml_exception
     */
    private static function resolve_requester_id(stdClass $event): int {
        global $DB;

        $requesterid = $DB->get_field_sql(
            "SELECT usermodified
               FROM {bookit_event_history}
              WHERE eventid = :eventid
                AND action = :action
                AND usermodified IS NOT NULL
           ORDER BY timecreated ASC, id ASC",
            ['eventid' => (int)$event->id, 'action' => 'created'],
            IGNORE_MULTIPLE
        );
        if (!empty($requesterid)) {
            return (int)$requesterid;
        }

        $earliesteditor = $DB->get_field_sql(
            "SELECT usermodified
               FROM {bookit_event_history}
              WHERE eventid = :eventid
                AND usermodified IS NOT NULL
           ORDER BY timecreated ASC, id ASC",
            ['eventid' => (int)$event->id],
            IGNORE_MULTIPLE
        );
        if (!empty($earliesteditor)) {
            return (int)$earliesteditor;
        }

        return (int)($event->usercreated ?? 0);
    }

    /**
     * Get configured service email addresses.
     *
     * @return string[]
     */
    private static function get_service_addresses(): array {
        $raw = trim((string)get_config('mod_bookit', 'bookingstatus_service_addresses'));
        if ($raw === '') {
            return [];
        }

        $addresses = preg_split('/[\s,;]+/', $raw);
        $addresses = array_filter(array_map('trim', $addresses));
        return array_values(array_unique($addresses));
    }

    /**
     * Prepare replacement data and fallback templates.
     *
     * @param stdClass $event
     * @param int $cmid
     * @param int $oldstatus
     * @param int $newstatus
     * @param array $notificationstatus Resolved expressive status metadata.
     * @return stdClass
     */
    private static function get_template_data(
        stdClass $event,
        int $cmid,
        int $oldstatus,
        int $newstatus,
        array $notificationstatus
    ): stdClass {
        $url = new moodle_url('/mod/bookit/view.php', ['id' => $cmid, 'eventid' => $event->id]);

        $data = new stdClass();
        $data->eventname = $event->name ?? '';
        $data->eventurl = $url->out(false);
        $data->room = self::get_room_name((int)($event->roomid ?? 0));
        $data->starttimestamp = (int)($event->starttime ?? 0);
        $data->endtimestamp = (int)($event->endtime ?? 0);
        $data->bookingperson = self::get_user_name(self::resolve_requester_id($event));
        $data->personincharge = self::get_user_name((int)($event->personinchargeid ?? 0));
        $data->otherexaminers = self::get_user_names_csv((string)($event->otherexaminers ?? ''));
        $data->newstatus = $newstatus;
        $data->oldstatus = $oldstatus;
        $data->statuskey = $notificationstatus['key'];

        return self::localize_template_data($data);
    }

    /**
     * Check whether the resolved expressive status type is enabled.
     *
     * @param array $notificationstatus Resolved expressive status metadata.
     * @return bool
     */
    private static function is_notification_enabled(array $notificationstatus): bool {
        $enabled = get_config('mod_bookit', $notificationstatus['enabledconfig']);
        return $enabled === false ? true : (int)$enabled === 1;
    }

    /**
     * Send notifications to user accounts via Moodle messages.
     *
     * @param int[] $recipientids
     * @param stdClass $template
     * @param int $cmid
     * @return int
     */
    private static function send_user_notifications(array $recipientids, stdClass $template, int $cmid): int {
        $userfrom = core_user::get_noreply_user();
        $sent = 0;

        foreach ($recipientids as $recipientid) {
            $userto = core_user::get_user($recipientid);
            if (!$userto || !$userto->id || $userto->deleted || $userto->suspended) {
                continue;
            }

            $recipientlang = install_helper::normalize_booking_status_notification_language((string)($userto->lang ?? ''));
            $localizedtemplate = self::localize_template_data($template, $recipientlang);
            $subject = self::replace_placeholders(
                self::resolve_template_text($localizedtemplate, 'subject', $recipientlang),
                $localizedtemplate
            );
            $body = self::replace_placeholders(
                self::resolve_template_text($localizedtemplate, 'body', $recipientlang),
                $localizedtemplate
            );
            $body = self::format_message_body($body, $localizedtemplate, $recipientlang);

            $message = new \core\message\message();
            $message->component = 'mod_bookit';
            $message->name = 'bookit_booking_status_changed';
            $message->userfrom = $userfrom;
            $message->userto = $userto;
            $message->subject = $subject;
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = text_to_html($body, false, false, true);
            $message->smallmessage = $subject;
            $message->notification = 1;
            $message->contexturl = $template->eventurl;
            $message->contexturlname = $template->eventname;
            $message->courseid = self::get_course_id($cmid);

            message_send($message);
            $sent++;
        }

        return $sent;
    }

    /**
     * Send notifications to external service addresses.
     *
     * @param string[] $serviceaddresses
     * @param stdClass $template
     * @return int
     */
    private static function send_service_notifications(array $serviceaddresses, stdClass $template): int {
        $userfrom = core_user::get_noreply_user();
        $recipientlang = install_helper::normalize_booking_status_notification_language(current_language());
        $localizedtemplate = self::localize_template_data($template, $recipientlang);
        $subject = self::replace_placeholders(
            self::resolve_template_text($localizedtemplate, 'subject', $recipientlang),
            $localizedtemplate
        );
        $body = self::replace_placeholders(
            self::resolve_template_text($localizedtemplate, 'body', $recipientlang),
            $localizedtemplate
        );
        $body = self::format_message_body($body, $localizedtemplate, $recipientlang);
        $sent = 0;

        foreach ($serviceaddresses as $address) {
            if (!validate_email($address)) {
                continue;
            }

            $recipient = clone $userfrom;
            $recipient->id = -1;
            $recipient->email = $address;
            $recipient->firstname = get_string('bookingstatus_service_recipient_firstname', 'mod_bookit');
            $recipient->lastname = get_string('bookingstatus_service_recipient_lastname', 'mod_bookit');

            if (email_to_user($recipient, $userfrom, $subject, $body, text_to_html($body, false, false, true))) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Replace supported placeholders in the configured template text.
     *
     * @param string $template
     * @param stdClass $data
     * @return string
     */
    private static function replace_placeholders(string $template, stdClass $data): string {
        $replacements = [
            '###EVENTNAME###' => $data->eventname,
            '###BOOKINGDATE###' => $data->bookingdate,
            '###BOOKINGSTATUS###' => $data->bookingstatus,
            '###OLDBOOKINGSTATUS###' => $data->oldbookingstatus,
            '###EVENTURL###' => $data->eventurl,
            '###ROOM###' => $data->room,
            '###STARTTIME###' => $data->starttime,
            '###ENDTIME###' => $data->endtime,
            '###BOOKINGPERSON###' => $data->bookingperson,
            '###PERSONINCHARGE###' => $data->personincharge,
            '###OTHEREXAMINERS###' => $data->otherexaminers,
        ];

        return strtr($template, $replacements);
    }

    /**
     * Wrap the resolved notification body in a shared greeting, detail block and closing.
     *
     * @param string $body
     * @param stdClass $data
     * @param string $lang
     * @return string
     */
    private static function format_message_body(string $body, stdClass $data, string $lang): string {
        $sections = array_filter([
            trim(install_helper::get_booking_status_notification_greeting($lang)),
            trim($body),
            trim(self::build_booking_details_block($data, $lang)),
            trim(install_helper::get_booking_status_notification_closing($lang)),
        ], static fn(string $section): bool => $section !== '');

        return implode("\n\n", $sections);
    }

    /**
     * Build a consistent plain-text detail block for booking notifications.
     *
     * @param stdClass $data
     * @param string $lang
     * @return string
     */
    private static function build_booking_details_block(stdClass $data, string $lang): string {
        $oldlang = force_current_language(install_helper::normalize_booking_status_notification_language($lang));
        $template = get_string('bookingstatus_notification_body_default', 'mod_bookit');
        force_current_language($oldlang);

        return self::replace_placeholders($template, $data);
    }

    /**
     * Resolve configured or shipped-default template text.
     *
     * @param stdClass $data
     * @param string $field
     * @param string $lang
     * @return string
     */
    private static function resolve_template_text(stdClass $data, string $field, string $lang): string {
        $statuskey = (string)$data->statuskey;
        $language = install_helper::normalize_booking_status_notification_language($lang);
        $configured = trim((string)get_config(
            'mod_bookit',
            install_helper::get_booking_status_notification_template_config_key($statuskey, $field)
        ));
        if ($configured !== '') {
            return $configured;
        }

        return $field === 'subject'
            ? install_helper::get_booking_status_notification_default_subject($statuskey, $language)
            : install_helper::get_booking_status_notification_default_body($statuskey, $language);
    }

    /**
     * Refresh language-sensitive template fields in the current language context.
     *
     * @param stdClass $template
     * @param string|null $lang
     * @return stdClass
     */
    private static function localize_template_data(stdClass $template, ?string $lang = null): stdClass {
        $oldlang = null;
        if ($lang !== null) {
            $oldlang = force_current_language(install_helper::normalize_booking_status_notification_language($lang));
        }

        $localized = clone $template;
        $localized->bookingstatus = event_manager::get_booking_status_label((int)$template->newstatus);
        $localized->oldbookingstatus = event_manager::get_booking_status_label((int)$template->oldstatus);
        $localized->bookingdate = userdate((int)($template->starttimestamp ?? 0), get_string('strftimedate', 'langconfig'));
        $localized->starttime = userdate((int)($template->starttimestamp ?? 0));
        $localized->endtime = userdate((int)($template->endtimestamp ?? 0));

        if ($lang !== null) {
            force_current_language($oldlang);
        }

        return $localized;
    }

    /**
     * Resolve a user name by ID.
     *
     * @param int $userid
     * @return string
     */
    private static function get_user_name(int $userid): string {
        if ($userid <= 0) {
            return '';
        }

        $user = core_user::get_user($userid);
        return $user ? fullname($user) : '';
    }

    /**
     * Resolve a comma-separated user list to names.
     *
     * @param string $ids
     * @return string
     */
    private static function get_user_names_csv(string $ids): string {
        $names = [];
        foreach (explode(',', $ids) as $rawid) {
            $id = (int)trim($rawid);
            if ($id > 0) {
                $name = self::get_user_name($id);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return implode(', ', array_unique($names));
    }

    /**
     * Resolve the room label for the notification.
     *
     * @param int $roomid
     * @return string
     */
    private static function get_room_name(int $roomid): string {
        global $DB;

        if ($roomid <= 0) {
            return '';
        }

        $room = $DB->get_record('bookit_room', ['id' => $roomid], 'name', IGNORE_MISSING);
        return $room->name ?? '';
    }

    /**
     * Resolve the course ID for a given cmid.
     *
     * @param int $cmid
     * @return int
     */
    private static function get_course_id(int $cmid): int {
        global $DB;

        $cm = $DB->get_record('course_modules', ['id' => $cmid], 'course', IGNORE_MISSING);
        return $cm ? (int)$cm->course : 0;
    }
}
