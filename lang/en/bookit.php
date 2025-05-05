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

$string['add_blocker'] = 'Add blocker';
$string['addbooking'] = 'Request booking';
$string['bookit:addevent'] = 'Add an event';
$string['bookit:addinstance'] = 'Add BookIt instance';
$string['bookit:addresource'] = 'Add a resource';
$string['bookit:editevent'] = 'Edit an event';
$string['bookit:editinternal'] = 'Edit an internal field';
$string['bookit:editresource'] = 'Edit a resource';
$string['bookit:view'] = 'View BookIt instance';
$string['bookit:viewalldetailsofevent'] = 'View all details of event';
$string['bookit:viewalldetailsofownevent'] = 'View all details of own event';
$string['bookitfieldset'] = 'PLACEHOLDER';
$string['category_name'] = 'Category name';
$string['checklistcategory'] = 'Checklist category';
$string['checklistcategorydeleted'] = 'Checklist category deleted successfully.';
$string['checklistcategorysuccess'] = 'Checklist category created successfully.';
$string['checklistcategoryupdatesuccess'] = 'Checklist category updated successfully.';
$string['checklistitem'] = 'Checklist item';
$string['checklistitemdeleted'] = 'Checklist item deleted successfully.';
$string['checklistitemname'] = 'Checklist item name';
$string['checklistitemsuccess'] = 'Checklist item created successfully.';
$string['checklistitemupdatesuccess'] = 'Checklist item updated successfully.';
$string['color'] = 'Color';
$string['customtemplate'] = 'Message';
$string['customtemplatedefaultmessage'] = 'Lorem ipsum dolor sit amet ###RECIPIENT###,'
. '<p>Consectetur adipiscing elit. ###CHECKLISTCATEGORY### ullamcorper etiam sit. ###CHECKLISTITEM### vulputate '
. 'velit esse. ###ITEMDUETIME### suscipit in posuere. ###ITEMSTATUS### mollis dolor.</p>'
. '<p>Non ###SEMESTERTERM###, commodo luctus ###EVENTTITLE###. Elit libero, ###DEPARTMENT### euismod ###ROOM### '
. 'semper. ###EVENTSTART### quis blandit turpis. ###EVENTDURATION### risus auctor, ###TOTALDURATION### in.</p>'
. '<p>Curabitur blandit tempus ###COURSETEMPLATE###, sollicitudin ###PERSONINCHARGE###. Nullam quis risus eget '
. '###OTHEREXAMINERS### congue leo. ###NUMBEROFPARTICIPANTS### sagittis ###BOOKINGPERSON### integer ###BOOKINGSTATUS###.</p>'
. '<p>Nulla vitae elit libero,<br>'
. 'Cras justo odio.</p>';
$string['define_institutions'] = 'Define institutions';
$string['edit'] = 'Edit';
$string['edit_blocker'] = 'Edit blocker';
$string['edit_event'] = "Edit event";
$string['edit_institution'] = 'Edit institution';
$string['edit_room'] = 'Edit room';
$string['edit_weekplan'] = 'Edit week plan';
$string['edit_weekplan_assignment'] = 'Edit weekplan assignment';
$string['end'] = 'End';
$string['end_before_start'] = 'The end date has to be after the start date!';
$string['end_of_period'] = 'End of period';
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
$string['general_settings'] = 'General Settings';
$string['globally'] = 'Globally';
$string['header_internal'] = 'Internal fields';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Active';
$string['institution_active_help'] = 'If this institution will be available to select in new events.';
$string['institution_name'] = 'Institution name';
$string['institutions'] = 'Institutions';
$string['internalnotes'] = 'Internal notes';
$string['internalnotes_help'] = 'These notes are just for internal use and not shown to the booking person.';
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
$string['new_institution'] = 'New institution';
$string['new_room'] = 'New room';
$string['new_timeslot'] = 'New time slot';
$string['new_weekplan'] = 'New week plan';
$string['new_weekplan_assignment'] = 'New weekplan assignment';
$string['period'] = 'Period';
$string['please_select_and_enter'] = 'Please select or enter a number';
$string['pluginadministration'] = 'BookIt administration';
$string['pluginname'] = 'BookIt';
$string['recipient'] = 'Recipient';
$string['resource_amount'] = 'Amount';
$string['responsibility'] = 'Responsibility';
$string['role'] = 'Role';
$string['room'] = 'Room';
$string['room_active'] = 'Active';
$string['room_active_help'] = 'If this room will be available to select in new events.';
$string['roommode'] = 'Room mode';
$string['roommode_free'] = 'Free selection inside slots';
$string['roommode_slots'] = 'Bookings can only start at beginnings of slots';
$string['rooms'] = 'Rooms';
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
$string['start'] = 'Start';
$string['start_of_period'] = 'Start of period';
$string['summer_semester'] = 'Summer Term';
$string['tools'] = 'Tools';
$string['weekplan'] = 'Week plan';
$string['weekplan_assignment_overlaps'] = 'The entered period is overlapping an already existing weekplan assignment.';
$string['weekplan_room'] = 'Weekplan assignments to rooms';
$string['weekplans'] = 'Week plans';
$string['winter_semester'] = 'Winter Term';
