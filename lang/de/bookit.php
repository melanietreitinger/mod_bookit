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
$string['bookit:addinstance'] = 'BookIt Instanz hinzufügen';
$string['bookit:view'] = 'BookIt Instanz anzeigen';
$string['bookitfieldset'] = 'PLATZHALTER';
$string['calendar'] = 'Kalender';
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
$string['edit'] = 'Bearbeiten';
$string['edit_event'] = "Termin bearbeiten";
$string['edit_institution'] = 'Institution bearbeiten';
$string['edit_room'] = 'Raum bearbeiten';
$string['edit_room_data'] = 'Raumdaten bearbeiten';
$string['edit_weekplan'] = 'Wochenplan bearbeiten';
$string['edit_weekplan_assignment'] = 'Wochenplan-Zuweisung bearbeiten';
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
$string['general_settings'] = 'Allgemeine Einstellungen';
$string['global_blocker'] = 'Globaler Blocker';
$string['globally'] = 'Global';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Aktiv';
$string['institution_active_help'] = 'Wenn ja, können buchende Personen diese Institution auswählen.';
$string['institution_name'] = 'Name der Institution';
$string['institutions'] = 'Institutionen';
$string['internalnotes'] = 'Interne Hinweise';
$string['internalnotes_help'] = 'Diese Notizen sind nur für den internen Gebrauch bestimmt und werden der buchenden Person nicht angezeigt.';
$string['legend'] = 'Legende';
$string['local_blocker'] = 'Lokaler Blocker (nur für diesen Raum)';
$string['item_state_done'] = 'Erledigt';
$string['item_state_open'] = 'Offen';
$string['item_state_processing'] = 'In Bearbeitung';
$string['item_state_unknown'] = 'Unbekannt';
$string['master_checklist'] = 'Haupt-Checkliste';
$string['modulename'] = 'BookIt';
$string['modulename_help'] = 'BookIt ist ein PlugIn für die Buchung von Services, Prüfungsterminen, Räumen oder anderer Resscourcen.';
$string['modulenameplural'] = 'BookIt Instanzen';
$string['new_institution'] = 'Neue Institution';
$string['new_room'] = 'Neuer Raum';
$string['new_timeslot'] = 'Neue timeslot';
$string['new_weekplan'] = 'Neuer Wochenplan';
$string['new_weekplan_assignment'] = 'Neue Wochenplan-Zuweisung';
$string['normal_slot'] = 'Normaler Slot';
$string['period'] = 'Zeitraum';
$string['new_checklistcategory'] = 'Neue Checklisten-Kategorie';
$string['new_checklistitem'] = 'Neues Checklisten-Element';
$string['please_select_and_enter'] = 'Anzahl auswählen oder eintragen';
$string['pluginadministration'] = 'BookIt Administration';
$string['pluginname'] = 'BookIt';
$string['recipient'] = 'Empfänger';
$string['resource_amount'] = 'Anzahl';
$string['room'] = 'Raum';
$string['room_active'] = 'Aktiv';
$string['room_active_help'] = 'Wenn ja, können buchende Personen diesen Raum auswählen..';
$string['responsibility'] = 'Verantwortlichkeit';
$string['role'] = 'Rolle';
$string['room'] = 'Raum';
$string['roommode'] = 'Raummodus';
$string['roommode_free'] = 'Freie Auswahl innerhalb der Slots';
$string['roommode_slots'] = 'Buchungen können nur an Anfängen von Slots starten';
$string['rooms'] = 'Räume';
$string['seats'] = 'Anzahl an Plätzen';
$string['select_coursetemplate'] = 'Auswahl Prüfungskursvorlage';
$string['select_coursetemplate_help'] = 'Wählen Sie eine Kursvorlage für den Kurs, in dem Ihre Prüfung stattfindet.';
$string['select_semester'] = 'Semester';
$string['select_semester_help'] = 'Wählen Sie das Semester aus, in dem der Termin stattfindet';
$string['settings_eventdefaultduration'] = 'Voreingestellte Länge eines Termins (min)';
$string['settings_eventdurationstepwidth'] = 'Schrittweite für die Länge eines Termins (min)';
$string['settings_eventmaxduration'] = 'Maximal mögliche Länge eines Termins (min)';
$string['settings_eventstartstepwidth'] = 'Schrittweite für Terminstartzeitpunkte (min)';
$string['start'] = 'Anfang';
$string['start_of_period'] = 'Anfang des Zeitraums';
$string['sort'] = 'Sortieren';
$string['summer_semester'] = 'Sommersemester';
$string['timeslots'] = 'Zeitslots';
$string['tools'] = 'Werkzeuge';
$string['weekplan'] = 'Wochenplan';
$string['weekplan_assignment_overlaps'] = 'Der eingegebene Zeitraum überschneidet eine bereits existierende Wochenplanzuweisung.';
$string['weekplan_assignments'] = 'Wochenplan-Zuweisungen';
$string['weekplan_help'] = 'Hier können Wochenpläne definiert werden. Jede Zeile startet mit einem abgekürzten Wochentag, gefolgt von einer kommagetrennten Liste von Zeitslots.<br>
Dies sind Beispiele für valide Zeilen:
<pre>
Di 8-11:30, 14:00-17
Mi 09-16
Do 07:45-10:00,10-12,13-15
</pre>';
$string['weekplan_room'] = 'Wochenplan-Zuweisungen zu Räumen';
$string['weekplans'] = 'Wochenpläne';
$string['time'] = 'Zeit';
$string['winter_semester'] = 'Wintersemester';
