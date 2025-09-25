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

$string['addbooking'] = 'Request booking';
$string['afterexam'] = 'After exam';
$string['before_due'] = 'Before due';
$string['before_due_date'] = 'Before due date';
$string['beforeexam'] = 'Before exam';
$string['bookit:addevent'] = 'Add an event';
$string['bookit:addinstance'] = 'Add BookIt instance';
$string['bookit:addresource'] = 'Add a resource';
$string['bookit:editevent'] = 'Edit an event';
$string['bookit:editinternal'] = 'Edit an internal field';
$string['bookit:editresource'] = 'Edit a resource';
$string['bookit:managemasterchecklist'] = 'View and edit the master checklist.';
$string['bookit:view'] = 'View BookIt instance';
$string['bookit:viewalldetailsofevent'] = 'View all details of event';
$string['bookit:viewalldetailsofownevent'] = 'View all details of own event';
$string['bookitfieldset'] = 'PLACEHOLDER';
$string['category_name'] = 'Category name';
$string['checklistcategory'] = 'Checklist category';
$string['checklistcategory_help'] = 'The checklist category which the checklist item belongs to. New items will be appended to the category and can be moved afterwards.';
$string['checklistcategorydeleted'] = 'Checklist category deleted successfully.';
$string['checklistcategorysuccess'] = 'Checklist category created successfully.';
$string['checklistcategoryupdatesuccess'] = 'Checklist category updated successfully.';
$string['checklistitem'] = 'Checklist item';
$string['checklistitemdeleted'] = 'Checklist item deleted successfully.';
$string['checklistitemname'] = 'Checklist item name';
$string['checklistitemname_help'] = 'The text content of the checklist item which will be displayed on the checklist.';
$string['checklistitemsuccess'] = 'Checklist item created successfully.';
$string['checklistitemupdatesuccess'] = 'Checklist item updated successfully.';
$string['customtemplate'] = 'Message';
$string['customtemplate_help'] = 'The custom message template for the notification. ';
$string['customtemplatedefaultmessage'] = 'Lorem ipsum dolor sit amet ###RECIPIENT###,'
.'<p>Consectetur adipiscing elit. ###CHECKLISTCATEGORY### ullamcorper etiam sit. ###CHECKLISTITEM### vulputate '
.'velit esse. ###ITEMDUETIME### suscipit in posuere. ###ITEMSTATUS### mollis dolor.</p>'
.'<p>Non ###SEMESTERTERM###, commodo luctus ###EVENTTITLE###. Elit libero, ###DEPARTMENT### euismod ###ROOM### '
.'semper. ###EVENTSTART### quis blandit turpis. ###EVENTDURATION### risus auctor, ###TOTALDURATION### in.</p>'
.'<p>Curabitur blandit tempus ###COURSETEMPLATE###, sollicitudin ###PERSONINCHARGE###. Nullam quis risus eget '
.'###OTHEREXAMINERS### congue leo. ###NUMBEROFPARTICIPANTS### sagittis ###BOOKINGPERSON### integer ###BOOKINGSTATUS###.</p>'
.'<p>Nulla vitae elit libero,<br>'
.'Cras justo odio.</p>';
$string['customtemplatedefaultmessage_before_due'] = 'Lorem ipsum ante ###RECIPIENT###,'
.'<p>Consectetur adipiscing elit. ###CHECKLISTCATEGORY### vitae cursus ###CHECKLISTITEM### consequat '
.'magna. ###ITEMDUETIME### pellentesque habitant morbi. ###ITEMSTATUS### tristique senectus netus.</p>'
.'<p>Mauris ###SEMESTERTERM### eleifend ###EVENTTITLE###. Sed ###DEPARTMENT### fermentum ###ROOM### '
.'tempor. ###EVENTSTART### blandit aliquam etiam. ###EVENTDURATION### enim facilisis ###TOTALDURATION### gravida.</p>'
.'<p>Ultricies integer ###COURSETEMPLATE###, quis ###PERSONINCHARGE###. Vivamus ###OTHEREXAMINERS### '
.'arcu felis ###NUMBEROFPARTICIPANTS### bibendum ###BOOKINGPERSON### ut ###BOOKINGSTATUS### placerat.</p>'
.'<p>Ante tempus imperdiet,<br>'
.'Duis autem vel.</p>';
$string['customtemplatedefaultmessage_when_due'] = 'Lorem ipsum hodie ###RECIPIENT###,'
.'<p>Pellentesque habitant ###CHECKLISTCATEGORY### morbi tristique ###CHECKLISTITEM### senectus et '
.'netus. ###ITEMDUETIME### malesuada fames ac. ###ITEMSTATUS### turpis egestas pretium.</p>'
.'<p>Aenean ###SEMESTERTERM### euismod ###EVENTTITLE###. Elementum ###DEPARTMENT### tempus ###ROOM### '
.'egestas. ###EVENTSTART### sed viverra tellus. ###EVENTDURATION### in hac ###TOTALDURATION### habitasse.</p>'
.'<p>Platea dictumst ###COURSETEMPLATE### vestibulum ###PERSONINCHARGE###. Rhoncus ###OTHEREXAMINERS### '
.'mattis rhoncus ###NUMBEROFPARTICIPANTS### urna ###BOOKINGPERSON### neque ###BOOKINGSTATUS### viverra.</p>'
.'<p>Justo nec ultrices,<br>'
.'Dui sapien eget.</p>';
$string['customtemplatedefaultmessage_overdue'] = 'Lorem ipsum serius ###RECIPIENT###,'
.'<p>Gravida quis ###CHECKLISTCATEGORY### blandit turpis ###CHECKLISTITEM### cursus in '
.'hac. ###ITEMDUETIME### habitasse platea dictumst. ###ITEMSTATUS### vestibulum rhoncus est.</p>'
.'<p>Pellentesque ###SEMESTERTERM### eu ###EVENTTITLE###. Tincidunt ###DEPARTMENT### praesent ###ROOM### '
.'semper. ###EVENTSTART### feugiat nisl pretium. ###EVENTDURATION### fusce ut ###TOTALDURATION### placerat.</p>'
.'<p>Orci eu ###COURSETEMPLATE### lobortis ###PERSONINCHARGE###. Elementum ###OTHEREXAMINERS### '
.'pulvinar etiam ###NUMBEROFPARTICIPANTS### non ###BOOKINGPERSON### enim ###BOOKINGSTATUS### praesent.</p>'
.'<p>Elementum curabitur vitae,<br>'
.'Nunc congue nisi.</p>';
$string['customtemplatedefaultmessage_when_done'] = 'Lorem ipsum factum ###RECIPIENT###,'
.'<p>Faucibus ornare ###CHECKLISTCATEGORY### suspendisse ###CHECKLISTITEM### sed nisi '
.'lacus. ###ITEMDUETIME### sed viverra ipsum. ###ITEMSTATUS### nunc aliquet bibendum.</p>'
.'<p>Enim ###SEMESTERTERM### neque ###EVENTTITLE###. Volutpat ###DEPARTMENT### consequat ###ROOM### '
.'mauris. ###EVENTSTART### nunc congue nisi. ###EVENTDURATION### vitae ###TOTALDURATION### suscipit tellus.</p>'
.'<p>Mauris ###COURSETEMPLATE### augue ###PERSONINCHARGE###. Interdum ###OTHEREXAMINERS### '
.'et malesuada ###NUMBEROFPARTICIPANTS### fames ###BOOKINGPERSON### ac ###BOOKINGSTATUS### turpis.</p>'
.'<p>Egestas congue quisque,<br>'
.'Egestas diam in.</p>';
$string['duedate'] = 'Due date';
$string['duedate_help'] = "The due date for the completion of the checklist item. Must be one of 'none', 'before' or 'after' the exam. An offset in days must be set if the options 'before' or 'after' are selected.";
$string['edit'] = 'Edit';
$string['edit_event'] = "Edit event";
$string['event_bookingstatus'] = 'Booking status';
$string['event_bookingstatus_list'] = 'New, In progress, Accepted, Canceled, Rejeced';
$string['event_compensationfordisadvantages'] = 'Other compensations of disadvantages';
$string['event_compensationfordisadvantages_help'] = 'Enter here information on compensation of disadvantages.';
$string['event_department'] = 'Department';
$string['event_department_help'] = 'Enter your department.';
$string['event_duration'] = 'Duration (in minutes)';
$string['event_duration_help'] = 'Enter the duration of the event.';
$string['event_error_mintime'] = 'You cannot enter events in the past.';
$string['event_extratime_description'] = '<i>Note that an extra time of {$a} minutes is automatically added to each event to allow preparation and wrap-up works to be done.</i>';
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
$string['event_usermodified'] = 'Created by user';
$string['header_internal'] = 'Internal fields';
$string['instancename'] = 'Name';
$string['item_state_done'] = 'Done';
$string['item_state_open'] = 'Open';
$string['item_state_processing'] = 'Processing';
$string['item_state_unknown'] = 'Unknown';
$string['master_checklist'] = 'Master checklist';
$string['modulename'] = 'BookIt';
$string['modulename_help'] = 'BookIt is a tool to book services or items, e.g. exam dates, rooms and ressources.';
$string['modulenameplural'] = 'BookIt instances';
$string['new_checklistcategory'] = 'New checklist category';
$string['new_checklistitem'] = 'New checklist item';
$string['noduedate'] = 'No due date';
$string['notifications'] = 'Notifications';
$string['notification_time'] = 'Time';
$string['notification_time_help'] = 'The time offset in days in relation the exam when the notification should be sent.';
$string['overdue'] = 'Reminder when overdue';
$string['overdue_date'] = 'After overdue date';
$string['reset'] = 'Reset';
$string['please_select_and_enter'] = 'Please select or enter a number';
$string['pluginadministration'] = 'BookIt administration';
$string['pluginname'] = 'BookIt';
$string['recipient'] = 'Recipient';
$string['recipient_help'] = 'The recipient of the notification.';
$string['resetmessagetoconfirm'] = 'Are you sure you want to reset the message to the default template? Your changes will be deleted.';
$string['resource_amount'] = 'Amount';
$string['responsibility'] = 'Responsibility';
$string['role'] = 'Role';
$string['role_help'] = 'These roles will be assigned to the checklist item and will be responsible for the execution. Multiple roles can be selected by holding CTRL.';
$string['room'] = 'Room';
$string['rooms'] = 'Rooms';
$string['rooms_help'] = 'These rooms will be assigned to the checklist item. Multiple rooms may be selected by holding CTRL.';
$string['runinstallhelper'] = 'Run install helper';
$string['runinstallhelperinfo'] = 'If you have just installed the plugin, you can run the install helper once to create default BookIt roles and example checklist data. Otherwise you need to import the roles manually from the provided files in the plugin directory.';
$string['select_coursetemplate'] = 'Select a course template';
$string['select_coursetemplate_help'] = 'Select a course template for the course in which your exam will take place.';
$string['select_semester'] = 'Term';
$string['select_semester_help'] = 'Select term of event.';
$string['settings_eventmaxyears'] = 'Maxmum year to select for event';
$string['settings_eventmaxyears_desc'] = 'Set the maxmum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventminyears'] = 'Minimum year to select for event';
$string['settings_eventminyears_desc'] = 'Set the minimum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_extratime'] = 'Extra time for event';
$string['settings_extratime_desc'] = 'Extra time which will be added automatically to each event to allow preparation and wrap-up works to be done.';
$string['settings_roomcolor'] = 'Color for room {$a}';
$string['settings_roomcolor_desc'] = 'Select a color to be used for the calendar view.';
$string['settings_roomcolor_wcagcheck'] = 'Color contrast check for room {$a}';
$string['settings_roomcolor_wcagcheck_desc'] = 'Contrast check for color <i>#{$a->bcolor}</i> and text <i>#{$a->fcolor}</i>: ';
$string['settings_roomcolorheading'] = 'Room colors';
$string['settings_textcolor'] = 'Event text color';
$string['settings_textcolor_desc'] = 'Set the text color of the event in the calendar view.';
$string['sort'] = 'Sort';
$string['summer_semester'] = 'Summer Term';
$string['time'] = 'Time';
$string['time_help'] = "If the due date for the checklist item is 'before' or 'after', this setting defines how many days before or after the exam the checklist item should be completed.";
$string['when_done'] = 'When done';
$string['when_due'] = 'When due';
$string['winter_semester'] = 'Winter Term';
