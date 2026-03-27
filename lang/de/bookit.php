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
$string['active_weekplan'] = 'Aktiver Wochenplan';
$string['add_blocker'] = 'Blocker hinzufügen';
$string['addbooking'] = 'Termin buchen';
$string['afterexam'] = 'Nach der Prüfung';
$string['allfaculties'] = 'Alle Fakultäten';
$string['allrooms'] = 'Alle Räume';
$string['allstatuses'] = 'Alle Status';
$string['back_to_event'] = 'Zurück zur Veranstaltung';
$string['back_to_overview'] = 'Zurück zur Übersicht';
$string['backtooverview'] = 'Zurück zur Übersicht';
$string['before_due'] = 'Vor Fälligkeit';
$string['before_due_date'] = 'Vor Fälligkeitsdatum';
$string['before_event'] = 'Vor der Prüfung';
$string['booking:resource_amount'] = 'Anzahl';
$string['booking:resource_amount_invalid'] = 'Angeforderte Anzahl ({$a->requested}) überschreitet verfügbare Anzahl ({$a->available})';
$string['booking:resource_amount_too_low'] = 'Anzahl muss mindestens 1 sein';
$string['booking:resource_max'] = 'Max: {$a}';
$string['booking:resource_room_conflict'] = 'Der gewählte Raum stellt nicht alle gebuchten Ressourcen zur Verfügung. Nutzen Sie die Raumübersicht, um einen Überblick über die Räume und die jeweiligen verfügbaren Ressourcen zu erhalten.';
$string['booking:resource_selected'] = 'Ausgewählt';
$string['booking:resource_unavailable'] = 'Nicht verfügbar im ausgewählten Raum';
$string['booking:resources_booked'] = 'Gebuchte Ressourcen';
$string['booking:resources_header'] = 'Ressourcen';
$string['booking:resources_info'] = 'Wählen Sie die Ressourcen aus, die Sie für diese Buchung benötigen.';
$string['bookit:addevent'] = 'Add an event';
$string['bookit:addinstance'] = 'BookIt Instanz hinzufügen';
$string['bookit:editevent'] = 'Edit an event';
$string['bookit:editinternal'] = 'Edit an internal field';
$string['bookit:managebasics'] = 'Manage the basic BookIt settings.';
$string['bookit:managemasterchecklist'] = 'Master-Checkliste anzeigen und bearbeiten.';
$string['bookit:view'] = 'BookIt Instanz anzeigen';
$string['bookit:viewalldetailsofevent'] = 'View all details of event';
$string['bookit:viewalldetailsofownevent'] = 'View all details of own event';
$string['bookit:viewownoverview'] = 'Eigene Ereignisübersicht anzeigen';
$string['bookitfieldset'] = 'PLATZHALTER';
$string['calendar'] = 'Kalender';
$string['calendar_desc'] = 'Allgemeines Kalender- und Buchungsverhalten';
$string['category_collapseexpand'] = 'Einklappen/Ausklappen';
$string['category_created'] = 'Kategorie erfolgreich erstellt';
$string['category_deleted'] = 'Kategorie erfolgreich gelöscht';
$string['category_name'] = 'Kategoriename';
$string['category_name_required'] = 'Kategoriename ist erforderlich.';
$string['category_updated'] = 'Kategorie erfolgreich aktualisiert';
$string['checklist'] = 'Checkliste';
$string['checklist_desc'] = 'Optionale Checkliste / Rollenerweiterung';
$string['checklist_duedate_days_after'] = '{$a} Tage nach der Veranstaltung';
$string['checklist_duedate_days_before'] = '{$a} Tage vor der Veranstaltung';
$string['checklist_placeholder'] = 'Dieser Abschnitt ist für das optionale BookIt-Checklist-Add-on reserviert.';
$string['checklistcategory'] = 'Checklisten-Kategorie';
$string['checklistcategory_help'] = 'Die Checklisten-Kategorie, zu der das Checklisten-Element gehört. Neue Elemente werden an die Kategorie angehängt und können anschließend verschoben werden.';
$string['checklistcategorydeleted'] = 'Checklisten-Kategorie erfolgreich gelöscht.';
$string['checklistcategorysuccess'] = 'Checklisten-Kategorie erfolgreich erstellt.';
$string['checklistcategoryupdatesuccess'] = 'Checklisten-Kategorie erfolgreich aktualisiert.';
$string['checklistitem'] = 'Checklisten-Element';
$string['checklistitemdeleted'] = 'Checklisten-Element erfolgreich gelöscht.';
$string['checklistitemname'] = 'Name des Checklisten-Elements';
$string['checklistitemname_help'] = 'Der Textinhalt des Checklisten-Elements, der auf der Checkliste angezeigt wird.';
$string['checklistitemnotfound'] = 'Checklisten-Element nicht gefunden';
$string['checklistitemsuccess'] = 'Checklisten-Element erfolgreich erstellt.';
$string['checklistitemupdatesuccess'] = 'Checklisten-Element erfolgreich aktualisiert.';
$string['chooseevent'] = 'Bitte wählen Sie mindestens ein Ereignis aus.';
$string['color'] = 'Farbe';
$string['csv_format'] = 'CSV (Komma-getrennte Werte)';
$string['csvfile'] = 'CSV-Datei';
$string['csvfile_help'] = 'Wählen Sie eine CSV-Datei mit Checklisten-Daten zum Import aus. Die Datei sollte die Spalten enthalten: category_name, item_name, item_description, order_index.';
$string['customtemplate'] = 'Nachricht';
$string['customtemplate_help'] = 'Die benutzerdefinierte Nachrichtenvorlage für die Benachrichtigung. ';
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
$string['define_institutions'] = 'Institutionen definieren';
$string['duedate'] = 'Fälligkeitsdatum';
$string['duedate_after_event'] = 'Nach Veranstaltung';
$string['duedate_before_event'] = 'Vor Veranstaltung';
$string['duedate_days'] = 'Tage';
$string['duedate_fixed'] = 'Fälligkeitsdatum';
$string['duedate_fixed_date'] = 'Festes Datum';
$string['duedate_help'] = "Das Fälligkeitsdatum für die Fertigstellung des Checklisten-Elements. Muss einer von 'none', 'before' oder 'after' der Prüfung sein. Ein Offset in Tagen muss gesetzt werden, wenn die Optionen 'before' oder 'after' ausgewählt werden.";
$string['duedatetype'] = 'Fälligkeitsdatum-Typ';
$string['duedatetype_help'] = 'Wie das Fälligkeitsdatum relativ zur Veranstaltung berechnet wird.';
$string['edit'] = 'Bearbeiten';
$string['edit_blocker'] = 'Blocker bearbeiten';
$string['edit_event'] = "Termin bearbeiten";
$string['edit_institution'] = 'Institution bearbeiten';
$string['edit_room'] = 'Raum bearbeiten';
$string['edit_room_data'] = 'Raumdaten bearbeiten';
$string['edit_weekplan'] = 'Wochenplan bearbeiten';
$string['edit_weekplan_assignment'] = 'Wochenplan-Zuweisung bearbeiten';
$string['editchecklistitem'] = 'Checklisten-Element bearbeiten';
$string['end'] = 'Ende';
$string['end_before_start'] = 'Der Endzeitpunkt muss nach dem Anfangszeitpunkt liegen!';
$string['end_of_period'] = 'Ende des Zeitraums';
$string['endtime'] = 'Endzeit';
$string['error:resource_not_found'] = 'Ressource nicht gefunden';
$string['error_amount_required'] = 'Anzahl ist erforderlich, wenn nicht als mengenneutral markiert.';
$string['error_category_name_exists'] = 'Eine Kategorie mit diesem Namen existiert bereits. Bitte wählen Sie einen anderen Namen.';
$string['error_category_not_found'] = 'Die ausgewählte Kategorie existiert nicht.';
$string['error_duedate_days_required'] = 'Anzahl der Tage ist erforderlich für Vor/Nach Veranstaltung Fälligkeitsdaten.';
$string['error_duedate_required'] = 'Fälligkeitsdatum ist erforderlich, wenn festes Datum ausgewählt ist.';
$string['error_sortorder_negative'] = 'Sortierreihenfolge muss eine positive Zahl sein.';
$string['event_bookingstatus'] = 'Buchungsstatus';
$string['event_bookingstatus_0'] = 'Neu';
$string['event_bookingstatus_1'] = 'In Bearbeitung';
$string['event_bookingstatus_2'] = 'Akzeptiert';
$string['event_bookingstatus_3'] = 'Storniert';
$string['event_bookingstatus_4'] = 'Abgelehnt';
$string['event_bookingstatus_help'] = 'Erklärung der Buchungsstatus-Optionen.';
$string['event_bookingstatus_list'] = 'Neu, In Bearbeitung, Bestätigt, Storniert, Abgelehnt';
$string['event_checklist:done'] = 'erledigt';
$string['event_checklist:go_to_resources'] = 'Veranstaltungsressourcen';
$string['event_checklist:progress'] = 'Checklisten-Fortschritt';
$string['event_checklist_heading'] = 'Checkliste für Veranstaltung: {$a}';
$string['event_checklist_no_items'] = 'Für diese Veranstaltung sind keine Checklisten-Einträge verfügbar.';
$string['event_checklist_title'] = 'Veranstaltungs-Checkliste';
$string['event_compensationfordisadvantages'] = 'Weitere Nachteilsausgleiche';
$string['event_compensationfordisadvantages_help'] = 'Tragen Sie hier die bereits bekannten Informationen zu Studierenden mit Nachteilsausgleich ein.';
$string['event_department'] = 'Institution';
$string['event_department_help'] = 'Tragen Sie die beantragende Institution ein.';
$string['event_details'] = 'Veranstaltungsdetails';
$string['event_duration'] = 'Dauer des Termins (in Minuten)';
$string['event_duration_help'] = 'Tragen Sie die Dauer des Events ein.';
$string['event_error_mintime'] = 'You cannot enter events in the past.';
$string['event_extratime_description'] = '<i>Zusätzlich wird Zeit vor und nach dem Termin für Vor- und Nachbereitung automatisch hinzugefügt.</i>';
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
$string['event_resources:go_to_checklist'] = 'Veranstaltungscheckliste';
$string['event_resources_checklist:booked_amount'] = 'Gebucht';
$string['event_resources_checklist:confirmed'] = 'bestätigt';
$string['event_resources_checklist:progress'] = 'Ressourcenstatus Fortschritt';
$string['event_resources_checklist_heading'] = 'Ressourcen-Checkliste für Veranstaltung: {$a}';
$string['event_resources_checklist_no_resources'] = 'Dieser Veranstaltung sind keine Ressourcen zugewiesen.';
$string['event_resources_checklist_title'] = 'Veranstaltungs-Ressourcen-Checkliste';
$string['event_resources_heading'] = 'Ressourcen für Veranstaltung: {$a}';
$string['event_resources_title'] = 'Veranstaltungsressourcen';
$string['event_room'] = 'Raum';
$string['event_room_help'] = 'Wählen Sie einen Raum für der Termin.';
$string['event_start'] = 'Beginn';
$string['event_start_help'] = 'Wählen Sie das Startdatum und -uhrzeit des Termins.';
$string['event_students'] = 'Anzahl der Teilnehmenden';
$string['event_students_help'] = 'Tragen Sie die erwartete Anzahl der Teilnehmenden ein.';
$string['event_supportperson'] = 'Support persons';
$string['event_supportperson_help'] = 'Support persons assigned to this event.';
$string['event_timecompensation'] = 'Time compensation';
$string['event_timecompensation_help'] = 'Check if you have participants entitled to time compensation.';
$string['event_usermodified'] = 'Created by user';
$string['export'] = 'Export';
$string['export_error'] = 'Export fehlgeschlagen. Bitte versuchen Sie es erneut.';
$string['export_format'] = 'Exportformat';
$string['export_help'] = 'Sie können für den Export zwischen zwei Dateiformaten wählen. Nutzen Sie PDF, wenn Sie die Liste außerhalb des Systems zur Ansicht nutzen wollen. Nutzen Sie CSV, um eine Sicherungsdatei zu erzeugen oder die Checkliste in ein anderes System zu übertragen.';
$string['export_success'] = 'Export erfolgreich abgeschlossen';
$string['exportedon'] = 'Exportiert am: {$a}';
$string['exportevents'] = 'Ereignisse exportieren';
$string['exportfailed'] = 'Export fehlgeschlagen: {$a}';
$string['filters'] = 'Filter: ';
$string['filters:label'] = 'Filter:';
$string['filters:no_selection'] = 'Keine Auswahl';
$string['filters:room_label'] = 'Raum:';
$string['from_x_onwards'] = 'Ab dem {$a}';
$string['general_settings'] = 'Allgemeine Einstellungen';
$string['global_blocker'] = 'Globaler Blocker';
$string['globally'] = 'Global';
$string['go'] = 'Anwenden';
$string['header_internal'] = 'Internal fields';
$string['import'] = 'Import';
$string['import_error'] = 'Import fehlgeschlagen. Bitte versuchen Sie es erneut.';
$string['import_help'] = 'Nutzen Sie für den Import eine Sicherungsdatei einer Checkliste in CSV-Format. Die in der CSV-Datei enthaltenen Checklistenpunkte und -Kategorien werden in Ihre Checkliste übernommen und werden unterhalb bestehender Punkte erstellt.';
$string['import_rooms'] = 'Räume importieren';
$string['import_rooms_desc'] = 'Wenn aktiviert, werden Raumzuweisungen aus der CSV importiert und Checklisten-Elementen zugeordnet. Wenn deaktiviert, haben Elemente keine Raumzuweisungen.';
$string['import_success'] = 'Import erfolgreich abgeschlossen';
$string['importfailed'] = 'Import fehlgeschlagen: {$a}';
$string['importsuccessful'] = 'Import erfolgreich: {$a} Elemente importiert';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Aktiv';
$string['institution_active_help'] = 'Wenn ja, können buchende Personen diese Institution auswählen.';
$string['institution_name'] = 'Name der Institution';
$string['institutions'] = 'Institutionen';
$string['internalnotes'] = 'Interne Hinweise';
$string['internalnotes_help'] = 'Diese Notizen sind nur für den internen Gebrauch bestimmt und werden der buchenden Person nicht angezeigt.';
$string['invalidchecklistitemid'] = 'Ungültige Checklisten-Element-ID';
$string['invalidcsvformat'] = 'Ungültiges CSV-Format. Bitte überprüfen Sie die Dateistruktur.';
$string['invalideventid'] = 'Ungültige Veranstaltungs-ID';
$string['invalidformat'] = 'Ungültiges Exportformat angegeben';
$string['invalidweekday'] = 'Dieser Wochentag ist für Buchungen nicht erlaubt.';
$string['item_created'] = 'Ressource erfolgreich erstellt';
$string['item_deleted'] = 'Ressource erfolgreich gelöscht';
$string['item_state_done'] = 'Erledigt';
$string['item_state_open'] = 'Offen';
$string['item_state_processing'] = 'In Bearbeitung';
$string['item_state_unknown'] = 'Unbekannt';
$string['item_updated'] = 'Ressource erfolgreich aktualisiert';
$string['legend'] = 'Legende';
$string['local_blocker'] = 'Lokaler Blocker (nur für diesen Raum)';
$string['location'] = 'Ort';
$string['master_checklist'] = 'Haupt-Checkliste';
$string['missing_role'] = 'Fehlende Rolle';
$string['missingdata'] = 'Fehlende erforderliche Daten für Import';
$string['modulename'] = 'BookIt';
$string['modulename_help'] = 'BookIt ist ein PlugIn für die Buchung von Services, Prüfungsterminen, Räumen oder anderer Resscourcen.';
$string['modulenameplural'] = 'BookIt Instanzen';
$string['n_seats'] = '{$a} Plätze';
$string['new_checklistcategory'] = 'Neue Checklisten-Kategorie';
$string['new_checklistitem'] = 'Neues Checklisten-Element';
$string['new_institution'] = 'Neue Institution';
$string['new_room'] = 'Neuer Raum';
$string['new_weekplan'] = 'Neuer Wochenplan';
$string['new_weekplan_assignment'] = 'Neue Wochenplan-Zuweisung';
$string['no_categories_available'] = 'Keine Kategorien verfügbar. Bitte erstellen Sie zuerst eine Kategorie.';
$string['no_selection'] = 'Keine Auswahl';
$string['no_slot_available'] = '<span class="text-danger">Kein Slot für diesen Tag und Raum mehr verfügbar.</span>';
$string['no_weekplan_defined'] = '<span class="text-danger">Kein Wochenplan für diesen Tag und Raum definiert.</span>';
$string['nocontent'] = 'Keine Haupt-Checklisten-Kategorien gefunden. Erstellen Sie die erste Kategorie!';
$string['noduedate'] = 'Kein Fälligkeitsdatum';
$string['noevents'] = 'Keine Ereignisse in der aktuellen Ansicht.';
$string['nofileselected'] = 'Keine Datei für den Import ausgewählt';
$string['notification_time'] = 'Zeit';
$string['notification_time_help'] = 'Der Zeitversatz in Tagen in Bezug auf die Prüfung, wann die Benachrichtigung gesendet werden soll.';
$string['notifications'] = 'Benachrichtigungen';
$string['overdue'] = 'Erinnerung bei Überfälligkeit';
$string['overdue_date'] = 'Nach Überfälligkeitsdatum';
$string['overlapping_allow_all'] = 'Überschneidung von Terminen erlauben';
$string['overlapping_allow_none'] = 'Überschneidung von Terminen nicht erlauben';
$string['overlapping_mode'] = 'Soll Überschneidung von Terminen verhindert werden?';
$string['overlapping_non_confirmed'] = 'Überschneidung von nicht bestätigten Terminen erlauben';
$string['overview'] = 'Meine gebuchten Ereignisse';
$string['overview_action_requires_confirmed_booking'] = 'Checkliste und Ressourcen sind erst nach Bestätigung der Buchungsanfrage verfügbar.';
$string['overview_help'] = 'Zeigt jedes Ereignis an, für das Sie als Prüfer aufgeführt sind.';
$string['overwrite_extratimeafter'] = 'Globale extratimeafter-Einstellung überschreiben?';
$string['overwrite_extratimebefore'] = 'Globale extratimebefore-Einstellung überschreiben?';
$string['pdf_format'] = 'PDF (Portable Document Format)';
$string['pdf_title'] = 'PDF Titel';
$string['pdf_title_help'] = 'Geben Sie einen benutzerdefinierten Titel für das PDF-Dokument ein. Dieser Titel erscheint in der Kopfzeile der exportierten PDF-Datei. Falls leer gelassen, wird der Name der Master-Checkliste als Standard-Titel verwendet.';
$string['period'] = 'Zeitraum';
$string['please_select_and_enter'] = 'Anzahl auswählen oder eintragen';
$string['pluginadministration'] = 'BookIt Administration';
$string['pluginname'] = 'BookIt';
$string['quantity'] = 'Anzahl';
$string['recipient'] = 'Empfänger';
$string['recipient_help'] = 'Der Empfänger der Benachrichtigung.';
$string['reset'] = 'Zurücksetzen';
$string['resetmessagetoconfirm'] = 'Sind Sie sicher, dass Sie die Nachricht auf die Standardvorlage zurücksetzen möchten? Ihre Änderungen werden gelöscht.';
$string['resource'] = 'Ressource';
$string['resource_amount'] = 'Anzahl';
$string['resources'] = 'Ressourcen';
$string['resources:active'] = 'Aktiv';
$string['resources:active_help'] = 'Nur aktive Ressourcen sind für Buchungen verfügbar.';
$string['resources:add_category'] = 'Kategorie hinzufügen';
$string['resources:add_resource'] = 'Ressource hinzufügen';
$string['resources:add_resource_no_categories'] = 'Bitte zuerst eine Kategorie anlegen';
$string['resources:all_rooms'] = 'Alle Räume';
$string['resources:amount'] = 'Anzahl';
$string['resources:amount_help'] = 'Die Anzahl der verfügbaren Einheiten dieser Ressource.';
$string['resources:amount_irrelevant'] = 'Anzahl irrelevant';
$string['resources:amount_must_be_positive'] = 'Die Anzahl muss eine positive Zahl sein.';
$string['resources:amount_unlimited'] = 'Unbegrenzt';
$string['resources:amountirrelevant'] = 'Anzahl irrelevant';
$string['resources:amountirrelevant_help'] = 'Aktivieren Sie dies, wenn die Ressource keine spezifische Menge hat (z.B. WLAN, Whiteboard).';
$string['resources:back_to_resources'] = 'Zurück zu Ressourcen';
$string['resources:category'] = 'Kategorie';
$string['resources:category_has_resources'] = 'Zum Löschen darf keine Ressource der Kategorie zugeordnet sein. Ordnen Sie die entsprechenden Ressourcen anderen Kategorien zu oder löschen Sie zuvor die Ressourcen in dieser Kategorie.';
$string['resources:category_help'] = 'Wählen Sie die Kategorie für diese Ressource aus.';
$string['resources:category_not_found'] = 'Die ausgewählte Ressourcenkategorie wurde nicht gefunden.';
$string['resources:category_required'] = 'Ressourcenkategorie ist erforderlich.';
$string['resources:confirm_delete_category'] = 'Möchten Sie die Kategorie "{$a}" wirklich löschen? Dadurch werden auch alle Ressourcen in dieser Kategorie gelöscht.';
$string['resources:confirm_delete_resource'] = 'Möchten Sie die Ressource "{$a}" wirklich löschen?';
$string['resources:customtemplatedefaultmessage_before_due'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### ist am ###DATE### fällig. Bitte stellen Sie sicher, dass sie rechtzeitig vorbereitet wird.';
$string['resources:customtemplatedefaultmessage_overdue'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### ist überfällig. Bitte handeln Sie umgehend.';
$string['resources:customtemplatedefaultmessage_when_done'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### wurde als erledigt markiert.';
$string['resources:customtemplatedefaultmessage_when_due'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### ist heute fällig. Bitte bestätigen Sie ihre Verfügbarkeit.';
$string['resources:delete_category'] = 'Kategorie löschen';
$string['resources:delete_resource'] = 'Ressource löschen';
$string['resources:description'] = 'Beschreibung';
$string['resources:description_help'] = 'Optionale Beschreibung für die Ressource oder Kategorie.';
$string['resources:duedate'] = 'Fälligkeitsdatum';
$string['resources:duedate_absolute'] = 'Absolutes Datum';
$string['resources:duedate_relative'] = 'Relativ zur Veranstaltung';
$string['resources:duedate_type'] = 'Fälligkeitsdatum-Typ';
$string['resources:edit_category'] = 'Kategorie bearbeiten';
$string['resources:edit_resource'] = 'Ressource bearbeiten';
$string['resources:filter_no_resources'] = 'Bitte zuerst eine Kategorie und Ressourcen anlegen.';
$string['resources:generate_checklist'] = 'Checkliste generieren';
$string['resources:inactive'] = 'Inaktiv';
$string['resources:info'] = 'Ressourcen-Information';
$string['resources:internalinfo'] = 'Interne Informationen';
$string['resources:internalinfo_help'] = 'Interne Hinweise, die nur für Administratoren sichtbar sind.';
$string['resources:invalid_status'] = 'Ungültiger Statuswert.';
$string['resources:name'] = 'Name';
$string['resources:name_help'] = 'Der Name der Ressource oder Kategorie.';
$string['resources:name_required'] = 'Ressourcenname ist erforderlich.';
$string['resources:no_categories'] = 'Noch keine Kategorien';
$string['resources:no_items'] = 'Keine Ressourcen in dieser Kategorie';
$string['resources:no_resources'] = 'Keine Ressourcen in dieser Kategorie';
$string['resources:no_rooms'] = 'Noch keine Räume angelegt.';
$string['resources:no_rooms_link'] = 'Räume anlegen';
$string['resources:notfound'] = 'Ressource nicht gefunden';
$string['resources:notification_before'] = 'Benachrichtigung vor Fälligkeit';
$string['resources:notification_done'] = 'Benachrichtigung bei Erledigung';
$string['resources:notification_overdue'] = 'Benachrichtigung bei Überfälligkeit';
$string['resources:notification_status_changed_body'] = 'Der Status der Ressource "{$a->resourcename}" für die Veranstaltung "{$a->eventname}" wurde aktualisiert auf: {$a->statuslabel}.';
$string['resources:notification_status_changed_subject'] = 'Ressourcenstatus aktualisiert: {$a->eventname}';
$string['resources:notification_when'] = 'Benachrichtigung bei Fälligkeit';
$string['resources:open_settings'] = 'Ressourcen-Checklisten-Einstellungen';
$string['resources:overview'] = 'Ressourcen-Übersicht';
$string['resources:resource'] = 'Ressource';
$string['resources:rooms'] = 'Räume';
$string['resources:rooms_help'] = 'Wählen Sie Räume aus, in denen diese Ressource verfügbar ist. Leer lassen, wenn die Ressource in allen Räumen verfügbar ist. Mehrere Räume können durch Halten von CTRL ausgewählt werden.';
$string['resources:settings'] = 'Ressourcen-Einstellungen';
$string['resources:settings_column'] = 'Einstellungen';
$string['resources:settings_empty'] = 'Keine Ressourcen-Einstellungen konfiguriert. Einträge aus vorhandenen Ressourcen generieren.';
$string['resources:settings_generated'] = '{$a} Checklisteneinträge erfolgreich generiert.';
$string['resources:settings_saved'] = 'Einstellungen erfolgreich gespeichert.';
$string['resources:settings_sortorder_help'] = 'Anzeigereihenfolge in den Ressourcen-Einstellungen (unabhängig von der Ressourcenübersicht).';
$string['resources:status'] = 'Ressourcenstatus';
$string['resources:status_confirmed'] = 'Bestätigt';
$string['resources:status_inprogress'] = 'In Bearbeitung';
$string['resources:status_rejected'] = 'Abgelehnt';
$string['resources:status_requested'] = 'Angefragt';
$string['resources:view_settings'] = 'Ressourcen-Checklisten-Einstellungen';
$string['resources_desc'] = 'Räume, Ressourcenfarben & Verfügbarkeit';
$string['responsibility'] = 'Verantwortlichkeit';
$string['role'] = 'Rolle';
$string['role_help'] = 'Diese Rollen werden dem Checklisten-Element zugewiesen und sind für die Ausführung verantwortlich. Mehrere Rollen können durch Halten von STRG ausgewählt werden.';
$string['room'] = 'Raum';
$string['room_active'] = 'Aktiv';
$string['room_active_help'] = 'Wenn ja, können buchende Personen diesen Raum auswählen..';
$string['roommode'] = 'Raummodus';
$string['roommode_free'] = 'Freie Auswahl innerhalb der Slots';
$string['roommode_slots'] = 'Buchungen können nur an Anfängen von Slots starten';
$string['roommode_top_to_bottom'] = 'Tage nur von früh nach spät füllen';
$string['rooms'] = 'Räume';
$string['rooms_help'] = 'Diese Räume werden dem Checklisten-Element zugewiesen. Mehrere Räume können durch Halten von STRG ausgewählt werden.';
$string['runinstallhelper'] = 'Installationshilfe ausführen';
$string['runinstallhelperinfo'] = 'Wenn Sie das Plugin gerade installiert haben, können Sie die Installationshilfe einmal ausführen, um Standard-BookIt-Rollen und Beispiel-Checklisten-Daten zu erstellen. Andernfalls müssen Sie die Rollen manuell aus den bereitgestellten Dateien im Plugin-Verzeichnis importieren.';
$string['search'] = 'Suchen';
$string['seats'] = 'Anzahl an Plätzen';
$string['select_coursetemplate'] = 'Auswahl Prüfungskursvorlage';
$string['select_coursetemplate_help'] = 'Wählen Sie eine Kursvorlage für den Kurs, in dem Ihre Prüfung stattfindet.';
$string['select_semester'] = 'Semester';
$string['select_semester_help'] = 'Wählen Sie das Semester aus, in dem der Termin stattfindet';
$string['selectevents'] = 'Bitte markieren Sie die zu exportierenden Ereignisse:';
$string['settings_eventdefaultduration'] = 'Default duration of an event (min)';
$string['settings_eventdurationstepwidth'] = 'The step width for the duration of an event (min)';
$string['settings_eventmaxduration'] = 'Maximum duration of an event (min)';
$string['settings_eventmaxyear'] = 'Maxmum year to select for event';
$string['settings_eventmaxyear_desc'] = 'Set the maxmum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventminyear'] = 'Minimum year to select for event';
$string['settings_eventminyear_desc'] = 'Set the minimum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_extratime'] = 'Extra time for event';
$string['settings_extratime_after'] = 'Zusätzliche Zeit nach dem Termin (min)';
$string['settings_extratime_after_desc'] = 'Zusätzliche Zeit in Minuten, die automatisch nach dem Termin hinzuaddiert wird, um Nacharbeiten zu erlauben.';
$string['settings_extratime_before'] = 'Zusätzliche Zeit vor dem Termin (min)';
$string['settings_extratime_before_desc'] = 'Zusätzliche Zeit in Minuten, die automatisch vor dem Termin hinzuaddiert wird, um Vorarbeiten zu erlauben.';
$string['settings_extratime_desc'] = 'Extra time which will be added automatically to each event to allow preparation and wrap-up works to be done.';
$string['settings_overview'] = 'Einstellungsübersicht';
$string['settings_pdf_checklist_heading'] = 'PDF-Checklisten-Einstellungen';
$string['settings_pdf_logo_custom'] = 'Benutzerdefiniertes PDF-Logo';
$string['settings_pdf_logo_custom_desc'] = 'Laden Sie ein benutzerdefiniertes Logo hoch, das in PDF-Checklisten verwendet wird, wenn oben "Benutzerdefiniertes Logo" ausgewählt ist. Unterstützte Formate: PNG, JPG, JPEG. Optimale Breite: 200-400px.';
$string['settings_pdf_logo_enable'] = 'Logo in PDF-Checkliste aktivieren';
$string['settings_pdf_logo_enable_desc'] = 'Ein Logo im Kopf der exportierten PDF-Checklisten anzeigen.';
$string['settings_pdf_logo_source'] = 'Logo-Quelle';
$string['settings_pdf_logo_source_custom'] = 'Benutzerdefiniertes Logo';
$string['settings_pdf_logo_source_desc'] = 'Wählen Sie die Quelle für das in PDF-Checklisten angezeigte Logo.';
$string['settings_pdf_logo_source_site'] = 'Site-Logo (core_admin | logo)';
$string['settings_pdf_logo_source_theme'] = 'Theme-Logo (theme_boost_union | logo)';
$string['settings_roomcolor'] = 'Color for room {$a}';
$string['settings_roomcolor_desc'] = 'Select a color to be used for the calendar view.';
$string['settings_roomcolor_wcagcheck'] = 'Color contrast check for room {$a}';
$string['settings_roomcolor_wcagcheck_desc'] = 'Contrast check for color <i>#{$a->bcolor}</i> and text <i>#{$a->fcolor}</i>: ';
$string['settings_roomcolorheading'] = 'Room colors';
$string['settings_sortorder'] = 'Sortierreihenfolge';
$string['settings_textcolor'] = 'Event text color';
$string['settings_textcolor_desc'] = 'Set the text color of the event in the calendar view.';
$string['settings_weekdaysvisible'] = 'Im Kalender angezeigte Wochentage';
$string['settings_weekdaysvisible_desc'] = 'Wählen Sie aus, welche Wochentage im BookIt-Kalender erscheinen und für Ereignisse ausgewählt werden können.
     <br><em>Standard: Montag, Dienstag, Mittwoch, Donnerstag, Freitag</em><br>
     <span style="color:#b50000;">
         Bitte beachten Sie, dass durch das Ausblenden von Wochentagen bereits gebuchte Ereignisse
         an diesen Tagen nicht mehr angezeigt werden.
     </span>';
$string['shortname'] = 'Kurzname';
$string['sort'] = 'Sortieren';
$string['sortorder_must_be_positive'] = 'Die Sortierreihenfolge muss eine positive Zahl sein.';
$string['start'] = 'Anfang';
$string['start_of_period'] = 'Anfang des Zeitraums';
$string['starttime'] = 'Startzeit';
$string['status'] = 'Status';
$string['summer_semester'] = 'Sommersemester';
$string['time'] = 'Zeit';
$string['time_help'] = "Wenn das Fälligkeitsdatum für das Checklisten-Element 'before' oder 'after' ist, definiert diese Einstellung, wie viele Tage vor oder nach der Prüfung das Checklisten-Element abgeschlossen werden soll.";
$string['timeslots'] = 'Zeitslots';
$string['tools'] = 'Werkzeuge';
$string['weekplan'] = 'Wochenplan';
$string['weekplan_assignment_overlaps'] = 'Der eingegebene Zeitraum überschneidet sich mit einer bereits bestehenden Wochenplan-Zuweisung.';
$string['weekplan_assignments'] = 'Wochenplan-Zuweisungen';
$string['weekplan_help'] = 'Der Wochenplan definiert die verfügbaren Zeitslots und Raumzuweisungen für dieses Semester.';
$string['weekplan_room'] = 'Wochenplan-Zuweisungen zu Räumen';
$string['weekplans'] = 'Wochenpläne';
$string['when_done'] = 'Wenn erledigt';
$string['when_due'] = 'Wenn fällig';
$string['winter_semester'] = 'Wintersemester';
