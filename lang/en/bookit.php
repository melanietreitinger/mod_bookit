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

$string['active_weekplan'] = 'Active weekplan';
$string['add_blocker'] = 'Add blocker';
$string['afterexam'] = 'After event';
$string['allfaculties'] = 'All faculties';
$string['allrooms'] = 'All rooms';
$string['allstatuses'] = 'All statuses';
$string['back_to_event'] = 'Back to Event';
$string['back_to_overview'] = 'Back to Overview';
$string['backtooverview'] = 'Back to overview';
$string['basic'] = 'Basic';
$string['before_due'] = 'Before due';
$string['before_due_date'] = 'Before due date';
$string['before_event'] = 'Before event';
$string['booking:resource_amount'] = 'Amount';
$string['booking:resource_amount_invalid'] = 'Requested amount ({$a->requested}) exceeds available amount ({$a->available})';
$string['booking:resource_amount_too_low'] = 'Amount must be at least 1';
$string['booking:resource_max'] = 'Max: {$a}';
$string['booking:resource_room_conflict'] = 'The selected room does not provide all booked resources. Use the room overview to get an overview of rooms and their available resources.';
$string['booking:resource_selected'] = 'Selected';
$string['booking:resource_unavailable'] = 'Not available in selected room';
$string['booking:resources_booked'] = 'Booked Resources';
$string['booking:resources_header'] = 'Resources';
$string['booking:resources_info'] = 'Select the resources you need for this booking.';
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
$string['bookitfieldset'] = 'PLACEHOLDER';
$string['calendar'] = 'Calendar';
$string['calendar_addbooking'] = 'Request booking';
$string['calendar_desc'] = 'General calendar & booking behaviour';
$string['calendar_editevent'] = "Edit event";
$string['calendar_eventlist'] = 'List';
$string['category_collapseexpand'] = 'Collapse/Expand';
$string['category_created'] = 'Category created successfully';
$string['category_deleted'] = 'Category deleted successfully';
$string['category_name'] = 'Category name';
$string['category_name_required'] = 'Category name is required.';
$string['category_updated'] = 'Category updated successfully';
$string['checklist'] = 'Checklist';
$string['checklist_desc'] = 'Optional checklist / roles extension';
$string['checklist_duedate_days_after'] = '{$a} days after event';
$string['checklist_duedate_days_before'] = '{$a} days before event';
$string['checklist_placeholder'] = 'This section is reserved for the optional BookIt-Checklist add-on.';
$string['checklistcategory'] = 'Checklist category';
$string['checklistcategory_help'] = 'The checklist category which the checklist item belongs to. New items will be appended to the category and can be moved afterwards.';
$string['checklistcategorydeleted'] = 'Checklist category deleted successfully.';
$string['checklistcategorysuccess'] = 'Checklist category created successfully.';
$string['checklistcategoryupdatesuccess'] = 'Checklist category updated successfully.';
$string['checklistitem'] = 'Checklist item';
$string['checklistitemdeleted'] = 'Checklist item deleted successfully.';
$string['checklistitemname'] = 'Checklist item name';
$string['checklistitemname_help'] = 'The text content of the checklist item which will be displayed on the checklist.';
$string['checklistitemnotfound'] = 'Checklist item not found';
$string['checklistitemsuccess'] = 'Checklist item created successfully.';
$string['checklistitemupdatesuccess'] = 'Checklist item updated successfully.';
$string['chooseevent'] = 'Please select at least one event.';
$string['color'] = 'Color';
$string['could_not_parse_line'] = 'Could not parse line.';
$string['could_not_parse_time_period_x'] = 'Could not parse time period "{$a}".';
$string['csv_format'] = 'CSV (Comma Separated Values)';
$string['csvfile'] = 'CSV file';
$string['csvfile_help'] = 'Select a CSV file with checklist data to import. The file should contain columns: category_name, item_name, item_description, order_index.';
$string['customtemplate'] = 'Message';
$string['customtemplate_help'] = 'The custom message template for the notification. ';
$string['customtemplatedefaultmessage'] = 'Lorem ipsum dolor sit amet ###RECIPIENT###,'
. '<p>Consectetur adipiscing elit. ###CHECKLISTCATEGORY### ullamcorper etiam sit. ###CHECKLISTITEM### vulputate '
. 'velit esse. ###ITEMDUETIME### suscipit in posuere. ###ITEMSTATUS### mollis dolor.</p>'
. '<p>Non ###SEMESTERTERM###, commodo luctus ###EVENTTITLE###. Elit libero, ###DEPARTMENT### euismod ###ROOM### '
. 'semper. ###EVENTSTART### quis blandit turpis. ###EVENTDURATION### risus auctor, ###TOTALDURATION### in.</p>'
. '<p>Curabitur blandit tempus ###COURSETEMPLATE###, sollicitudin ###PERSONINCHARGE###. Nullam quis risus eget '
. '###OTHEREXAMINERS### congue leo. ###NUMBEROFPARTICIPANTS### sagittis ###BOOKINGPERSON### integer ###BOOKINGSTATUS###.</p>'
. '<p>Nulla vitae elit libero,<br>'
. 'Cras justo odio.</p>';
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
$string['define_institutions'] = 'Define institutions';
$string['did_not_begin_with_weekday'] = "Did not begin with weekday abbreviation";
$string['duedate'] = 'Due date';
$string['duedate_after_event'] = 'After event';
$string['duedate_before_event'] = 'Before event';
$string['duedate_days'] = 'Days';
$string['duedate_fixed'] = 'Due date';
$string['duedate_fixed_date'] = 'Fixed date';
$string['duedate_help'] = "The due date for the completion of the checklist item. Must be one of 'none', 'before' or 'after' the exam. An offset in days must be set if the options 'before' or 'after' are selected.";
$string['duedatetype'] = 'Due date type';
$string['duedatetype_help'] = 'How the due date is calculated relative to the event.';
$string['edit'] = 'Edit';
$string['edit_blocker'] = 'Edit blocker';
$string['edit_institution'] = 'Edit institution';
$string['edit_room'] = 'Edit room';
$string['edit_room_data'] = 'Edit room data';
$string['edit_weekplan'] = 'Edit week plan';
$string['edit_weekplan_assignment'] = 'Edit weekplan assignment';
$string['editchecklistitem'] = 'Edit Checklist Item';
$string['end'] = 'End';
$string['end_before_start'] = 'The end date has to be after the start date!';
$string['end_before_start_in_timeperiod_x'] = 'The end time was before the start time in time period "{$a}".';
$string['end_of_period'] = 'End of period';
$string['endtime'] = 'End time';
$string['error:resource_not_found'] = 'Resource not found';
$string['error_amount_required'] = 'Amount is required when not marked as amount irrelevant.';
$string['error_category_name_exists'] = 'A category with this name already exists. Please choose a different name.';
$string['error_category_not_found'] = 'The selected category does not exist.';
$string['error_duedate_days_required'] = 'Number of days is required for before/after event due dates.';
$string['error_duedate_required'] = 'Due date is required when fixed date type is selected.';
$string['error_sortorder_negative'] = 'Sort order must be a positive number.';
$string['error_starttime_in_past'] = 'Start time cannot be in the past.';
$string['event_bookingstatus'] = 'Booking status';
$string['event_bookingstatus_0'] = 'New';
$string['event_bookingstatus_1'] = 'In Progress';
$string['event_bookingstatus_2'] = 'Accepted';
$string['event_bookingstatus_3'] = 'Canceled';
$string['event_bookingstatus_4'] = 'Rejected';
$string['event_bookingstatus_help'] = 'Explanation of the booking status options.';
$string['event_bookingstatus_list'] = 'New, In progress, Accepted, Canceled, Rejected';
$string['event_checklist:done'] = 'done';

$string["cancelattemptinfo:view"] ="Cancel Attempt"; //TODO: einsortieren

$string['event_checklist:go_to_resources'] = 'Event Resources';
$string['event_checklist:progress'] = 'Checklist Progress';
$string['event_checklist_heading'] = 'Checklist for Event: {$a}';
$string['event_checklist_no_items'] = 'No checklist items are available for this event.';
$string['event_checklist_title'] = 'Event Checklist';
$string['event_compensationfordisadvantages'] = 'Other compensations of disadvantages';
$string['event_compensationfordisadvantages_help'] = 'Enter here information on compensation of disadvantages.';
$string['event_department'] = 'Department';
$string['event_department_help'] = 'Enter your department.';
$string['event_details'] = 'Event Details';
$string['event_duration'] = 'Duration (in minutes)';
$string['event_duration_help'] = 'Enter the duration of the event.';
$string['event_error_mintime'] = 'You cannot enter events in the past.';
$string['event_extratime_description'] = '<i>Note that extra time before and after the event is automatically added to allow preparation and wrap-up works to be done.</i>';
$string['event_extratime_label'] = '<i>Extra time for the event</i>';
$string['event_internalnotes'] = 'Internal notes';
$string['event_internalnotes_help'] = 'These notes are just for internal use and not shown to the booking person.';
$string['event_name'] = 'Event title';
$string['event_name_help'] = 'Enter the title of the event.';
$string['event_notes'] = 'Notes';
$string['event_notes_help'] = 'Please enter additional notes to inform your support team.';
$string['event_otherexaminers'] = 'Other examiners of this exam';
$string['event_otherexaminers_help'] = 'Enter other examiners of this exam.';
$string['event_personincharge'] = 'Person in charge of this exam';
$string['event_personincharge_help'] = 'Enter person in charge of this exam.';
$string['event_refcourseid'] = 'Exam course';
$string['event_refcourseid_help'] = 'Exam course associated with this exam';
$string['event_reserved'] = 'Reserved';
$string['event_resources'] = 'Resources';
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
$string['event_start'] = 'Event start';
$string['event_start_help'] = 'Please enter here the start date and time for your event.';
$string['event_students'] = 'Amount of participants';
$string['event_students_help'] = 'Enter amount of participants as a number.';
$string['event_supportperson'] = 'Support persons';
$string['event_supportperson_help'] = 'Support persons assigned to this event.';
$string['event_timecompensation'] = 'Time compensation';
$string['event_timecompensation_help'] = 'Check if you have participants entitled to time compensation.';
$string['event_usercreated'] = 'Created by user';
$string['event_usermodified'] = 'Created by user';
$string['export'] = 'Export';
$string['export_error'] = 'Export failed. Please try again.';
$string['export_format'] = 'Export format';
$string['export_help'] = 'You can choose between two file formats for export. Use PDF if you want to view the list outside the system. Use CSV to create a backup file or transfer the checklist to another system.';
$string['export_success'] = 'Export completed successfully';
$string['exportedon'] = 'Exported on: {$a}';
$string['exportevents'] = 'Export events';
$string['exportfailed'] = 'Export failed: {$a}';
$string['filtercategories'] = 'Filtercategories';
$string['filters'] = 'Filters: ';
$string['filters:label'] = 'Filters:';
$string['filters:no_selection'] = 'No selection';
$string['filters:room_label'] = 'Room:';
$string['from_x_onwards'] = 'From {$a} onwards';
$string['global_blocker'] = 'Global blocker';
$string['globally'] = 'Globally';
$string['go'] = 'Apply';
$string['header_internal'] = 'Internal fields';
$string['import'] = 'Import';
$string['import_error'] = 'Import failed. Please try again.';
$string['import_help'] = 'Use a backup file of a checklist in CSV format for import. The checklist items and categories contained in the CSV file will be imported into your checklist and will be created below existing items.';
$string['import_rooms'] = 'Import rooms';
$string['import_rooms_desc'] = 'When checked, room assignments from the CSV will be imported and mapped to checklist items. When unchecked, items will have no room assignments.';
$string['import_success'] = 'Import completed successfully';
$string['importfailed'] = 'Import failed: {$a}';
$string['importsuccessful'] = 'Import successful: {$a} items imported';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Active';
$string['institution_active_help'] = 'If this institution will be available to select in new events.';
$string['institution_name'] = 'Institution name';
$string['institutions'] = 'Institutions';
$string['internalnotes'] = 'Internal notes';
$string['internalnotes_help'] = 'These notes are just for internal use and not shown to the booking person.';
$string['invalidchecklistitemid'] = 'Invalid checklist item ID';
$string['invalidcsvformat'] = 'Invalid CSV format. Please check the file structure.';
$string['invalideventid'] = 'Invalid event ID';
$string['invalidformat'] = 'Invalid export format specified';
$string['invalidweekday'] = 'This weekday is not allowed for booking.';
$string['item_created'] = 'Resource created successfully';
$string['item_deleted'] = 'Resource deleted successfully';
$string['item_state_done'] = 'Done';
$string['item_state_open'] = 'Open';
$string['item_state_processing'] = 'Processing';
$string['item_state_unknown'] = 'Unknown';
$string['item_updated'] = 'Resource updated successfully';
$string['legend'] = 'Legend';
$string['line_x'] = 'Line {$a}:';
$string['local_blocker'] = 'Local blocker (only for this room)';
$string['location'] = 'Location';
$string['master_checklist'] = 'Master checklist';
$string['missing_role'] = 'Missing Role';
$string['missingdata'] = 'Missing required data for import';
$string['modulename'] = 'BookIt';
$string['modulename_help'] = 'BookIt is a tool to book services or items, e.g. exam dates, rooms and ressources.';
$string['modulenameplural'] = 'BookIt instances';
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
$string['nocontent'] = 'No master checklist categories found. Create the first category!';
$string['noduedate'] = 'No due date';
$string['noevents'] = 'No events in current view.';
$string['nofileselected'] = 'No file selected for import';
$string['normal_slot'] = 'Normal slot';
$string['notification_time'] = 'Time';
$string['notification_time_help'] = 'The time offset in days in relation the exam when the notification should be sent.';
$string['notifications'] = 'Notifications';
$string['overdue'] = 'Reminder when overdue';
$string['overdue_date'] = 'After overdue date';
$string['overlapping_allow_all'] = 'Allow all overlapping events';
$string['overlapping_allow_none'] = 'Allow no overlaps';
$string['overlapping_mode'] = 'Should overlapping events be prevented?';
$string['overlapping_non_confirmed'] = 'Allow overlapping with non-confirmed events';
$string['overview'] = 'My booked events';
$string['overview_action_requires_confirmed_booking'] = 'Checklist and resources are available only after the booking has been confirmed.';
$string['overview_help'] = 'Shows every event for which you are listed as examiner.';
$string['overwrite_extratimeafter'] = 'Overwrite global extratimeafter setting?';
$string['overwrite_extratimebefore'] = 'Overwrite global extratimebefore setting?';
$string['pdf_format'] = 'PDF (Portable Document Format)';
$string['pdf_title'] = 'PDF Title';
$string['pdf_title_help'] = 'Enter a custom title for the PDF document. This title will appear in the header of the exported PDF file. If left empty, the master checklist name will be used as the default title.';
$string['period'] = 'Period';
$string['please_select_and_enter'] = 'Please select or enter a number';
$string['pluginadministration'] = 'BookIt administration';
$string['pluginname'] = 'BookIt';
$string['quantity'] = 'Quantity';
$string['recipient'] = 'Recipient';
$string['recipient_help'] = 'The recipient of the notification.';
$string['reset'] = 'Reset';
$string['resetmessagetoconfirm'] = 'Are you sure you want to reset the message to the default template? Your changes will be deleted.';
$string['resource'] = 'Resource';
$string['resource_amount'] = 'Amount';
$string['resources'] = 'Resources';
$string['resources:active'] = 'Active';
$string['resources:active_help'] = 'Only active resources are available for booking.';
$string['resources:add_category'] = 'Add Category';
$string['resources:add_resource'] = 'Add Resource';
$string['resources:add_resource_no_categories'] = 'Please create a category first';
$string['resources:all_rooms'] = 'All rooms';
$string['resources:amount'] = 'Amount';
$string['resources:amount_help'] = 'The number of available units of this resource.';
$string['resources:amount_irrelevant'] = 'Amount irrelevant';
$string['resources:amount_must_be_positive'] = 'Amount must be a positive number.';
$string['resources:amount_unlimited'] = 'Unlimited';
$string['resources:amountirrelevant'] = 'Amount irrelevant';
$string['resources:amountirrelevant_help'] = 'Check this if the resource does not have a specific quantity (e.g., WiFi, whiteboard).';
$string['resources:back_to_resources'] = 'Back to Resources';
$string['resources:category'] = 'Category';
$string['resources:category_has_resources'] = 'To delete this category, no resources must be assigned to it. Please reassign the resources to other categories or delete them first.';
$string['resources:category_help'] = 'Select the category for this resource.';
$string['resources:category_not_found'] = 'The selected resource category was not found.';
$string['resources:category_required'] = 'Resource category is required.';
$string['resources:confirm_delete_category'] = 'Are you sure you want to delete category "{$a}"? This will also delete all resources in this category.';
$string['resources:confirm_delete_resource'] = 'Are you sure you want to delete resource "{$a}"?';
$string['resources:customtemplatedefaultmessage_before_due'] = 'Dear ###RECIPIENT###, the resource ###ITEM### is due on ###DATE###. Please ensure it is prepared in time.';
$string['resources:customtemplatedefaultmessage_overdue'] = 'Dear ###RECIPIENT###, the resource ###ITEM### is overdue. Please take action immediately.';
$string['resources:customtemplatedefaultmessage_when_done'] = 'Dear ###RECIPIENT###, the resource ###ITEM### has been marked as done.';
$string['resources:customtemplatedefaultmessage_when_due'] = 'Dear ###RECIPIENT###, the resource ###ITEM### is due today. Please confirm its availability.';
$string['resources:delete_category'] = 'Delete Category';
$string['resources:delete_resource'] = 'Delete Resource';
$string['resources:description'] = 'Description';
$string['resources:description_help'] = 'Optional description for the resource or category.';
$string['resources:duedate'] = 'Due Date';
$string['resources:duedate_absolute'] = 'Absolute Date';
$string['resources:duedate_relative'] = 'Relative to Event';
$string['resources:duedate_type'] = 'Due Date Type';
$string['resources:edit_category'] = 'Edit Category';
$string['resources:edit_resource'] = 'Edit Resource';
$string['resources:filter_no_resources'] = 'Add a category and resources first.';
$string['resources:generate_checklist'] = 'Generate Checklist';
$string['resources:inactive'] = 'Inactive';
$string['resources:info'] = 'Resource information';
$string['resources:internalinfo'] = 'Internal Information';
$string['resources:internalinfo_help'] = 'Internal notes visible only to administrators.';
$string['resources:invalid_status'] = 'Invalid status value.';
$string['resources:name'] = 'Name';
$string['resources:name_help'] = 'The name of the resource or category.';
$string['resources:name_required'] = 'Resource name is required.';
$string['resources:no_categories'] = 'No categories yet';
$string['resources:no_items'] = 'No resources in this category';
$string['resources:no_resources'] = 'No resources in this category';
$string['resources:no_rooms'] = 'No rooms created yet.';
$string['resources:no_rooms_link'] = 'Create rooms';
$string['resources:notfound'] = 'Resource not found';
$string['resources:notification_before'] = 'Notification Before Due';
$string['resources:notification_done'] = 'Notification When Done';
$string['resources:notification_overdue'] = 'Notification When Overdue';
$string['resources:notification_status_changed_body'] = 'The status of resource "{$a->resourcename}" for event "{$a->eventname}" has been updated to: {$a->statuslabel}.';
$string['resources:notification_status_changed_subject'] = 'Resource status updated: {$a->eventname}';
$string['resources:notification_when'] = 'Notification When Due';
$string['resources:open_settings'] = 'Resource checklist settings';
$string['resources:overview'] = 'Resource Overview';
$string['resources:resource'] = 'Resource';
$string['resources:rooms'] = 'Rooms';
$string['resources:rooms_help'] = 'Select rooms where this resource is available. Leave empty if the resource is available in all rooms. Multiple rooms can be selected by holding CTRL.';
$string['resources:settings'] = 'Resource Settings';
$string['resources:settings_column'] = 'Settings';
$string['resources:settings_empty'] = 'No resource settings configured yet. Generate settings entries from existing resources.';
$string['resources:settings_generated'] = '{$a} checklist items generated successfully.';
$string['resources:settings_saved'] = 'Settings saved successfully.';
$string['resources:settings_sortorder_help'] = 'Display order in the resource settings (independent from resource overview).';
$string['resources:status'] = 'Resource Status';
$string['resources:status_confirmed'] = 'Confirmed';
$string['resources:status_inprogress'] = 'In Progress';
$string['resources:status_rejected'] = 'Rejected';
$string['resources:status_requested'] = 'Requested';
$string['resources:view_settings'] = 'Resource Checklist Settings';
$string['resources_desc'] = 'Rooms, resource colours & availability';
$string['responsibility'] = 'Responsibility';
$string['role'] = 'Role';
$string['role_help'] = 'These roles will be assigned to the checklist item and will be responsible for the execution. Multiple roles can be selected by holding CTRL.';
$string['room'] = 'Room';
$string['room_active'] = 'Active';
$string['room_active_help'] = 'If this room will be available to select in new events.';
$string['room_doesnt_have_enough_seats'] = 'The selected room doesn\'t have enough seats for your requested number of participants.';
$string['roommode'] = 'Room mode';
$string['roommode_free'] = 'Free selection inside slots';
$string['roommode_slots'] = 'Bookings can only start at beginnings of slots';
$string['roommode_top_to_bottom'] = 'Only fill days top to bottom';
$string['rooms'] = 'Rooms';
$string['rooms_help'] = 'These rooms will be assigned to the checklist item. Multiple rooms may be selected by holding CTRL.';
$string['runinstallhelper'] = 'Run install helper';
$string['runinstallhelperinfo'] = 'If you have just installed the plugin, you can run the install helper once to create default BookIt roles and example checklist data. Otherwise you need to import the roles manually from the provided files in the plugin directory.';
$string['search'] = 'Search';
$string['seats'] = 'Amount of seats';
$string['select_coursetemplate'] = 'Select a course template';
$string['select_coursetemplate_help'] = 'Select a course template for the course in which your exam will take place.';
$string['select_semester'] = 'Term';
$string['select_semester_help'] = 'Select term of event.';
$string['selectevents'] = 'Please tick the events to export:';
$string['settings_checklist'] = 'Checklist settings';
$string['settings_eventdefaultduration'] = 'Default duration of an event in minutes';
$string['settings_eventdurationstepwidth'] = 'The step width for the duration of an event in minutes';
$string['settings_eventmaxduration'] = 'Maximum duration of an event in minutes';
$string['settings_eventmaxyear'] = 'Maxmum year to select for event';
$string['settings_eventmaxyear_desc'] = 'Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventminyear'] = 'Minimum year to select for event';
$string['settings_eventminyear_desc'] = 'Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventstartstepwidth'] = 'The step width for the event start time in minutes';
$string['settings_extratime'] = 'Extra time for event';
$string['settings_extratime_after'] = 'Extra time after event in minutes';
$string['settings_extratime_after_desc'] = 'Extra time in minutes which will be added automatically after each event to allow preparation and wrap-up works to be done.';
$string['settings_extratime_before'] = 'Extra time before event in minutes';
$string['settings_extratime_before_desc'] = 'Extra time in minutes which will be added automatically before each event to allow preparation and wrap-up works to be done.';
$string['settings_extratime_desc'] = 'Extra time which will be added automatically to each event to allow preparation and wrap-up works to be done.';
$string['settings_general'] = 'General settings';
$string['settings_master_checklist'] = 'Configure Master checklist';
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
$string['settings_resourcdes'] = 'Resource settings';
$string['settings_roomcolor'] = 'Color for room {$a}';
$string['settings_roomcolor_desc'] = 'Select a color to be used for the calendar view.';
$string['settings_roomcolor_wcagcheck'] = 'Color contrast check for room {$a}';
$string['settings_roomcolor_wcagcheck_desc'] = 'Contrast check for color <i>#{$a->bcolor}</i> and text <i>#{$a->fcolor}</i>: ';
$string['settings_sortorder'] = 'Sort order';
$string['settings_textcolor'] = 'Event text color';
$string['settings_textcolor_desc'] = 'Set the text color of the event in the calendar view.';
$string['settings_weekdaysvisible'] = 'Weekdays shown in calendar';
$string['settings_weekdaysvisible_desc'] =
        'Choose which weekdays appear in the BookIt calendar and may be selected for events.
     <br><em>Default: Monday, Tuesday, Wednesday, Thursday, Friday</em><br>
     <span style="color:#b50000;">
         Please note that by hiding weekdays, events that have already been booked
         on those days will no longer be displayed.
     </span>';
$string['shortname'] = 'Shortname';
$string['sort'] = 'Sort';
$string['sortorder_must_be_positive'] = 'Sort order must be a positive number.';
$string['start'] = 'Start';
$string['start_of_period'] = 'Start of period';
$string['starttime'] = 'Start time';
$string['status'] = 'Status';
$string['summer_semester'] = 'Summer Term';
$string['time'] = 'Time';
$string['time_help'] = "If the due date for the checklist item is 'before' or 'after', this setting defines how many days before or after the exam the checklist item should be completed.";
$string['timeslots'] = 'Time slots';
$string['tools'] = 'Tools';
$string['weekplan'] = 'Weekplan';
$string['weekplan_assignment_overlaps'] = 'The entered period is overlapping an already existing weekplan assignment.';
$string['weekplan_assignments'] = 'Weekplan assignments';
$string['weekplan_help'] = 'The week plan defines the available time slots and room assignments for this semester.';
$string['weekplan_room'] = 'Weekplan assignments to rooms';
$string['weekplans'] = 'Week plans';
$string['when_done'] = 'When done';
$string['when_due'] = 'When due';
$string['winter_semester'] = 'Winter Term';
