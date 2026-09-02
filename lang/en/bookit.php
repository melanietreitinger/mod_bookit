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
 * Plugin strings are defined here.
 *
 * @package     mod_bookit
 * @category    string
 * @copyright   2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allfaculties'] = 'All faculties';
$string['allrooms'] = 'All rooms';
$string['allstatuses'] = 'All statuses';
$string['back_to_overview'] = 'Back to Overview';
$string['blocker_add'] = 'Add blocker';
$string['blocker_edit'] = 'Edit blocker';
$string['blocker_end'] = 'End';
$string['blocker_globally'] = 'Globally';
$string['blocker_start'] = 'Start';
$string['booking:resource_amount'] = 'Amount';
$string['booking:resource_amount_invalid'] = 'Requested amount ({$a->requested}) exceeds available amount ({$a->available})';
$string['booking:resource_amount_too_low'] = 'Amount must be at least 1';
$string['booking:resource_max'] = 'Max: {$a}';
$string['booking:resource_room_conflict'] = 'The selected room does not provide all booked resources. Use the room overview to get an overview of rooms and their available resources.';
$string['bookingstatus_action_reactivate'] = 'Reactivate as new request';
$string['bookingstatus_body_canceled'] = 'Message body for Canceled';
$string['bookingstatus_body_confirmed'] = 'Message body for Confirmed';
$string['bookingstatus_body_default_canceled'] = 'Thank you for your request "###EVENTNAME###" for ###BOOKINGDATE###. Unfortunately, we must decline the request due to the current circumstances.';
$string['bookingstatus_body_default_confirmed'] = 'Thank you for your request "###EVENTNAME###" for ###BOOKINGDATE###. We are pleased to confirm your booking.';
$string['bookingstatus_body_default_inprogress'] = 'Thank you for your request "###EVENTNAME###" for ###BOOKINGDATE###. Some resources are still being reviewed. You will be notified shortly.';
$string['bookingstatus_body_default_new'] = 'Thank you for your booking request "###EVENTNAME###" for ###BOOKINGDATE###. We have received your request and will review it shortly.';
$string['bookingstatus_body_default_rejected'] = 'Thank you for your request "###EVENTNAME###" for ###BOOKINGDATE###. Unfortunately, we must decline the request due to the current circumstances.';
$string['bookingstatus_body_desc'] = 'Supported placeholders include ###EVENTNAME### and ###BOOKINGDATE###. Additional supported placeholders: ###BOOKINGSTATUS###, ###OLDBOOKINGSTATUS###, ###EVENTURL###, ###ROOM###, ###STARTTIME###, ###ENDTIME###, ###BOOKINGPERSON###, ###PERSONINCHARGE###, ###OTHEREXAMINERS###.';
$string['bookingstatus_body_inprogress'] = 'Message body for In Progress';
$string['bookingstatus_body_new'] = 'Message body for New';
$string['bookingstatus_body_rejected'] = 'Message body for Rejected';
$string['bookingstatus_enabled_canceled'] = 'Send message for Canceled';
$string['bookingstatus_enabled_confirmed'] = 'Send message for Confirmed';
$string['bookingstatus_enabled_inprogress'] = 'Send message for In Progress';
$string['bookingstatus_enabled_new'] = 'Send message for New';
$string['bookingstatus_enabled_rejected'] = 'Send message for Rejected';
$string['bookingstatus_notification_body_default'] = 'The booking request "###EVENTNAME###" has been updated to "###BOOKINGSTATUS###".'
    . "\n\n"
    . 'Room: ###ROOM###'
    . "\n"
    . 'Start: ###STARTTIME###'
    . "\n"
    . 'End: ###ENDTIME###'
    . "\n"
    . 'Booking person: ###BOOKINGPERSON###'
    . "\n"
    . 'Person in charge: ###PERSONINCHARGE###'
    . "\n"
    . 'Other examiners: ###OTHEREXAMINERS###'
    . "\n\n"
    . 'Open booking: ###EVENTURL###';
$string['bookingstatus_notification_closing'] = "Kind regards,\nYour BookIt team";
$string['bookingstatus_notification_greeting'] = 'Hello,';
$string['bookingstatus_notifications_desc'] = 'Configure recipients and message templates for booking-status changes.';
$string['bookingstatus_notifications_heading'] = 'Booking status notifications';
$string['bookingstatus_notify_bookingperson'] = 'Notify booking person';
$string['bookingstatus_notify_bookingperson_desc'] = 'Send booking-status messages to the user who created the booking request.';
$string['bookingstatus_notify_otherexaminers'] = 'Notify other examiners';
$string['bookingstatus_notify_otherexaminers_desc'] = 'Send booking-status messages to all additional examiners assigned to the booking request.';
$string['bookingstatus_notify_personincharge'] = 'Notify person in charge';
$string['bookingstatus_notify_personincharge_desc'] = 'Send booking-status messages to the responsible examiner.';
$string['bookingstatus_notify_serviceteam'] = 'Notify service team';
$string['bookingstatus_notify_serviceteam_desc'] = 'Send booking-status messages to users who have the service-team role.';
$string['bookingstatus_service_addresses'] = 'Booking notification service addresses';
$string['bookingstatus_service_addresses_desc'] = 'Email addresses that should receive booking-status notifications in addition to the service-team users (comma, semicolon, or whitespace separated).';
$string['bookingstatus_service_recipient_firstname'] = 'University';
$string['bookingstatus_service_recipient_lastname'] = 'Service';
$string['bookingstatus_subject_canceled'] = 'Message subject for Canceled';
$string['bookingstatus_subject_confirmed'] = 'Message subject for Confirmed';
$string['bookingstatus_subject_default_canceled'] = 'Booking request canceled: ###EVENTNAME### on ###BOOKINGDATE###';
$string['bookingstatus_subject_default_confirmed'] = 'Booking request confirmed: ###EVENTNAME### on ###BOOKINGDATE###';
$string['bookingstatus_subject_default_inprogress'] = 'Booking request in review: ###EVENTNAME### on ###BOOKINGDATE###';
$string['bookingstatus_subject_default_new'] = 'Booking request received: ###EVENTNAME### on ###BOOKINGDATE###';
$string['bookingstatus_subject_default_rejected'] = 'Booking request rejected: ###EVENTNAME### on ###BOOKINGDATE###';
$string['bookingstatus_subject_desc'] = 'Supported placeholders: ###EVENTNAME###, ###BOOKINGDATE###, ###BOOKINGSTATUS###, ###OLDBOOKINGSTATUS###, ###EVENTURL###, ###ROOM###, ###STARTTIME###, ###ENDTIME###, ###BOOKINGPERSON###, ###PERSONINCHARGE###, ###OTHEREXAMINERS###.';
$string['bookingstatus_subject_inprogress'] = 'Message subject for In Progress';
$string['bookingstatus_subject_new'] = 'Message subject for New';
$string['bookingstatus_subject_rejected'] = 'Message subject for Rejected';
$string['bookit:addevent'] = 'Add an event';
$string['bookit:addinstance'] = 'Add BookIt instance';
$string['bookit:editevent'] = 'Edit an event';
$string['bookit:editinternal'] = 'Edit an internal field';
$string['bookit:filterstatus'] = 'Filter by Status';
$string['bookit:managebasics'] = 'Manage the basic BookIt settings.';
$string['bookit:managemasterchecklist'] = 'View and edit the master checklist.';
$string['bookit:view'] = 'View BookIt instance';
$string['bookit:viewalldetailsofevent'] = 'View all details of event';
$string['bookit:viewalldetailsofownevent'] = 'View all details of own event';
$string['bookit:viewownoverview'] = 'View own events overview';
$string['bookit:viewrestrictedobserver'] = 'View the restricted observer projection';
$string['calendar'] = 'Calendar';
$string['calendar_addbooking'] = 'Request booking';
$string['calendar_editevent'] = "Edit event";
$string['calendar_eventlist'] = 'List';
$string['calendar_optional_fields'] = 'Optional booking fields';
$string['calendar_optional_fields_desc'] = 'Select optional fields for the booking form.';
$string['calendar_profile_desc'] = 'Shared third-pass controls for semester range, examiner pool, and the optional booking fields that stay in scope for both requesters and service-team users.';
$string['calendar_profile_heading'] = 'Calendar booking profile';
$string['category_collapseexpand'] = 'Collapse/Expand';
$string['category_deleted'] = 'Category deleted successfully';
$string['category_name'] = 'Category name';
$string['category_name_required'] = 'Category name is required.';
$string['category_updated'] = 'Category updated successfully';
$string['checklist'] = 'Checklist';
$string['checklist_due_after_event'] = 'After event';
$string['checklist_due_before_event'] = 'Before event';
$string['checklist_due_noduedate'] = 'No due date';
$string['checklist_duedate'] = 'Due date';
$string['checklist_duedate_days_after'] = '{$a} days after event';
$string['checklist_duedate_days_before'] = '{$a} days before event';
$string['checklist_duedate_help'] = "The due date for the completion of the checklist item. Must be one of 'none', 'before' or 'after' the exam. An offset in days must be set if the options 'before' or 'after' are selected.";
$string['checklist_responsibility'] = 'Responsibility';
$string['checklistcategory'] = 'Checklist category';
$string['checklistcategory_help'] = 'The checklist category which the checklist item belongs to. New items will be appended to the category and can be moved afterwards.';
$string['checklistcategory_required'] = 'Checklist category is required.';
$string['checklistcategorydeleted'] = 'Checklist category deleted successfully.';
$string['checklistcategorysuccess'] = 'Checklist category created successfully.';
$string['checklistcategoryupdatesuccess'] = 'Checklist category updated successfully.';
$string['checklistduedate_required'] = 'Due date option is required.';
$string['checklistitem'] = 'Checklist item';
$string['checklistitemdeleted'] = 'Checklist item deleted successfully.';
$string['checklistitemname'] = 'Checklist item name';
$string['checklistitemname_help'] = 'The text content of the checklist item which will be displayed on the checklist.';
$string['checklistitemname_required'] = 'Checklist item name is required.';
$string['checklistitemnotfound'] = 'Checklist item not found';
$string['checklistitemsuccess'] = 'Checklist item created successfully.';
$string['checklistitemupdatesuccess'] = 'Checklist item updated successfully.';
$string['checklistrole_required'] = 'At least one role is required.';
$string['checklistrooms_required'] = 'At least one room is required.';
$string['chooseevent'] = 'Please select at least one event.';
$string['close_and_discard_changes'] = 'Close and discard changes';
$string['color'] = 'Color';
$string['could_not_parse_line'] = 'Could not parse line.';
$string['could_not_parse_time_period_x'] = 'Could not parse time period "{$a}".';
$string['csv_format'] = 'CSV (Comma Separated Values)';
$string['csvfile'] = 'CSV file';
$string['customtemplate'] = 'Message';
$string['customtemplate_help'] = 'The custom message template for the notification. ';
$string['customtemplatedefaultmessage_before_due'] = 'Lorem ipsum ante ###RECIPIENT###,'
. '<p>Consectetur adipiscing elit. ###CHECKLISTCATEGORY### vitae cursus ###CHECKLISTITEM### consequat '
. 'magna. ###ITEMDUETIME### pellentesque habitant morbi. ###ITEMSTATUS### tristique senectus netus.</p>'
. '<p>Mauris ###SEMESTERTERM### eleifend ###EVENTTITLE###. Sed ###DEPARTMENT### fermentum ###ROOM### '
. 'tempor. ###EVENTSTART### blandit aliquam etiam. ###EVENTDURATION### enim facilisis ###TOTALDURATION### gravida.</p>'
. '<p>Ultricies integer ###COURSETEMPLATE###, quis ###PERSONINCHARGE###. Vivamus ###OTHEREXAMINERS### '
. 'arcu felis ###NUMBEROFPARTICIPANTS### bibendum ###BOOKINGPERSON### ut ###BOOKINGSTATUS### placerat.</p>'
. '<p>Ante tempus imperdiet,<br>'
. 'Duis autem vel.</p>';
$string['customtemplatedefaultmessage_overdue'] = 'Lorem ipsum serius ###RECIPIENT###,'
. '<p>Gravida quis ###CHECKLISTCATEGORY### blandit turpis ###CHECKLISTITEM### cursus in '
. 'hac. ###ITEMDUETIME### habitasse platea dictumst. ###ITEMSTATUS### vestibulum rhoncus est.</p>'
. '<p>Pellentesque ###SEMESTERTERM### eu ###EVENTTITLE###. Tincidunt ###DEPARTMENT### praesent ###ROOM### '
. 'semper. ###EVENTSTART### feugiat nisl pretium. ###EVENTDURATION### fusce ut ###TOTALDURATION### placerat.</p>'
. '<p>Orci eu ###COURSETEMPLATE### lobortis ###PERSONINCHARGE###. Elementum ###OTHEREXAMINERS### '
. 'pulvinar etiam ###NUMBEROFPARTICIPANTS### non ###BOOKINGPERSON### enim ###BOOKINGSTATUS### praesent.</p>'
. '<p>Elementum curabitur vitae,<br>'
. 'Nunc congue nisi.</p>';
$string['customtemplatedefaultmessage_when_done'] = 'Lorem ipsum factum ###RECIPIENT###,'
. '<p>Faucibus ornare ###CHECKLISTCATEGORY### suspendisse ###CHECKLISTITEM### sed nisi '
. 'lacus. ###ITEMDUETIME### sed viverra ipsum. ###ITEMSTATUS### nunc aliquet bibendum.</p>'
. '<p>Enim ###SEMESTERTERM### neque ###EVENTTITLE###. Volutpat ###DEPARTMENT### consequat ###ROOM### '
. 'mauris. ###EVENTSTART### nunc congue nisi. ###EVENTDURATION### vitae ###TOTALDURATION### suscipit tellus.</p>'
. '<p>Mauris ###COURSETEMPLATE### augue ###PERSONINCHARGE###. Interdum ###OTHEREXAMINERS### '
. 'et malesuada ###NUMBEROFPARTICIPANTS### fames ###BOOKINGPERSON### ac ###BOOKINGSTATUS### turpis.</p>'
. '<p>Egestas congue quisque,<br>'
. 'Egestas diam in.</p>';
$string['customtemplatedefaultmessage_when_due'] = 'Lorem ipsum hodie ###RECIPIENT###,'
. '<p>Pellentesque habitant ###CHECKLISTCATEGORY### morbi tristique ###CHECKLISTITEM### senectus et '
. 'netus. ###ITEMDUETIME### malesuada fames ac. ###ITEMSTATUS### turpis egestas pretium.</p>'
. '<p>Aenean ###SEMESTERTERM### euismod ###EVENTTITLE###. Elementum ###DEPARTMENT### tempus ###ROOM### '
. 'egestas. ###EVENTSTART### sed viverra tellus. ###EVENTDURATION### in hac ###TOTALDURATION### habitasse.</p>'
. '<p>Platea dictumst ###COURSETEMPLATE### vestibulum ###PERSONINCHARGE###. Rhoncus ###OTHEREXAMINERS### '
. 'mattis rhoncus ###NUMBEROFPARTICIPANTS### urna ###BOOKINGPERSON### neque ###BOOKINGSTATUS### viverra.</p>'
. '<p>Justo nec ultrices,<br>'
. 'Dui sapien eget.</p>';
$string['duedate_after_event'] = 'After event';
$string['duedate_before_event'] = 'Before event';
$string['edit'] = 'Edit';
$string['end_before_start_in_timeperiod_x'] = 'The end time was before the start time in time period "{$a}".';
$string['error_amount_required'] = 'Amount is required when not marked as amount irrelevant.';
$string['error_category_name_exists'] = 'A category with this name already exists. Please choose a different name.';
$string['error_category_not_found'] = 'The selected category does not exist.';
$string['event_bookingstatus'] = 'Booking status';
$string['event_bookingstatus_0'] = 'New';
$string['event_bookingstatus_1'] = 'In Progress';
$string['event_bookingstatus_2'] = 'Confirmed';
$string['event_bookingstatus_3'] = 'Canceled';
$string['event_bookingstatus_4'] = 'Rejected';
$string['event_bookingstatus_help'] = 'Explanation of the booking status options.';
$string['event_cancel_only_notice'] = 'This request can no longer be edited freely. You may only cancel it.';
$string['event_checklist:done'] = 'done';
$string['event_checklist:go_to_resources'] = 'Event Resources';
$string['event_checklist:progress'] = 'Checklist Progress';
$string['event_checklist_heading'] = 'Checklist for Event: {$a}';
$string['event_checklist_no_items'] = 'No checklist items are available for this event.';
$string['event_checklist_title'] = 'Event Checklist';
$string['event_compensationfordisadvantages'] = 'Compensations of disadvantages';
$string['event_compensationfordisadvantages_help'] = 'Enter here information on compensation of disadvantages.';
$string['event_department'] = 'Department';
$string['event_department_help'] = 'Enter your department.';
$string['event_details'] = 'Event Details';
$string['event_duration'] = 'Duration (in minutes)';
$string['event_duration_help'] = 'Enter the duration of the event.';
$string['event_error_mintime'] = 'You cannot enter events in the past.';
$string['event_extratime'] = '<i>Extra time for the event</i>';
$string['event_extratime_description'] = '<i>Note that extra time before and after the event is automatically added to allow preparation and wrap-up works to be done.</i>';
$string['event_internalnotes'] = 'Internal notes';
$string['event_internalnotes_help'] = 'These notes are just for internal use and not shown to the booking person.';
$string['event_name'] = 'Event title';
$string['event_name_help'] = 'Enter the title of the event.';
$string['event_notes'] = 'Notes';
$string['event_notes_help'] = 'Please enter additional notes to inform your support team.';
$string['event_otherexaminers'] = 'Other examiners of this exam';
$string['event_otherexaminers_help'] = 'Enter other examiners of this exam.';
$string['event_past_participant_notice'] = 'This booking already started and can no longer be changed by participants.';
$string['event_personincharge'] = 'Person in charge of this exam';
$string['event_personincharge_help'] = 'Enter person in charge of this exam.';
$string['event_refcourseid'] = 'Exam course';
$string['event_refcourseid_help'] = 'Exam course associated with this exam';
$string['event_reserved'] = 'Reserved';
$string['event_resources:go_to_checklist'] = 'Event Checklist';
$string['event_resources_checklist:booked_amount'] = 'Booked';
$string['event_resources_checklist:confirmed'] = 'confirmed';
$string['event_resources_checklist:progress'] = 'Resource Status Progress';
$string['event_resources_checklist_heading'] = 'Resource Checklist for Event: {$a}';
$string['event_resources_checklist_no_resources'] = 'No resources are assigned to this event.';
$string['event_resources_checklist_title'] = 'Event Resource Checklist';
$string['event_resources_heading'] = 'Resources for Event: {$a}';
$string['event_resources_title'] = 'Event Resources';
$string['event_room'] = 'Room';
$string['event_room_help'] = 'Select the room for your event.';
$string['event_semester_summer'] = 'Summer Term';
$string['event_semester_winter'] = 'Winter Term';
$string['event_start'] = 'Event start';
$string['event_start_help'] = 'Please enter here the start date and time for your event.';
$string['event_starttime'] = 'Start time';
$string['event_students'] = 'Amount of participants';
$string['event_students_help'] = 'Enter the estimated number of participants.';
$string['event_supportperson'] = 'Support persons';
$string['event_supportperson_help'] = 'Support persons assigned to this event.';
$string['event_supportperson_internalnotes_notice'] = 'Support on site may edit Support persons and Internal notes; other internal fields are read-only.';
$string['event_totalparticipants'] = 'Total number of participants';
$string['event_totalparticipants_help'] = 'Enter the number of participants who actually attended. This internal field is available only to the Service Team.';
$string['event_totalparticipants_invalid'] = 'Enter a whole number greater than or equal to zero.';
$string['event_usercreated'] = 'Created by user';
$string['event_usermodified'] = 'Last modified by';
$string['eventaudit_booking_reactivated'] = 'Booking reactivated';
$string['eventaudit_booking_status_changed'] = 'Booking status changed';
$string['events'] = 'Events';
$string['examiner_display_unknown_user'] = 'Unknown user (ID {$a})';
$string['examiner_pool_invalid_assignment'] = 'The selected examiner is not part of the configured examiner pool.';
$string['examiner_pool_usernames'] = 'Examiner pool usernames';
$string['examiner_pool_usernames_desc'] = 'Optional comma-separated list of Moodle usernames that may be selected as person in charge or additional examiners. Leave empty to allow all active users.';
$string['export'] = 'Export';
$string['export_error'] = 'Export failed. Please try again.';
$string['export_format'] = 'Export format';
$string['export_help'] = 'You can choose between two file formats for export. Use PDF if you want to view the list outside the system. Use CSV to create a backup file or transfer the checklist to another system.';
$string['export_success'] = 'Export completed successfully';
$string['exportedon'] = 'Exported on: {$a}';
$string['exportevents'] = 'Export events';
$string['exportevents_from'] = 'From';
$string['exportevents_ics_bookingperson'] = 'Booking person';
$string['exportevents_ics_duration'] = 'Duration';
$string['exportevents_ics_faculty'] = 'Faculty';
$string['exportevents_ics_otherexaminers'] = 'Other examiners';
$string['exportevents_ics_participants'] = 'Participants';
$string['exportevents_ics_personincharge'] = 'Person in charge';
$string['exportevents_ics_requirements'] = 'Requirements';
$string['exportevents_ics_semester'] = 'Semester term';
$string['exportevents_reset_range'] = 'Reset range';
$string['exportevents_selectedcount'] = 'Selected: {$a}';
$string['exportevents_to'] = 'To';
$string['exportfailed'] = 'Export failed: {$a}';
$string['filtercategories'] = 'Filtercategories';
$string['filters'] = 'Filters: ';
$string['filters:room_label'] = 'Room:';
$string['from_x_onwards'] = 'From {$a} onwards';
$string['global_blocker'] = 'Global blocker';
$string['header_internal'] = 'Internal fields';
$string['history_action_canceled'] = 'Canceled';
$string['history_action_confirmed'] = 'Changed to Confirmed';
$string['history_action_created'] = 'Created';
$string['history_action_moved_to_in_progress'] = 'Moved to In Progress';
$string['history_action_reactivated'] = 'Reactivated as new request';
$string['history_action_rejected'] = 'Rejected';
$string['history_action_restored'] = 'Restored';
$string['history_action_self_canceled'] = 'Cancelled by requester';
$string['history_action_updated'] = 'Updated';
$string['import'] = 'Import';
$string['import_help'] = 'Use a backup file of a checklist in CSV format for import. The checklist items and categories contained in the CSV file will be imported into your checklist and will be created below existing items.';
$string['import_rooms'] = 'Import rooms';
$string['import_rooms_desc'] = 'When checked, room assignments from the CSV will be imported and mapped to checklist items. When unchecked, items will have no room assignments.';
$string['importfailed'] = 'Import failed: {$a}';
$string['importsuccessful'] = 'Import successful: {$a} items imported';
$string['installation_heading'] = 'Role presets';
$string['installation_heading_desc'] = 'Install the shipped BookIt roles or download the XML presets.';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Active';
$string['institution_active_help'] = 'If this institution will be available to select in new events.';
$string['institution_edit'] = 'Edit institution';
$string['institution_name'] = 'Institution name';
$string['institutionid_empty_notice'] = 'No active institutions are available. Run the install helper first.';
$string['institutions'] = 'Institutions';
$string['internalnotes'] = 'Internal notes';
$string['internalnotes_help'] = 'These notes are just for internal use and not shown to the booking person.';
$string['invalidchecklistitemid'] = 'Invalid checklist item ID';
$string['invalidcsvformat'] = 'Invalid CSV format. Please check the file structure.';
$string['invalidformat'] = 'Invalid export format specified';
$string['invalidweekday'] = 'This weekday is not allowed for booking.';
$string['item_created'] = 'Resource created successfully';
$string['item_deleted'] = 'Resource deleted successfully';
$string['item_updated'] = 'Resource updated successfully';
$string['legend'] = 'Legend';
$string['line_x'] = 'Line {$a}:';
$string['local_blocker'] = 'Local blocker (only for this room)';
$string['master_checklist'] = 'Master checklist';
$string['messageprovider:bookit_booking_status_changed'] = 'Notification of booking status changes';
$string['messageprovider:bookit_resource_status_changed'] = 'Notification of resource status changes';
$string['missing_role'] = 'Missing Role';
$string['missingdata'] = 'Missing required data for import';
$string['modulename'] = 'Calendar';
$string['modulename_help'] = 'Calendar is a tool to book services or items, e.g. exam dates, rooms and ressources.';
$string['modulenameplural'] = 'Calendar activities';
$string['n_seats'] = '{$a} seats';
$string['new_checklistcategory'] = 'New checklist category';
$string['new_checklistitem'] = 'New checklist item';
$string['new_institution'] = 'New institution';
$string['new_room'] = 'New room';
$string['new_weekplan'] = 'New week plan';
$string['new_weekplan_assignment'] = 'New weekplan assignment';
$string['no_categories_available'] = 'No categories available. Please create a category first.';
$string['no_selection'] = 'No selection';
$string['no_slot_available'] = '<span class="text-danger">No slot available for that day and room.</span>';
$string['no_weekplan_defined'] = '<span class="text-danger">No weekplan defined for that day and room.</span>';
$string['nobookitinstances'] = 'There are no BookIt instances in this course.';
$string['nocontent'] = 'No master checklist categories found. Create the first category!';
$string['noevents'] = 'No events in current view.';
$string['nofileselected'] = 'No file selected for import';
$string['normal_slot'] = 'Normal slot';
$string['notification_time'] = 'Time';
$string['notification_time_help'] = 'The time offset in days in relation the exam when the notification should be sent.';
$string['notifications'] = 'Notifications';
$string['observer_empty_state'] = 'No confirmed bookings are currently available for your role.';
$string['observer_export_denied'] = 'Event export is not available for your role.';
$string['observer_no_detail_access'] = 'Details are not available in this view.';
$string['optional_checklist_enabled'] = 'Enable checklist module';
$string['optional_checklist_enabled_desc'] = 'Allows administrators and service-team roles to use the checklist and master checklist parts. Leave disabled for calendar-only installations.';
$string['optional_part_disabled'] = 'This BookIt module part is currently disabled in General Settings.';
$string['optional_plugin_parts_desc'] = 'Fresh installs start in calendar-only mode. Enable resources or checklist here only when needed.';
$string['optional_plugin_parts_heading'] = 'Optional plugin parts';
$string['optional_resources_enabled'] = 'Enable resources module';
$string['optional_resources_enabled_desc'] = 'Allows administrators and service-team roles to use the resources catalog and event resource workflows. Leave disabled for calendar-only installations.';
$string['overview'] = 'My booked events';
$string['overview_all_events'] = 'All bookings';
$string['overview_all_requests'] = 'All requests';
$string['overview_apply_filters'] = 'Apply filters';
$string['overview_cancel_booking'] = 'Cancel booking';
$string['overview_cancel_booking_confirm'] = 'Cancel booking?';
$string['overview_cancel_booking_confirm_body'] = 'This will cancel your booking request. You can review it later in the history tab.';
$string['overview_cancel_column'] = 'Actions';
$string['overview_column_bookingstatus'] = 'Booking status';
$string['overview_column_checklist'] = 'Checklist';
$string['overview_column_date'] = 'Date';
$string['overview_column_datetime'] = 'Date and time';
$string['overview_column_id'] = 'ID';
$string['overview_column_myrole'] = 'My role';
$string['overview_column_personincharge'] = 'Person in charge';
$string['overview_column_progress'] = 'Progress';
$string['overview_column_resources'] = 'Resources';
$string['overview_column_room'] = 'Room';
$string['overview_column_title'] = 'Title';
$string['overview_confirmed_requests'] = 'Confirmed bookings';
$string['overview_confirmed_requests_empty'] = 'There are currently no confirmed bookings in this workspace.';
$string['overview_count'] = '{$a} events';
$string['overview_filter_all_faculties'] = 'All faculties';
$string['overview_filter_assignment'] = 'Assignment';
$string['overview_filter_assignment_all'] = 'All';
$string['overview_filter_assignment_assigned'] = 'Assigned to me';
$string['overview_filter_enddate'] = 'End date';
$string['overview_filter_faculty'] = 'Faculty';
$string['overview_filter_startdate'] = 'Start date';
$string['overview_filter_status'] = 'Booking status';
$string['overview_history'] = 'History';
$string['overview_my_events'] = 'My booked events';
$string['overview_nav_badge_inprogress'] = '{$a} in progress requests';
$string['overview_nav_badge_new'] = '{$a} new requests';
$string['overview_no_results'] = 'No bookings match the selected filters.';
$string['overview_open_requests'] = 'Open requests';
$string['overview_open_requests_empty'] = 'There are currently no open booking requests.';
$string['overview_reactivate_booking_confirm'] = 'Reactivate as new request?';
$string['overview_reactivate_booking_confirm_body'] = 'This will submit the booking again as a new request.';
$string['overview_reactivate_column'] = 'Reactivate';
$string['overview_rejected_cancelled_requests'] = 'Rejected and cancelled';
$string['overview_rejected_requests_empty'] = 'There are currently no rejected or booker-cancelled booking requests in the trash queue.';
$string['overview_request_workspace'] = 'Request workspace';
$string['overview_request_workspace_switch'] = 'Request queues';
$string['overview_reset_filters'] = 'Reset filters';
$string['overview_role_bookingperson'] = 'Booking person';
$string['overview_role_otherexaminer'] = 'Other examiner';
$string['overview_role_personincharge'] = 'Person in charge';
$string['overview_role_supportperson'] = 'Support person';
$string['overview_status_group_closed'] = 'Closed / not confirmed';
$string['overview_status_group_confirmed'] = 'Confirmed booking';
$string['overview_status_group_open'] = 'Open request';
$string['overview_workflow_history'] = 'Workflow history';
$string['overview_workflow_history_change'] = '{$a->field}: {$a->from} -> {$a->to}';
$string['overview_workflow_history_empty'] = 'No workflow entries recorded yet.';
$string['overview_workflow_history_recovery'] = 'Recovery uses the last valid workflow state.';
$string['pdf_format'] = 'PDF (Portable Document Format)';
$string['pdf_title'] = 'PDF Title';
$string['pdf_title_help'] = 'Enter a custom title for the PDF document. This title will appear in the header of the exported PDF file. If left empty, the master checklist name will be used as the default title.';
$string['period'] = 'Period';
$string['pluginadministration'] = 'BookIt administration';
$string['pluginname'] = 'BookIt';
$string['recipient'] = 'Recipient';
$string['recipient_help'] = 'The recipient of the notification.';
$string['reset'] = 'Reset';
$string['resetmessagetoconfirm'] = 'Are you sure you want to reset the message to the default template? Your changes will be deleted.';
$string['resource'] = 'Resource';
$string['resources'] = 'Resources';
$string['resources:active'] = 'Active';
$string['resources:active_help'] = 'Only active resources are available for booking.';
$string['resources:add_category'] = 'Add Category';
$string['resources:add_resource'] = 'Add Resource';
$string['resources:add_resource_no_categories'] = 'Please create a category first';
$string['resources:all_rooms'] = 'All rooms';
$string['resources:amount'] = 'Amount';
$string['resources:amount_help'] = 'The number of available units of this resource.';
$string['resources:amount_must_be_positive'] = 'Amount must be a positive number.';
$string['resources:amount_unlimited'] = 'Unlimited';
$string['resources:amountirrelevant'] = 'Amount irrelevant';
$string['resources:amountirrelevant_help'] = 'Check this if the resource does not have a specific quantity (e.g., WiFi, whiteboard).';
$string['resources:category'] = 'Category';
$string['resources:category_has_resources'] = 'To delete this category, no resources must be assigned to it. Please reassign the resources to other categories or delete them first.';
$string['resources:category_help'] = 'Select the category for this resource.';
$string['resources:category_not_found'] = 'The selected resource category was not found.';
$string['resources:category_required'] = 'Resource category is required.';
$string['resources:customtemplatedefaultmessage_before_due'] = 'Dear ###RECIPIENT###, the resource ###ITEM### is due on ###DATE###. Please ensure it is prepared in time.';
$string['resources:customtemplatedefaultmessage_overdue'] = 'Dear ###RECIPIENT###, the resource ###ITEM### is overdue. Please take action immediately.';
$string['resources:customtemplatedefaultmessage_when_done'] = 'Dear ###RECIPIENT###, the resource ###ITEM### has been marked as done.';
$string['resources:customtemplatedefaultmessage_when_due'] = 'Dear ###RECIPIENT###, the resource ###ITEM### is due today. Please confirm its availability.';
$string['resources:description'] = 'Description';
$string['resources:description_help'] = 'Optional description for the resource or category.';
$string['resources:edit_category'] = 'Edit Category';
$string['resources:edit_resource'] = 'Edit Resource';
$string['resources:filter_no_resources'] = 'Add a category and resources first.';
$string['resources:info'] = 'Resource information';
$string['resources:internalinfo'] = 'Internal Information';
$string['resources:internalinfo_help'] = 'Internal notes visible only to administrators.';
$string['resources:invalid_status'] = 'Invalid status value.';
$string['resources:name'] = 'Name';
$string['resources:name_help'] = 'The name of the resource or category.';
$string['resources:name_required'] = 'Resource name is required.';
$string['resources:no_categories'] = 'No categories yet';
$string['resources:no_rooms'] = 'No rooms created yet.';
$string['resources:no_rooms_link'] = 'Create rooms';
$string['resources:notfound'] = 'Resource not found';
$string['resources:notification_status_changed_body'] = 'The status of resource "{$a->resourcename}" for event "{$a->eventname}" has been updated to: {$a->statuslabel}.';
$string['resources:notification_status_changed_subject'] = 'Resource status updated: {$a->eventname}';
$string['resources:open_settings'] = 'Resource checklist settings';
$string['resources:overview'] = 'Resource Overview';
$string['resources:resource'] = 'Resource';
$string['resources:rooms'] = 'Rooms';
$string['resources:rooms_help'] = 'Select rooms where this resource is available. Leave empty if the resource is available in all rooms. Multiple rooms can be selected by holding CTRL.';
$string['resources:settings_column'] = 'Settings';
$string['resources:settings_saved'] = 'Settings saved successfully.';
$string['resources:status'] = 'Resource Status';
$string['before_due'] = 'Before due';
$string['overdue'] = 'Reminder when overdue';
$string['when_done'] = 'When done';
$string['when_due'] = 'When due';
$string['role'] = 'Role';
$string['role_help'] = 'These roles will be assigned to the checklist item and will be responsible for the execution. Multiple roles can be selected by holding CTRL.';
$string['rolepreset_bookingperson'] = 'Booking Person';
$string['rolepreset_bookingperson_help'] = 'Booking Person: A person who makes a request for an e-examination (examiners, examination offices, student staff, academic staff, ...).';
$string['rolepreset_examiner'] = 'Examiner';
$string['rolepreset_examiner_help'] = 'Examiner: A person who belongs to or is associated with a university and examines students.';
$string['rolepreset_observer'] = 'Observer';
$string['rolepreset_observer_help'] = 'Observer: A person who can only view events (e.g. room management, examination office, deanship, service team of another university).';
$string['rolepreset_serviceteam'] = 'Service Team';
$string['rolepreset_serviceteam_help'] = 'Service Team: A person who works in the e-assessment service and manages exam bookings.';
$string['rolepreset_supportonsite'] = 'Support on Site';
$string['rolepreset_supportonsite_help'] = 'Support on Site: A person who supports an exam on site.';
$string['rolepresetdownloads'] = 'Role preset downloads';
$string['room'] = 'Room';
$string['room_active'] = 'Set room active';
$string['room_active_help'] = 'If this room will be available to select in new events.';
$string['room_color'] = 'Color';
$string['room_data_edit'] = 'Edit room data';
$string['room_doesnt_have_enough_seats'] = 'The selected room doesn\'t have enough seats for your requested number of participants.';
$string['room_edit'] = 'Edit room';
$string['room_location'] = 'Location';
$string['room_mode'] = 'Room mode';
$string['room_mode_free'] = 'Free selection inside slots';
$string['room_mode_slots'] = 'Bookings can only start at beginnings of slots';
$string['room_mode_top_to_bottom'] = 'Only fill days top to bottom';
$string['room_overlapping_allow_all'] = 'Allow all overlapping events';
$string['room_overlapping_allow_none'] = 'Allow no overlaps';
$string['room_overlapping_mode'] = 'Should overlapping events be prevented?';
$string['room_overlapping_non_confirmed'] = 'Allow overlapping with non-confirmed events';
$string['room_overwrite_extratimeafter'] = 'Overwrite global extratimeafter setting?';
$string['room_overwrite_extratimebefore'] = 'Overwrite global extratimebefore setting?';
$string['room_seats'] = 'Amount of seats';
$string['room_shortname'] = 'Shortname';
$string['room_shortname_help'] = 'Shortname of a room with max. 6 characters. This is used in the calendar view.';
$string['roomid_empty_notice'] = 'No active rooms are available. Run the install helper first.';
$string['rooms'] = 'Rooms';
$string['rooms_help'] = 'These rooms will be assigned to the checklist item. Multiple rooms may be selected by holding CTRL.';
$string['runinstallhelper'] = 'Run install helper';
$string['runinstallhelper_ready_state'] = 'Install helper already completed successfully.';
$string['runinstallhelper_result_failed'] = 'Install helper failed. Imported roles: {$a->rolesimported}; created baseline items: {$a->baselinecreated}; errors: {$a->errors}.';
$string['runinstallhelper_result_idempotent'] = 'Install helper verified the existing setup. Kept role presets: {$a->rolesskipped}; verified baseline items: {$a->baselineverified}.';
$string['runinstallhelper_result_partial'] = 'Install helper finished partially. Imported roles: {$a->rolesimported}; kept presets: {$a->rolesskipped}; created baseline items: {$a->baselinecreated}; verified baseline items: {$a->baselineverified}; errors: {$a->errors}.';
$string['runinstallhelper_result_success'] = 'Install helper completed successfully. Imported roles: {$a->rolesimported}; created baseline items: {$a->baselinecreated}.';
$string['runinstallhelperinfo'] = 'Creates the default calendar baseline and imports the shipped role presets if needed.';
$string['seats'] = 'Amount of seats';
$string['select_coursetemplate'] = 'Select a course template';
$string['select_coursetemplate_help'] = 'Select a course template for the course in which your exam will take place.';
$string['select_semester'] = 'Term';
$string['select_semester_help'] = 'Select term of event.';
$string['selectevents'] = 'Please tick the events to export:';
$string['semesterlookaheadyears'] = 'Years of future semesters';
$string['semesterlookaheadyears_desc'] = 'How many future calendar years should be offered in the semester selector.';
$string['semesterlookbackyears'] = 'Years of past semesters';
$string['semesterlookbackyears_desc'] = 'How many previous calendar years should be offered in the semester selector.';
$string['settings_eventdefaultduration'] = 'Default duration of an event in minutes';
$string['settings_eventdurationstepwidth'] = 'The step width for the duration of an event in minutes';
$string['settings_eventmaxduration'] = 'Maximum duration of an event in minutes';
$string['settings_eventmaxyear'] = 'Maxmum year to select for event';
$string['settings_eventmaxyear_desc'] = 'Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventminyear'] = 'Minimum year to select for event';
$string['settings_eventminyear_desc'] = 'Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventstartstepwidth'] = 'The step width for the event start time in minutes';
$string['settings_extratime_after'] = 'Extra time after event in minutes';
$string['settings_extratime_after_desc'] = 'Extra time in minutes which will be added automatically after each event to allow preparation and wrap-up works to be done.';
$string['settings_extratime_before'] = 'Extra time before event in minutes';
$string['settings_extratime_before_desc'] = 'Extra time in minutes which will be added automatically before each event to allow preparation and wrap-up works to be done.';
$string['settings_general'] = 'General settings';
$string['settings_master_checklist_desc'] = 'The Master checklist can be used to map workflows for an event. You can configure worklflow categories, workflow items, applicable rooms and the roles responsible for the item.
You can select a due date (optional) before or after the event and send notifications <i>Before due</i>, <i>When due</i>, <i>Reminder when overdue</i> and <i>When done</i>.';
$string['settings_overview'] = 'BookIt settings';
$string['settings_pdf_logo_custom'] = 'Custom PDF logo';
$string['settings_pdf_logo_custom_desc'] = 'Upload a custom logo to be used in PDF checklists when "Custom logo" is selected above. Supported formats: PNG, JPG, JPEG. Optimal width: 200-400px.';
$string['settings_pdf_logo_enable'] = 'Enable logo in PDF checklist';
$string['settings_pdf_logo_enable_desc'] = 'Show a logo in the header of exported PDF checklists.';
$string['settings_pdf_logo_source'] = 'Logo source';
$string['settings_pdf_logo_source_custom'] = 'Custom logo';
$string['settings_pdf_logo_source_desc'] = 'Choose the source for the logo displayed in PDF checklists.';
$string['settings_pdf_logo_source_site'] = 'Site logo (core_admin | logo)';
$string['settings_pdf_logo_source_theme'] = 'Theme logo (theme_boost_union | logo)';
$string['settings_weekdaysvisible'] = 'Weekdays shown in calendar';
$string['settings_weekdaysvisible_desc'] = 'Choose which weekdays appear in the BookIt calendar and may be selected for events.
     <br><em>Default: Monday, Tuesday, Wednesday, Thursday, Friday</em><br>
     <span style="color:#b50000;">
         Please note that by hiding weekdays, events that have already been booked
         on those days will no longer be displayed.
     </span>';
$string['sort'] = 'Sort';
$string['sortorder_must_be_positive'] = 'Sort order must be a positive number.';
$string['time'] = 'Time';
$string['time_help'] = "If the due date for the checklist item is 'before' or 'after', this setting defines how many days before or after the exam the checklist item should be completed.";
$string['tools'] = 'Tools';
$string['weekplan'] = 'Weekplan';
$string['weekplan_active'] = 'Active weekplan';
$string['weekplan_assignment_edit'] = 'Edit weekplan assignment';
$string['weekplan_assignment_overlaps'] = 'The entered period is overlapping an already existing weekplan assignment.';
$string['weekplan_assignments'] = 'Weekplan assignments';
$string['weekplan_did_not_begin_with_weekday'] = "Did not begin with weekday abbreviation";
$string['weekplan_edit'] = 'Edit week plan';
$string['weekplan_end_before_start'] = 'The end date has to be after the start date!';
$string['weekplan_end_of_period'] = 'End of period';
$string['weekplan_help'] = 'The week plan defines the available time slots and room assignments for this semester.';
$string['weekplan_start_of_period'] = 'Start of period';
$string['weekplans'] = 'Week plans';
