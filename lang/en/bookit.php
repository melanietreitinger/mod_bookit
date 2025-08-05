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
$string['modulename'] = 'BookIt';
$string['modulename_help'] = 'BookIt is a tool to book services or items, e.g. exam dates, rooms and ressources.';
$string['modulenameplural'] = 'BookIt instances';
$string['please_select_and_enter'] = 'Please select or enter a number';
$string['pluginadministration'] = 'BookIt administration';
$string['pluginname'] = 'BookIt';
$string['resource_amount'] = 'Amount';
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
$string['summer_semester'] = 'Summer Term';
$string['winter_semester'] = 'Winter Term';


$string['overview']        = 'My booked events';
$string['overview_help']   = 'Shows every event for which you are listed as examiner.';
$string['checklist']       = 'Checklist';
$string['status']          = 'Status';
$string['room']            = 'Room';
$string['bookit:viewownoverview'] = 'View own events overview';


//NEU
$string['config_weekdaysvisible']      = 'Weekdays shown in calendar';
$string['config_weekdaysvisible_desc'] =
    'Choose which weekdays appear in the BookIT calendar and may be selected for events.
     <br><em>Default: Monday, Tuesday, Wednesday, Thursday, Friday</em><br>
     <span style="color:#b50000;">
         Please note that by hiding weekdays, events that have already been booked
         on those days will no longer be displayed.
     </span>';
$string['invalidweekday'] = 'This weekday is not allowed for booking.';



$string['backtooverview'] = 'Back to overview';
$string['event_bookingstatus_help'] = 'Explanation of the booking status options.';
$string['editevent'] = 'Edit event';

$string['new']         = 'New';
$string['inprogress']  = 'In progress';
$string['accepted']    = 'Accepted';
$string['cancelled']   = 'Cancelled';
$string['rejected']    = 'Rejected';

$string['allrooms']     = 'All rooms';
$string['allfaculties'] = 'All faculties';
$string['allstatuses']  = 'All statuses';

$string['exportevents'] = 'Export events';
$string['selectevents'] = 'Please tick the events to export:';
$string['noevents']     = 'No events in current view.';
$string['chooseevent']  = 'Please select at least one event.';
$string['export'] = "Export";
