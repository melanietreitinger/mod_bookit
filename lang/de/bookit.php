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

$string['add_blocker'] = 'Blocker hinzufügen';
$string['addbooking'] = 'Termin buchen';
// ...@TODO: translate.
$string['bookit:addevent'] = 'Add an event';
$string['bookit:addinstance'] = 'BookIt Instanz hinzufügen';
// ...@TODO: translate.
$string['bookit:addresource'] = 'Add a resource';
// ...@TODO: translate.
$string['bookit:editevent'] = 'Edit an event';
// ...@TODO: translate.
$string['bookit:editinternal'] = 'Edit an internal field';
// ...@TODO: translate.
$string['bookit:editresource'] = 'Edit a resource';
$string['bookit:view'] = 'BookIt Instanz anzeigen';
// ...@TODO: translate.
$string['bookit:viewalldetailsofevent'] = 'View all details of event';
// ...@TODO: translate.
$string['bookit:viewalldetailsofownevent'] = 'View all details of own event';
$string['bookitfieldset'] = 'PLATZHALTER';
$string['color'] = 'Farbe';
$string['define_institutions'] = 'Institutionen definieren';
$string['edit_blocker'] = 'Blocker bearbeiten';
$string['category_name'] = 'Kategoriename';
$string['checklistcategory'] = 'Checklisten-Kategorie';
$string['checklistcategorydeleted'] = 'Checklisten-Kategorie erfolgreich gelöscht.';
$string['checklistcategorysuccess'] = 'Checklisten-Kategorie erfolgreich erstellt.';
$string['checklistcategoryupdatesuccess'] = 'Checklisten-Kategorie erfolgreich aktualisiert.';
$string['checklistitem'] = 'Checklisten-Element';
$string['checklistitemdeleted'] = 'Checklisten-Element erfolgreich gelöscht.';
$string['checklistitemname'] = 'Name des Checklisten-Elements';
$string['checklistitemsuccess'] = 'Checklisten-Element erfolgreich erstellt.';
$string['checklistitemupdatesuccess'] = 'Checklisten-Element erfolgreich aktualisiert.';
$string['customtemplate'] = 'Nachricht';
$string['customtemplatedefaultmessage'] = 'Lorem ipsum dolor sit amet ###RECIPIENT###,'
. '<p>Consectetur adipiscing elit. ###CHECKLISTCATEGORY### ullamcorper etiam sit. ###CHECKLISTITEM### vulputate '
. 'velit esse. ###ITEMDUETIME### suscipit in posuere. ###ITEMSTATUS### mollis dolor.</p>'
. '<p>Non ###SEMESTERTERM###, commodo luctus ###EVENTTITLE###. Elit libero, ###DEPARTMENT### euismod ###ROOM### '
. 'semper. ###EVENTSTART### quis blandit turpis. ###EVENTDURATION### risus auctor, ###TOTALDURATION### in.</p>'
. '<p>Curabitur blandit tempus ###COURSETEMPLATE###, sollicitudin ###PERSONINCHARGE###. Nullam quis risus eget '
. '###OTHEREXAMINERS### congue leo. ###NUMBEROFPARTICIPANTS### sagittis ###BOOKINGPERSON### integer ###BOOKINGSTATUS###.</p>'
. '<p>Nulla vitae elit libero,<br>'
. 'Cras justo odio.</p>';
$string['edit'] = 'Bearbeiten';
$string['edit_event'] = "Termin bearbeiten";
$string['end'] = 'Ende';
$string['end_before_start'] = 'Der Endzeitpunkt muss nach dem Anfangszeitpunkt liegen!';
$string['end_of_period'] = 'Ende des Zeitraums';
$string['event_bookingstatus'] = 'Buchungsstatus';
$string['event_bookingstatus_list'] = 'Neu, In Bearbeitung, Bestätigt, Storniert, Abgelehnt';
$string['event_compensationfordisadvantages'] = 'Weitere Nachteilsausgleiche';
$string['event_compensationfordisadvantages_help'] = 'Tragen Sie hier die bereits bekannten Informationen zu Studierenden mit Nachteilsausgleich ein.';
$string['event_department'] = 'Institution';
$string['event_department_help'] = 'Tragen Sie die beantragende Institution ein.';
$string['event_duration'] = 'Dauer des Termins (in Minuten)';
$string['event_duration_help'] = 'Tragen Sie die Dauer des Events ein.';
// ...@TODO: translate.
$string['event_error_mintime'] = 'You cannot enter events in the past.';
// ...@TODO: translate.
$string['event_extratime_description'] = '<i>Note that an extra time of {$a} minutes is automatically added to each event to allow preparation and wrap-up works to be done.</i>';
// ...@TODO: translate.
$string['event_extratime_label'] = '<i>Extra time for the event</i>';
$string['event_internalnotes'] = 'Interne Hinweise';
$string['event_internalnotes_help'] = 'Diese Notizen sind nur für den internen Gebrauch bestimmt und werden der buchenden Person nicht angezeigt.';
$string['event_name'] = 'Termin Name';
$string['event_name_help'] = 'Tragen Sie den Namen des Termins ein.';
$string['event_notes'] = 'Anmerkungen';
$string['event_notes_help'] = 'Tragen Sie zusätzliche Informationen für den Support ein.';
$string['event_otherexaminers'] = 'Weitere Prüfende';
$string['event_otherexaminers_help'] = 'Wählen Sie weitere Püfende für diese Prüfung aus.';
$string['event_personincharge'] = 'Verantwortliche Person';
$string['event_personincharge_help'] = 'Tragen Sie die verantwortliche Person ein.';
$string['event_refcourseid'] = 'Prüfungskurs';
$string['event_refcourseid_help'] = 'Auswahl des Prüfungskurses, der zu dieser Prüfung gehört.';
$string['event_reserved'] = 'Gebucht';
$string['event_resources'] = 'Ressourcen';
$string['event_room'] = 'Raum';
$string['event_room_help'] = 'Wählen Sie einen Raum für der Termin.';
$string['event_start'] = 'Beginn';
$string['event_start_help'] = 'Wählen Sie das Startdatum und -uhrzeit des Termins.';
$string['event_students'] = 'Anzahl der Teilnehmenden';
$string['event_students_help'] = 'Tragen Sie die erwartete Anzahl der Teilnehmenden ein.';
// ...@TODO: translate.
$string['event_supportperson'] = 'Support persons';
// ...@TODO: translate.
$string['event_supportperson_help'] = 'Support persons assigned to this event.';
// ...@TODO: translate.
$string['event_timecompensation'] = 'Time compensation';
// ...@TODO: translate.
$string['event_timecompensation_help'] = 'Check if you have participants entitled to time compensation.';
// ...@TODO: translate.
$string['event_usermodified'] = 'Created by user';
// ...@TODO: translate.
$string['header_internal'] = 'Internal fields';
$string['general_settings'] = 'Allgemeine Einstellungen';
$string['globally'] = 'Global';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Aktiv';
$string['institution_active_help'] = 'Wenn ja, können buchende Personen diese Institution auswählen.';
$string['institution_name'] = 'Name der Institution';
$string['institutions'] = 'Institutionen';
$string['internalnotes'] = 'Interne Hinweise';
$string['internalnotes_help'] = 'Diese Notizen sind nur für den internen Gebrauch bestimmt und werden der buchenden Person nicht angezeigt.';
$string['item_state_done'] = 'Erledigt';
$string['item_state_open'] = 'Offen';
$string['item_state_processing'] = 'In Bearbeitung';
$string['item_state_unknown'] = 'Unbekannt';
$string['master_checklist'] = 'Haupt-Checkliste';
$string['modulename'] = 'BookIt';
$string['modulename_help'] = 'BookIt ist ein PlugIn für die Buchung von Services, Prüfungsterminen, Räumen oder anderer Resscourcen.';
$string['modulenameplural'] = 'BookIt Instanzen';
$string['new_institution'] = 'Neue Institution';
$string['new_checklistcategory'] = 'Neue Checklisten-Kategorie';
$string['new_checklistitem'] = 'Neues Checklisten-Element';
$string['new_room'] = 'Neuer Raum';
$string['new_timeslot'] = 'Neue timeslot';
$string['new_weekplan'] = 'Neuer Wochenplan';
$string['new_weekplan_room'] = 'Neue Wochenplan-Zuweisung';
$string['period'] = 'Zeitraum';
$string['please_select_and_enter'] = 'Anzahl auswählen oder eintragen';
$string['pluginadministration'] = 'BookIt Administration';
$string['pluginname'] = 'BookIt';
$string['recipient'] = 'Empfänger';
$string['resource_amount'] = 'Anzahl';
$string['responsibility'] = 'Verantwortlichkeit';
$string['role'] = 'Rolle';
$string['room'] = 'Raum';
$string['roomcolorheading'] = 'Room colors';
$string['rooms'] = 'Räume';
$string['select_coursetemplate'] = 'Auswahl Prüfungskursvorlage';
$string['select_coursetemplate_help'] = 'Wählen Sie eine Kursvorlage für den Kurs, in dem Ihre Prüfung stattfindet.';
$string['select_semester'] = 'Semester';
$string['select_semester_help'] = 'Wählen Sie das Semester aus, in dem der Termin stattfindet';
// ...@TODO: translate.
$string['settings_eventmaxyears'] = 'Maxmum year to select for event';
// ...@TODO: translate.
$string['settings_eventmaxyears_desc'] = 'Set the maxmum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
// ...@TODO: translate.
$string['settings_eventminyears'] = 'Minimum year to select for event';
// ...@TODO: translate.
$string['settings_eventminyears_desc'] = 'Set the minimum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
// ...@TODO: translate.
$string['settings_extratime'] = 'Extra time for event';
// ...@TODO: translate.
$string['settings_extratime_desc'] = 'Extra time which will be added automatically to each event to allow preparation and wrap-up works to be done.';
// ...@TODO: translate.
$string['settings_roomcolor'] = 'Color for room {$a}';
// ...@TODO: translate.
$string['settings_roomcolor_desc'] = 'Select a color to be used for the calendar view.';
// ...@TODO: translate.
$string['settings_roomcolor_wcagcheck'] = 'Color contrast check for room {$a}';
// ...@TODO: translate.
$string['settings_roomcolor_wcagcheck_desc'] = 'Contrast check for color <i>#{$a->bcolor}</i> and text <i>#{$a->fcolor}</i>: ';
// ...@TODO: translate.
$string['settings_roomcolorheading'] = 'Room colors';
// ...@TODO: translate.
$string['settings_textcolor'] = 'Event text color';
// ...@TODO: translate.
$string['settings_textcolor_desc'] = 'Set the text color of the event in the calendar view.';
$string['start'] = 'Anfang';
$string['start_of_period'] = 'Anfang des Zeitraums';
$string['sort'] = 'Sortieren';
$string['summer_semester'] = 'Sommersemester';
$string['timeslots'] = 'Zeitslots';
$string['tools'] = 'Werkzeuge';
$string['time'] = 'Zeit';
$string['type_before_due'] = 'Vor Fälligkeit';
$string['type_before_due_date'] = 'Vor Fälligkeitsdatum';
$string['type_overdue'] = 'Erinnerung bei Überfälligkeit';
$string['type_overdue_date'] = 'Nach Überfälligkeitsdatum';
$string['type_when_done'] = 'Wenn erledigt';
$string['type_when_due'] = 'Wenn fällig';
$string['type_when_due'] = 'Wenn fällig';
$string['weekplan'] = 'Wochenplan';
$string['weekplan_assignment_overlaps'] = 'Der eingegebene Zeitraum überschneidet eine bereits existierende Wochenplanzuweisung.';
$string['weekplan_room'] = 'Wochenplan-Zuweisungen';
$string['weekplans'] = 'Wochenpläne';
$string['winter_semester'] = 'Wintersemester';
