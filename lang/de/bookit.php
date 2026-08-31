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
$string['afterexam'] = 'Nach der Prüfung';
$string['allfaculties'] = 'Alle Fakultäten';
$string['allrooms'] = 'Alle Räume';
$string['allstatuses'] = 'Alle Status';
$string['back_to_overview'] = 'Zurück zur Übersicht';
$string['before_due'] = 'Vor Fälligkeit';
$string['before_event'] = 'Vor der Prüfung';
$string['booking:resource_amount'] = 'Anzahl';
$string['booking:resource_amount_invalid'] = 'Angeforderte Anzahl ({$a->requested}) überschreitet verfügbare Anzahl ({$a->available})';
$string['booking:resource_amount_too_low'] = 'Anzahl muss mindestens 1 sein';
$string['booking:resource_max'] = 'Max: {$a}';
$string['booking:resource_room_conflict'] = 'Der gewählte Raum stellt nicht alle gebuchten Ressourcen zur Verfügung. Nutzen Sie die Raumübersicht, um einen Überblick über die Räume und die jeweiligen verfügbaren Ressourcen zu erhalten.';
$string['bookingstatus_action_reactivate'] = 'Als neue Anfrage reaktivieren';
$string['bookingstatus_body_canceled'] = 'Nachrichtentext für Storniert';
$string['bookingstatus_body_confirmed'] = 'Nachrichtentext für Bestätigt';
$string['bookingstatus_body_default_canceled'] = 'Vielen Dank fuer Ihre Anfrage "###EVENTNAME###" fuer ###BOOKINGDATE###. Leider muessen wir die Anfrage aufgrund bestimmter Umstaende ablehnen.';
$string['bookingstatus_body_default_confirmed'] = 'Vielen Dank fuer Ihre Anfrage "###EVENTNAME###" fuer ###BOOKINGDATE###. Gerne bestaetigen wir Ihre Buchung.';
$string['bookingstatus_body_default_inprogress'] = 'Vielen Dank fuer Ihre Anfrage "###EVENTNAME###" fuer ###BOOKINGDATE###. Einige Ressourcen werden noch geprueft, Sie werden in Kuerze benachrichtigt.';
$string['bookingstatus_body_default_new'] = 'Vielen Dank fuer Ihre Buchungsanfrage "###EVENTNAME###" fuer ###BOOKINGDATE###. Wir haben Ihre Anfrage erhalten und pruefen sie in Kuerze.';
$string['bookingstatus_body_default_rejected'] = 'Vielen Dank fuer Ihre Anfrage "###EVENTNAME###" fuer ###BOOKINGDATE###. Leider muessen wir die Anfrage aufgrund bestimmter Umstaende ablehnen.';
$string['bookingstatus_body_desc'] = 'Unterstuetzte Platzhalter: ###EVENTNAME### und ###BOOKINGDATE###. Weitere unterstuetzte Platzhalter: ###BOOKINGSTATUS###, ###OLDBOOKINGSTATUS###, ###EVENTURL###, ###ROOM###, ###STARTTIME###, ###ENDTIME###, ###BOOKINGPERSON###, ###PERSONINCHARGE###, ###OTHEREXAMINERS###.';
$string['bookingstatus_body_inprogress'] = 'Nachrichtentext für In Bearbeitung';
$string['bookingstatus_body_new'] = 'Nachrichtentext für Neu';
$string['bookingstatus_body_rejected'] = 'Nachrichtentext für Abgelehnt';
$string['bookingstatus_enabled_canceled'] = 'Nachricht fuer Storniert senden';
$string['bookingstatus_enabled_confirmed'] = 'Nachricht fuer Bestaetigt senden';
$string['bookingstatus_enabled_inprogress'] = 'Nachricht fuer In Bearbeitung senden';
$string['bookingstatus_enabled_new'] = 'Nachricht fuer Neu senden';
$string['bookingstatus_enabled_rejected'] = 'Nachricht fuer Abgelehnt senden';
$string['bookingstatus_notification_body_default'] = 'Die Buchungsanfrage "###EVENTNAME###" wurde auf "###BOOKINGSTATUS###" gesetzt.'
    . "\n\n"
    . 'Raum: ###ROOM###'
    . "\n"
    . 'Beginn: ###STARTTIME###'
    . "\n"
    . 'Ende: ###ENDTIME###'
    . "\n"
    . 'Buchende Person: ###BOOKINGPERSON###'
    . "\n"
    . 'Verantwortliche Person: ###PERSONINCHARGE###'
    . "\n"
    . 'Weitere Prüfende: ###OTHEREXAMINERS###'
    . "\n\n"
    . 'Buchung öffnen: ###EVENTURL###';
$string['bookingstatus_notification_closing'] = "Freundliche Gruesse\nIhr BookIt-Team";
$string['bookingstatus_notification_greeting'] = 'Guten Tag,';
$string['bookingstatus_notifications_desc'] = 'Empfaenger und Nachrichtenvorlagen fuer Aenderungen am Buchungsstatus konfigurieren.';
$string['bookingstatus_notifications_heading'] = 'Benachrichtigungen zum Buchungsstatus';
$string['bookingstatus_notify_bookingperson'] = 'Buchende Person benachrichtigen';
$string['bookingstatus_notify_bookingperson_desc'] = 'Sendet Statusänderungen an die Person, die die Buchungsanfrage erstellt hat.';
$string['bookingstatus_notify_otherexaminers'] = 'Weitere Prüfende benachrichtigen';
$string['bookingstatus_notify_otherexaminers_desc'] = 'Sendet Statusänderungen an alle weiteren Prüfenden der Buchungsanfrage.';
$string['bookingstatus_notify_personincharge'] = 'Verantwortliche Person benachrichtigen';
$string['bookingstatus_notify_personincharge_desc'] = 'Sendet Statusänderungen an die verantwortliche Person.';
$string['bookingstatus_notify_serviceteam'] = 'Service-Team benachrichtigen';
$string['bookingstatus_notify_serviceteam_desc'] = 'Sendet Statusaenderungen an Nutzerinnen und Nutzer mit der Service-Team-Capability in dieser BookIt-Aktivitaet.';
$string['bookingstatus_service_addresses'] = 'Universitäre Service-Adressen';
$string['bookingstatus_service_addresses_desc'] = 'Optionale gemeinsame Service-Adressen, die Statusaenderungen zusaetzlich zu den Service-Team-Nutzern erhalten sollen (getrennt durch Komma, Semikolon oder Leerzeichen).';
$string['bookingstatus_service_recipient_firstname'] = 'Universität';
$string['bookingstatus_service_recipient_lastname'] = 'Service';
$string['bookingstatus_subject_canceled'] = 'Betreff für Storniert';
$string['bookingstatus_subject_confirmed'] = 'Betreff für Bestätigt';
$string['bookingstatus_subject_default_canceled'] = 'Buchungsanfrage storniert: ###EVENTNAME### am ###BOOKINGDATE###';
$string['bookingstatus_subject_default_confirmed'] = 'Buchungsanfrage bestätigt: ###EVENTNAME### am ###BOOKINGDATE###';
$string['bookingstatus_subject_default_inprogress'] = 'Buchungsanfrage in Bearbeitung: ###EVENTNAME### am ###BOOKINGDATE###';
$string['bookingstatus_subject_default_new'] = 'Buchungsanfrage eingegangen: ###EVENTNAME### am ###BOOKINGDATE###';
$string['bookingstatus_subject_default_rejected'] = 'Buchungsanfrage abgelehnt: ###EVENTNAME### am ###BOOKINGDATE###';
$string['bookingstatus_subject_desc'] = 'Unterstuetzte Platzhalter: ###EVENTNAME### und ###BOOKINGDATE###. Weitere unterstuetzte Platzhalter: ###BOOKINGSTATUS###, ###OLDBOOKINGSTATUS###, ###EVENTURL###, ###ROOM###, ###STARTTIME###, ###ENDTIME###, ###BOOKINGPERSON###, ###PERSONINCHARGE###, ###OTHEREXAMINERS###.';
$string['bookingstatus_subject_inprogress'] = 'Betreff für In Bearbeitung';
$string['bookingstatus_subject_new'] = 'Betreff für Neu';
$string['bookingstatus_subject_rejected'] = 'Betreff für Abgelehnt';
$string['bookit:addevent'] = 'Event hinzufuegen';
$string['bookit:addinstance'] = 'BookIt Instanz hinzufügen';
$string['bookit:editevent'] = 'Event bearbeiten';
$string['bookit:editinternal'] = 'Internes Feld bearbeiten';
$string['bookit:filterstatus'] = 'Status filtern';
$string['bookit:managebasics'] = 'Die grundlegenden BookIt-Einstellungen verwalten.';
$string['bookit:managemasterchecklist'] = 'Master-Checkliste anzeigen und bearbeiten.';
$string['bookit:view'] = 'BookIt Instanz anzeigen';
$string['bookit:viewalldetailsofevent'] = 'Alle Details eines Events anzeigen';
$string['bookit:viewalldetailsofownevent'] = 'Alle Details des eigenen Events anzeigen';
$string['bookit:viewownoverview'] = 'Eigene Ereignisübersicht anzeigen';
$string['bookit:viewrestrictedobserver'] = 'Eingeschränkte Beobachteransicht anzeigen';
$string['calendar'] = 'Kalender';
$string['calendar_addbooking'] = 'Termin buchen';
$string['calendar_editevent'] = "Termin bearbeiten";
$string['calendar_eventlist'] = 'Liste';
$string['calendar_optional_fields'] = 'Aktive optionale Kalenderfelder';
$string['calendar_optional_fields_desc'] = 'Kommagetrennte Liste der optionalen Felder, die im gemeinsamen Buchungsprofil sichtbar bleiben. Unterstützte Schlüssel: timecompensation, compensationfordisadvantages, notes, refcourseid, coursetemplate.';
$string['calendar_profile_desc'] = 'Gemeinsame Third-Pass-Steuerung für Semesterbereich, Prüfenden-Pool und die optionalen Buchungsfelder, die für buchende Personen und Serviceteam gleich gelten.';
$string['calendar_profile_heading'] = 'Kalender-Buchungsprofil';
$string['category_collapseexpand'] = 'Einklappen/Ausklappen';
$string['category_deleted'] = 'Kategorie erfolgreich gelöscht';
$string['category_name'] = 'Kategoriename';
$string['category_name_required'] = 'Kategoriename ist erforderlich.';
$string['category_updated'] = 'Kategorie erfolgreich aktualisiert';
$string['checklist'] = 'Checkliste';
$string['checklist_duedate_days_after'] = '{$a} Tage nach der Veranstaltung';
$string['checklist_duedate_days_before'] = '{$a} Tage vor der Veranstaltung';
$string['checklistcategory'] = 'Checklisten-Kategorie';
$string['checklistcategory_help'] = 'Die Checklisten-Kategorie, zu der das Checklisten-Element gehört. Neue Elemente werden an die Kategorie angehängt und können anschließend verschoben werden.';
$string['checklistcategory_required'] = 'Die Checklisten-Kategorie ist erforderlich.';
$string['checklistcategorydeleted'] = 'Checklisten-Kategorie erfolgreich gelöscht.';
$string['checklistcategorysuccess'] = 'Checklisten-Kategorie erfolgreich erstellt.';
$string['checklistcategoryupdatesuccess'] = 'Checklisten-Kategorie erfolgreich aktualisiert.';
$string['checklistduedate_required'] = 'Eine Fälligkeitsoption ist erforderlich.';
$string['checklistitem'] = 'Checklisten-Element';
$string['checklistitemdeleted'] = 'Checklisten-Element erfolgreich gelöscht.';
$string['checklistitemname'] = 'Name des Checklisten-Elements';
$string['checklistitemname_help'] = 'Der Textinhalt des Checklisten-Elements, der auf der Checkliste angezeigt wird.';
$string['checklistitemname_required'] = 'Der Name des Checklisten-Elements ist erforderlich.';
$string['checklistitemnotfound'] = 'Checklisten-Element nicht gefunden';
$string['checklistitemsuccess'] = 'Checklisten-Element erfolgreich erstellt.';
$string['checklistitemupdatesuccess'] = 'Checklisten-Element erfolgreich aktualisiert.';
$string['checklistrole_required'] = 'Mindestens eine Rolle ist erforderlich.';
$string['checklistrooms_required'] = 'Mindestens ein Raum ist erforderlich.';
$string['chooseevent'] = 'Bitte wählen Sie mindestens ein Ereignis aus.';
$string['close_and_discard_changes'] = 'Schließen und Änderungen verwerfen';
$string['color'] = 'Farbe';
$string['could_not_parse_line'] = 'Zeile konnte nicht gelesen werden';
$string['could_not_parse_time_period_x'] = 'Zeitraum in Zeile {$a} konnte nicht gelesen werden';
$string['csv_format'] = 'CSV (Komma-getrennte Werte)';
$string['csvfile'] = 'CSV-Datei';
$string['customtemplate'] = 'Nachricht';
$string['customtemplate_help'] = 'Die benutzerdefinierte Nachrichtenvorlage für die Benachrichtigung. ';
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
$string['did_not_begin_with_weekday'] = 'Zeile beginnt nicht mit einem Wochentag';
$string['duedate'] = 'Fälligkeitsdatum';
$string['duedate_after_event'] = 'Nach Veranstaltung';
$string['duedate_before_event'] = 'Vor Veranstaltung';
$string['duedate_help'] = "Das Fälligkeitsdatum für die Fertigstellung des Checklisten-Elements. Muss einer von 'none', 'before' oder 'after' der Prüfung sein. Ein Offset in Tagen muss gesetzt werden, wenn die Optionen 'before' oder 'after' ausgewählt werden.";
$string['edit'] = 'Bearbeiten';
$string['edit_blocker'] = 'Blocker bearbeiten';
$string['edit_institution'] = 'Institution bearbeiten';
$string['edit_room'] = 'Raum bearbeiten';
$string['edit_room_data'] = 'Raumdaten bearbeiten';
$string['edit_weekplan'] = 'Wochenplan bearbeiten';
$string['edit_weekplan_assignment'] = 'Wochenplan-Zuweisung bearbeiten';
$string['end'] = 'Ende';
$string['end_before_start'] = 'Der Endzeitpunkt muss nach dem Anfangszeitpunkt liegen!';
$string['end_before_start_in_timeperiod_x'] = 'Ende liegt vor dem Start in Zeitraum {$a}';
$string['end_of_period'] = 'Ende des Zeitraums';
$string['error_amount_required'] = 'Anzahl ist erforderlich, wenn nicht als mengenneutral markiert.';
$string['error_category_name_exists'] = 'Eine Kategorie mit diesem Namen existiert bereits. Bitte wählen Sie einen anderen Namen.';
$string['error_category_not_found'] = 'Die ausgewählte Kategorie existiert nicht.';
$string['error_starttime_in_past'] = 'Die Startzeit kann nicht in der Vergangenheit sein.';
$string['event_bookingstatus'] = 'Buchungsstatus';
$string['event_bookingstatus_0'] = 'Neu';
$string['event_bookingstatus_1'] = 'In Bearbeitung';
$string['event_bookingstatus_2'] = 'Bestätigt';
$string['event_bookingstatus_3'] = 'Storniert';
$string['event_bookingstatus_4'] = 'Abgelehnt';
$string['event_bookingstatus_help'] = 'Erklärung der Buchungsstatus-Optionen.';
$string['event_cancel_only_notice'] = 'Diese Anfrage kann nicht mehr frei bearbeitet werden. Es steht nur noch die Stornierung zur Verfügung.';
$string['event_checklist:done'] = 'erledigt';
$string['event_checklist:go_to_resources'] = 'Veranstaltungsressourcen';
$string['event_checklist:progress'] = 'Checklisten-Fortschritt';
$string['event_checklist_heading'] = 'Checkliste für Veranstaltung: {$a}';
$string['event_checklist_no_items'] = 'Für diese Veranstaltung sind keine Checklisten-Einträge verfügbar.';
$string['event_checklist_title'] = 'Veranstaltungs-Checkliste';
$string['event_compensationfordisadvantages'] = 'Weitere Nachteilsausgleiche';
$string['event_compensationfordisadvantages_help'] = 'Tragen Sie hier die bereits bekannten Informationen zu Studierenden mit Nachteilsausgleich ein.';
$string['event_compensationfordisadvantages_label'] = 'Nachteile';
$string['event_department'] = 'Institution';
$string['event_department_help'] = 'Tragen Sie die beantragende Institution ein.';
$string['event_details'] = 'Veranstaltungsdetails';
$string['event_duration'] = 'Dauer des Termins (in Minuten)';
$string['event_duration_help'] = 'Tragen Sie die Dauer des Events ein.';
$string['event_error_mintime'] = 'Sie koennen keine Termine in der Vergangenheit anlegen.';
$string['event_extratime_description'] = '<i>Zusätzlich wird Zeit vor und nach dem Termin für Vor- und Nachbereitung automatisch hinzugefügt.</i>';
$string['event_extratime_label'] = '<i>Extra-Zeit fuer das Event</i>';
$string['event_internalnotes'] = 'Interne Hinweise';
$string['event_internalnotes_help'] = 'Diese Notizen sind nur für den internen Gebrauch bestimmt und werden der buchenden Person nicht angezeigt.';
$string['event_name'] = 'Termin Name';
$string['event_name_help'] = 'Tragen Sie den Namen des Termins ein.';
$string['event_notes'] = 'Anmerkungen';
$string['event_notes_help'] = 'Tragen Sie zusätzliche Informationen für den Support ein.';
$string['event_otherexaminers'] = 'Weitere Prüfende';
$string['event_otherexaminers_help'] = 'Wählen Sie weitere Prüfende für diese Prüfung aus.';
$string['event_past_participant_notice'] = 'Diese Buchung hat bereits begonnen und kann von Teilnehmenden nicht mehr geaendert werden.';
$string['event_personincharge'] = 'Verantwortliche Person';
$string['event_personincharge_help'] = 'Tragen Sie die verantwortliche Person ein.';
$string['event_reserved'] = 'Gebucht';
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
$string['event_supportperson'] = 'Support-Personen';
$string['event_supportperson_help'] = 'Diesem Event zugewiesene Support-Personen.';
$string['event_supportperson_internalnotes_notice'] = 'Support vor Ort darf „Support-Personen“ und „Interne Notizen“ bearbeiten; alle übrigen internen Felder sind schreibgeschützt.';
$string['event_timecompensation'] = 'Zeitnachteilsausgleich';
$string['event_timecompensation_help'] = 'Aktivieren, wenn Teilnehmende einen Zeitnachteilsausgleich haben.';
$string['event_usercreated'] = 'Angelegt von';
$string['event_usermodified'] = 'Zuletzt geaendert von';
$string['eventaudit_booking_reactivated'] = 'Buchung reaktiviert';
$string['eventaudit_booking_status_changed'] = 'Buchungsstatus geändert';
$string['examiner_display_unknown_user'] = 'Unbekannter Nutzer (ID {$a})';
$string['examiner_pool_invalid_assignment'] = 'Die ausgewählte Person gehört nicht zum konfigurierten Prüfenden-Pool.';
$string['examiner_pool_usernames'] = 'Usernames für den Prüfenden-Pool';
$string['examiner_pool_usernames_desc'] = 'Optionale kommagetrennte Liste von Moodle-Usernames, die als verantwortliche Person oder weitere Prüfende ausgewählt werden dürfen. Leer lassen, um alle aktiven Nutzer zuzulassen.';
$string['export'] = 'Export';
$string['export_error'] = 'Export fehlgeschlagen. Bitte versuchen Sie es erneut.';
$string['export_format'] = 'Exportformat';
$string['export_help'] = 'Sie können für den Export zwischen zwei Dateiformaten wählen. Nutzen Sie PDF, wenn Sie die Liste außerhalb des Systems zur Ansicht nutzen wollen. Nutzen Sie CSV, um eine Sicherungsdatei zu erzeugen oder die Checkliste in ein anderes System zu übertragen.';
$string['export_success'] = 'Export erfolgreich abgeschlossen';
$string['exportedon'] = 'Exportiert am: {$a}';
$string['exportevents'] = 'Ereignisse exportieren';
$string['exportevents_from'] = 'Von';
$string['exportevents_ics_bookingperson'] = 'Buchende Person';
$string['exportevents_ics_duration'] = 'Dauer';
$string['exportevents_ics_faculty'] = 'Fakultät';
$string['exportevents_ics_otherexaminers'] = 'Weitere Prüfende';
$string['exportevents_ics_participants'] = 'Teilnehmende';
$string['exportevents_ics_personincharge'] = 'Verantwortliche Person';
$string['exportevents_ics_requirements'] = 'Anforderungen';
$string['exportevents_ics_semester'] = 'Semester';
$string['exportevents_reset_range'] = 'Zeitraum zurücksetzen';
$string['exportevents_selectedcount'] = 'Ausgewählt: {$a}';
$string['exportevents_to'] = 'Bis';
$string['exportfailed'] = 'Export fehlgeschlagen: {$a}';
$string['filtercategories'] = 'Kategorien filtern';
$string['filters'] = 'Filter: ';
$string['filters:room_label'] = 'Raum:';
$string['from_x_onwards'] = 'Ab dem {$a}';
$string['global_blocker'] = 'Globaler Blocker';
$string['globally'] = 'Global';
$string['header_internal'] = 'Interne Felder';
$string['history_action_canceled'] = 'Storniert';
$string['history_action_confirmed'] = 'Auf Bestätigt geändert';
$string['history_action_created'] = 'Erstellt';
$string['history_action_moved_to_in_progress'] = 'In Bearbeitung verschoben';
$string['history_action_reactivated'] = 'Als neue Anfrage reaktiviert';
$string['history_action_rejected'] = 'Abgelehnt';
$string['history_action_restored'] = 'Wiederhergestellt';
$string['history_action_self_canceled'] = 'Vom Antragsteller storniert';
$string['history_action_updated'] = 'Aktualisiert';
$string['import'] = 'Import';
$string['import_help'] = 'Nutzen Sie für den Import eine Sicherungsdatei einer Checkliste in CSV-Format. Die in der CSV-Datei enthaltenen Checklistenpunkte und -Kategorien werden in Ihre Checkliste übernommen und werden unterhalb bestehender Punkte erstellt.';
$string['import_rooms'] = 'Räume importieren';
$string['import_rooms_desc'] = 'Wenn aktiviert, werden Raumzuweisungen aus der CSV importiert und Checklisten-Elementen zugeordnet. Wenn deaktiviert, haben Elemente keine Raumzuweisungen.';
$string['importfailed'] = 'Import fehlgeschlagen: {$a}';
$string['importsuccessful'] = 'Import erfolgreich: {$a} Elemente importiert';
$string['installation_heading'] = 'Rollen-Presets';
$string['installation_heading_desc'] = 'Installiert die mitgelieferten BookIt-Rollen oder stellt die XML-Presets zum Download bereit.';
$string['instancename'] = 'Name';
$string['institution'] = 'Institution';
$string['institution_active'] = 'Aktiv';
$string['institution_active_help'] = 'Wenn ja, können buchende Personen diese Institution auswählen.';
$string['institution_name'] = 'Name der Institution';
$string['institutionid_empty_notice'] = 'Es sind keine aktiven Institutionen vorhanden. Führen Sie zuerst den Install Helper aus.';
$string['institutions'] = 'Institutionen';
$string['internalnotes'] = 'Interne Hinweise';
$string['internalnotes_help'] = 'Diese Notizen sind nur für den internen Gebrauch bestimmt und werden der buchenden Person nicht angezeigt.';
$string['invalidchecklistitemid'] = 'Ungültige Checklisten-Element-ID';
$string['invalidcsvformat'] = 'Ungültiges CSV-Format. Bitte überprüfen Sie die Dateistruktur.';
$string['invalidformat'] = 'Ungültiges Exportformat angegeben';
$string['invalidweekday'] = 'Dieser Wochentag ist für Buchungen nicht erlaubt.';
$string['item_created'] = 'Ressource erfolgreich erstellt';
$string['item_deleted'] = 'Ressource erfolgreich gelöscht';
$string['item_updated'] = 'Ressource erfolgreich aktualisiert';
$string['legend'] = 'Legende';
$string['line_x'] = 'Zeile {$a}';
$string['local_blocker'] = 'Lokaler Blocker (nur für diesen Raum)';
$string['location'] = 'Ort';
$string['master_checklist'] = 'Haupt-Checkliste';
$string['messageprovider:bookit_booking_status_changed'] = 'Benachrichtigung bei Aenderung des Buchungsstatus';
$string['messageprovider:bookit_resource_status_changed'] = 'Benachrichtigung bei Aenderung des Ressourcenstatus';
$string['missing_role'] = 'Fehlende Rolle';
$string['missingdata'] = 'Fehlende erforderliche Daten für Import';
$string['modulename'] = 'Kalender';
$string['modulename_help'] = 'Kalender ist ein PlugIn für die Buchung von Services, Prüfungsterminen, Räumen oder anderer Resscourcen.';
$string['modulenameplural'] = 'Kalender-Aktivitäten';
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
$string['nobookitinstances'] = 'In diesem Kurs gibt es keine BookIt-Instanzen.';
$string['nocontent'] = 'Keine Haupt-Checklisten-Kategorien gefunden. Erstellen Sie die erste Kategorie!';
$string['noduedate'] = 'Kein Fälligkeitsdatum';
$string['noevents'] = 'Keine Ereignisse in der aktuellen Ansicht.';
$string['nofileselected'] = 'Keine Datei für den Import ausgewählt';
$string['normal_slot'] = 'Normaler Slot';
$string['notification_time'] = 'Zeit';
$string['notification_time_help'] = 'Der Zeitversatz in Tagen in Bezug auf die Prüfung, wann die Benachrichtigung gesendet werden soll.';
$string['notifications'] = 'Benachrichtigungen';
$string['observer_empty_state'] = 'Keine bestätigten Buchungen für Ihre Rolle sichtbar.';
$string['observer_export_denied'] = 'Der Event-Export ist fuer Ihre Rolle nicht verfuegbar.';
$string['observer_no_detail_access'] = 'Details sind in dieser Ansicht nicht verfügbar.';
$string['optional_checklist_enabled'] = 'Checklisten-Modul aktivieren';
$string['optional_checklist_enabled_desc'] = 'Erlaubt Administratoren und Service-Team-Rollen die Nutzung der Checklisten- und Haupt-Checklisten-Funktionen. Für reine Kalender-Installationen deaktiviert lassen.';
$string['optional_part_disabled'] = 'Dieser BookIt-Modulteil ist in den General Settings aktuell deaktiviert.';
$string['optional_plugin_parts_desc'] = 'Frische Installationen starten im reinen Kalender-Modus. Aktivieren Sie Ressourcen oder Checkliste hier nur bei Bedarf.';
$string['optional_plugin_parts_heading'] = 'Optionale Plugin-Teile';
$string['optional_resources_enabled'] = 'Ressourcen-Modul aktivieren';
$string['optional_resources_enabled_desc'] = 'Erlaubt Administratoren und Service-Team-Rollen die Nutzung des Ressourcen-Katalogs und der Ressourcen-Workflows an Terminen. Für reine Kalender-Installationen deaktiviert lassen.';
$string['overdue'] = 'Erinnerung bei Überfälligkeit';
$string['overlapping_allow_all'] = 'Überschneidung von Terminen erlauben';
$string['overlapping_allow_none'] = 'Überschneidung von Terminen nicht erlauben';
$string['overlapping_mode'] = 'Soll Überschneidung von Terminen verhindert werden?';
$string['overlapping_non_confirmed'] = 'Überschneidung von nicht bestätigten Terminen erlauben';
$string['overview'] = 'Meine gebuchten Ereignisse';
$string['overview_all_events'] = 'Alle Buchungen';
$string['overview_all_requests'] = 'Alle Anfragen';
$string['overview_apply_filters'] = 'Filter anwenden';
$string['overview_cancel_booking'] = 'Buchung stornieren';
$string['overview_cancel_booking_confirm'] = 'Buchung stornieren?';
$string['overview_cancel_booking_confirm_body'] = 'Ihre Buchungsanfrage wird storniert. Sie finden den Eintrag später im Verlauf.';
$string['overview_cancel_column'] = 'Aktionen';
$string['overview_column_bookingstatus'] = 'Buchungsstatus';
$string['overview_column_checklist'] = 'Checkliste';
$string['overview_column_date'] = 'Datum';
$string['overview_column_datetime'] = 'Datum und Uhrzeit';
$string['overview_column_id'] = 'ID';
$string['overview_column_myrole'] = 'Meine Rolle';
$string['overview_column_personincharge'] = 'Verantwortliche Person';
$string['overview_column_progress'] = 'Fortschritt';
$string['overview_column_resources'] = 'Ressourcen';
$string['overview_column_room'] = 'Raum';
$string['overview_column_title'] = 'Titel';
$string['overview_confirmed_requests'] = 'Bestätigte Buchungen';
$string['overview_confirmed_requests_empty'] = 'Aktuell liegen keine bestätigten Buchungen in diesem Workspace vor.';
$string['overview_count'] = '{$a} Ereignisse';
$string['overview_filter_all_faculties'] = 'Alle Fakultäten';
$string['overview_filter_assignment'] = 'Zuweisung';
$string['overview_filter_assignment_all'] = 'Alle';
$string['overview_filter_assignment_assigned'] = 'Mir zugewiesen';
$string['overview_filter_enddate'] = 'Enddatum';
$string['overview_filter_faculty'] = 'Fakultät';
$string['overview_filter_startdate'] = 'Startdatum';
$string['overview_filter_status'] = 'Buchungsstatus';
$string['overview_history'] = 'Historie';
$string['overview_my_events'] = 'Meine gebuchten Ereignisse';
$string['overview_nav_badge_inprogress'] = '{$a} Anfragen in Bearbeitung';
$string['overview_nav_badge_new'] = '{$a} neue Anfragen';
$string['overview_no_results'] = 'Zu den gewählten Filtern wurden keine Buchungen gefunden.';
$string['overview_open_requests'] = 'Offene Anfragen';
$string['overview_open_requests_empty'] = 'Aktuell liegen keine offenen Buchungsanfragen vor.';
$string['overview_reactivate_booking_confirm'] = 'Als neue Anfrage reaktivieren?';
$string['overview_reactivate_booking_confirm_body'] = 'Die Buchung wird erneut als neue Anfrage eingereicht.';
$string['overview_reactivate_column'] = 'Reaktivieren';
$string['overview_rejected_cancelled_requests'] = 'Abgelehnt und storniert';
$string['overview_rejected_requests_empty'] = 'Aktuell liegen keine abgelehnten oder vom Buchenden stornierten Buchungsanfragen in der Papierkorb-Queue vor.';
$string['overview_request_workspace'] = 'Anfragen-Arbeitsbereich';
$string['overview_request_workspace_switch'] = 'Anfragen-Warteschlangen';
$string['overview_reset_filters'] = 'Filter zurücksetzen';
$string['overview_role_bookingperson'] = 'Buchende Person';
$string['overview_role_otherexaminer'] = 'Weitere prüfende Person';
$string['overview_role_personincharge'] = 'Verantwortliche Person';
$string['overview_role_supportperson'] = 'Support-Person';
$string['overview_status_group_closed'] = 'Abgeschlossen / nicht bestätigt';
$string['overview_status_group_confirmed'] = 'Bestätigte Buchung';
$string['overview_status_group_open'] = 'Offene Anfrage';
$string['overview_workflow_history'] = 'Workflow-Historie';
$string['overview_workflow_history_change'] = '{$a->field}: {$a->from} -> {$a->to}';
$string['overview_workflow_history_empty'] = 'Es wurden noch keine Workflow-Eintraege protokolliert.';
$string['overview_workflow_history_recovery'] = 'Die Wiederherstellung nutzt den letzten gueltigen Workflow-Status.';
$string['overwrite_extratimeafter'] = 'Globale extratimeafter-Einstellung überschreiben?';
$string['overwrite_extratimebefore'] = 'Globale extratimebefore-Einstellung überschreiben?';
$string['pdf_format'] = 'PDF (Portable Document Format)';
$string['pdf_title'] = 'PDF Titel';
$string['pdf_title_help'] = 'Geben Sie einen benutzerdefinierten Titel für das PDF-Dokument ein. Dieser Titel erscheint in der Kopfzeile der exportierten PDF-Datei. Falls leer gelassen, wird der Name der Master-Checkliste als Standard-Titel verwendet.';
$string['period'] = 'Zeitraum';
$string['pluginadministration'] = 'BookIt Administration';
$string['pluginname'] = 'BookIt';
$string['recipient'] = 'Empfänger';
$string['recipient_help'] = 'Der Empfänger der Benachrichtigung.';
$string['reset'] = 'Zurücksetzen';
$string['resetmessagetoconfirm'] = 'Sind Sie sicher, dass Sie die Nachricht auf die Standardvorlage zurücksetzen möchten? Ihre Änderungen werden gelöscht.';
$string['resource'] = 'Ressource';
$string['resources'] = 'Ressourcen';
$string['resources:active'] = 'Aktiv';
$string['resources:active_help'] = 'Nur aktive Ressourcen sind für Buchungen verfügbar.';
$string['resources:add_category'] = 'Kategorie hinzufügen';
$string['resources:add_resource'] = 'Ressource hinzufügen';
$string['resources:add_resource_no_categories'] = 'Bitte zuerst eine Kategorie anlegen';
$string['resources:amount'] = 'Anzahl';
$string['resources:amount_help'] = 'Die Anzahl der verfügbaren Einheiten dieser Ressource.';
$string['resources:amount_must_be_positive'] = 'Die Anzahl muss eine positive Zahl sein.';
$string['resources:amount_unlimited'] = 'Unbegrenzt';
$string['resources:amountirrelevant'] = 'Anzahl irrelevant';
$string['resources:amountirrelevant_help'] = 'Aktivieren Sie dies, wenn die Ressource keine spezifische Menge hat (z.B. WLAN, Whiteboard).';
$string['resources:category'] = 'Kategorie';
$string['resources:category_has_resources'] = 'Zum Löschen darf keine Ressource der Kategorie zugeordnet sein. Ordnen Sie die entsprechenden Ressourcen anderen Kategorien zu oder löschen Sie zuvor die Ressourcen in dieser Kategorie.';
$string['resources:category_help'] = 'Wählen Sie die Kategorie für diese Ressource aus.';
$string['resources:category_not_found'] = 'Die ausgewählte Ressourcenkategorie wurde nicht gefunden.';
$string['resources:category_required'] = 'Ressourcenkategorie ist erforderlich.';
$string['resources:customtemplatedefaultmessage_before_due'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### ist am ###DATE### fällig. Bitte stellen Sie sicher, dass sie rechtzeitig vorbereitet wird.';
$string['resources:customtemplatedefaultmessage_overdue'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### ist überfällig. Bitte handeln Sie umgehend.';
$string['resources:customtemplatedefaultmessage_when_done'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### wurde als erledigt markiert.';
$string['resources:customtemplatedefaultmessage_when_due'] = 'Sehr geehrte/r ###RECIPIENT###, die Ressource ###ITEM### ist heute fällig. Bitte bestätigen Sie ihre Verfügbarkeit.';
$string['resources:description'] = 'Beschreibung';
$string['resources:description_help'] = 'Optionale Beschreibung für die Ressource oder Kategorie.';
$string['resources:edit_category'] = 'Kategorie bearbeiten';
$string['resources:edit_resource'] = 'Ressource bearbeiten';
$string['resources:filter_no_resources'] = 'Bitte zuerst eine Kategorie und Ressourcen anlegen.';
$string['resources:info'] = 'Ressourcen-Information';
$string['resources:internalinfo'] = 'Interne Informationen';
$string['resources:internalinfo_help'] = 'Interne Hinweise, die nur für Administratoren sichtbar sind.';
$string['resources:invalid_status'] = 'Ungültiger Statuswert.';
$string['resources:name'] = 'Name';
$string['resources:name_help'] = 'Der Name der Ressource oder Kategorie.';
$string['resources:name_required'] = 'Ressourcenname ist erforderlich.';
$string['resources:no_categories'] = 'Noch keine Kategorien';
$string['resources:no_rooms'] = 'Noch keine Räume angelegt.';
$string['resources:no_rooms_link'] = 'Räume anlegen';
$string['resources:notfound'] = 'Ressource nicht gefunden';
$string['resources:notification_status_changed_body'] = 'Der Status der Ressource "{$a->resourcename}" für die Veranstaltung "{$a->eventname}" wurde aktualisiert auf: {$a->statuslabel}.';
$string['resources:notification_status_changed_subject'] = 'Ressourcenstatus aktualisiert: {$a->eventname}';
$string['resources:open_settings'] = 'Ressourcen-Checklisten-Einstellungen';
$string['resources:overview'] = 'Ressourcen-Übersicht';
$string['resources:resource'] = 'Ressource';
$string['resources:rooms'] = 'Räume';
$string['resources:rooms_help'] = 'Wählen Sie Räume aus, in denen diese Ressource verfügbar ist. Leer lassen, wenn die Ressource in allen Räumen verfügbar ist. Mehrere Räume können durch Halten von CTRL ausgewählt werden.';
$string['resources:settings_column'] = 'Einstellungen';
$string['resources:settings_saved'] = 'Einstellungen erfolgreich gespeichert.';
$string['resources:status'] = 'Ressourcenstatus';
$string['responsibility'] = 'Verantwortlichkeit';
$string['role'] = 'Rolle';
$string['role_help'] = 'Diese Rollen werden dem Checklisten-Element zugewiesen und sind für die Ausführung verantwortlich. Mehrere Rollen können durch Halten von STRG ausgewählt werden.';
$string['rolepreset_bookingperson'] = 'Buchungsperson';
$string['rolepreset_bookingperson_help'] = 'Buchungsperson: Eine Person, die eine Anfrage für eine E-Prüfung stellt (Prüfer, Prüfungsämter, studentisches Hilfs personal, wissenschaftliches Personal, ...).';
$string['rolepreset_examiner'] = 'Prüfer';
$string['rolepreset_examiner_help'] = 'Prüfer: Eine Person, die einer Universität angehört oder mit dieser verbunden ist und Studierende prüft.';
$string['rolepreset_observer'] = 'Beobachter';
$string['rolepreset_observer_help'] = 'Beobachter: Eine Person, die Veranstaltungen nur einsehen kann (z. B. Raumentwicklungs verwaltung, Prüfungsamt, Dekanat, Serviceteam einer anderen Universität).';
$string['rolepreset_serviceteam'] = 'Service-Team';
$string['rolepreset_serviceteam_help'] = 'Service-Team: Eine Person, die im E-Assessment-Service arbeitet und Prüfungs buchungen verwaltet.';
$string['rolepreset_supportonsite'] = 'Vor-Ort-Support';
$string['rolepreset_supportonsite_help'] = 'Vor-Ort-Support: Eine Person, die eine Prüfung vor Ort unterstützt.';
$string['rolepresetdownloads'] = 'Downloads der Rollen-Presets';
$string['room'] = 'Raum';
$string['room_active'] = 'Aktiv';
$string['room_doesnt_have_enough_seats'] = 'Der gewählte Raum hat nicht genügend Plätze für die angegebene Anzahl an Teilnehmenden.';
$string['roomid_empty_notice'] = 'Es sind keine aktiven Räume vorhanden. Führen Sie zuerst den Install Helper aus.';
$string['roommode'] = 'Raummodus';
$string['roommode_free'] = 'Freie Auswahl innerhalb der Slots';
$string['roommode_slots'] = 'Buchungen können nur an Anfängen von Slots starten';
$string['roommode_top_to_bottom'] = 'Tage nur von früh nach spät füllen';
$string['rooms'] = 'Räume';
$string['rooms_help'] = 'Diese Räume werden dem Checklisten-Element zugewiesen. Mehrere Räume können durch Halten von STRG ausgewählt werden.';
$string['runinstallhelper'] = 'Install Helper ausfuehren';
$string['runinstallhelper_ready_state'] = 'Der Install Helper wurde bereits erfolgreich ausgeführt.';
$string['runinstallhelper_result_failed'] = 'Der Install Helper ist fehlgeschlagen. Importierte Rollen: {$a->rolesimported}; angelegte Baseline-Objekte: {$a->baselinecreated}; Fehler: {$a->errors}.';
$string['runinstallhelper_result_idempotent'] = 'Der Install Helper hat die vorhandene Konfiguration bestätigt. Beibehaltene Rollen-Presets: {$a->rolesskipped}; verifizierte Baseline-Objekte: {$a->baselineverified}.';
$string['runinstallhelper_result_partial'] = 'Der Install Helper wurde nur teilweise abgeschlossen. Importierte Rollen: {$a->rolesimported}; beibehaltene Presets: {$a->rolesskipped}; angelegte Baseline-Objekte: {$a->baselinecreated}; verifizierte Baseline-Objekte: {$a->baselineverified}; Fehler: {$a->errors}.';
$string['runinstallhelper_result_success'] = 'Der Install Helper wurde erfolgreich ausgeführt. Importierte Rollen: {$a->rolesimported}; angelegte Baseline-Objekte: {$a->baselinecreated}.';
$string['runinstallhelperinfo'] = 'Erstellt bei Bedarf die Standard-Kalender-Basis und importiert die mitgelieferten Rollen-Presets.';
$string['seats'] = 'Anzahl an Plätzen';
$string['select_semester'] = 'Semester';
$string['select_semester_help'] = 'Wählen Sie das Semester aus, in dem der Termin stattfindet';
$string['selectevents'] = 'Bitte markieren Sie die zu exportierenden Ereignisse:';
$string['semesterlookaheadyears'] = 'Jahre zukünftiger Semester';
$string['semesterlookaheadyears_desc'] = 'Wie viele zukünftige Kalenderjahre im Semester-Selektor angeboten werden sollen.';
$string['semesterlookbackyears'] = 'Jahre vergangener Semester';
$string['semesterlookbackyears_desc'] = 'Wie viele vergangene Kalenderjahre im Semester-Selektor angeboten werden sollen.';
$string['settings_eventdefaultduration'] = 'Default duration of an event (min)';
$string['settings_eventdurationstepwidth'] = 'The step width for the duration of an event (min)';
$string['settings_eventmaxduration'] = 'Maximum duration of an event (min)';
$string['settings_eventmaxyear'] = 'Maximales Jahr fuer Eventauswahl';
$string['settings_eventmaxyear_desc'] = 'Set the maxmum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventminyear'] = 'Minimales Jahr fuer Eventauswahl';
$string['settings_eventminyear_desc'] = 'Set the minimum year to select for event. Note: this only applies to roles with the capability <code>caneditinternal</code>.';
$string['settings_eventyear_current'] = 'Aktuelles Jahr';
$string['settings_eventyear_minus1'] = '-1 Jahr';
$string['settings_eventyear_minus2'] = '-2 Jahre';
$string['settings_eventyear_plus1'] = '+1 Jahr';
$string['settings_eventyear_plus2'] = '+2 Jahre';
$string['settings_eventstartstepwidth'] = 'Schrittweite fuer Startzeit';
$string['settings_extratime_after'] = 'Zusätzliche Zeit nach dem Termin (min)';
$string['settings_extratime_after_desc'] = 'Zusätzliche Zeit in Minuten, die automatisch nach dem Termin hinzuaddiert wird, um Nacharbeiten zu erlauben.';
$string['settings_extratime_before'] = 'Zusätzliche Zeit vor dem Termin (min)';
$string['settings_extratime_before_desc'] = 'Zusätzliche Zeit in Minuten, die automatisch vor dem Termin hinzuaddiert wird, um Vorarbeiten zu erlauben.';
$string['settings_general'] = 'Allgemein';
$string['settings_master_checklist_desc'] = 'Konfiguration der Master-Checkliste';
$string['settings_overview'] = 'Einstellungsübersicht';
$string['settings_pdf_logo_custom'] = 'Benutzerdefiniertes PDF-Logo';
$string['settings_pdf_logo_custom_desc'] = 'Laden Sie ein benutzerdefiniertes Logo hoch, das in PDF-Checklisten verwendet wird, wenn oben "Benutzerdefiniertes Logo" ausgewählt ist. Unterstützte Formate: PNG, JPG, JPEG. Optimale Breite: 200-400px.';
$string['settings_pdf_logo_enable'] = 'Logo in PDF-Checkliste aktivieren';
$string['settings_pdf_logo_enable_desc'] = 'Ein Logo im Kopf der exportierten PDF-Checklisten anzeigen.';
$string['settings_pdf_logo_source'] = 'Logo-Quelle';
$string['settings_pdf_logo_source_custom'] = 'Benutzerdefiniertes Logo';
$string['settings_pdf_logo_source_desc'] = 'Wählen Sie die Quelle für das in PDF-Checklisten angezeigte Logo.';
$string['settings_pdf_logo_source_site'] = 'Site-Logo (core_admin | logo)';
$string['settings_pdf_logo_source_theme'] = 'Theme-Logo (theme_boost_union | logo)';
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
$string['summer_semester'] = 'Sommersemester';
$string['time'] = 'Zeit';
$string['time_help'] = "Wenn das Fälligkeitsdatum für das Checklisten-Element 'before' oder 'after' ist, definiert diese Einstellung, wie viele Tage vor oder nach der Prüfung das Checklisten-Element abgeschlossen werden soll.";
$string['tools'] = 'Werkzeuge';
$string['weekplan'] = 'Wochenplan';
$string['weekplan_assignment_overlaps'] = 'Der eingegebene Zeitraum überschneidet sich mit einer bereits bestehenden Wochenplan-Zuweisung.';
$string['weekplan_assignments'] = 'Wochenplan-Zuweisungen';
$string['weekplan_help'] = 'Der Wochenplan definiert die verfügbaren Zeitslots und Raumzuweisungen für dieses Semester.';
$string['weekplans'] = 'Wochenpläne';
$string['when_done'] = 'Wenn erledigt';
$string['when_due'] = 'Wenn fällig';
$string['winter_semester'] = 'Wintersemester';
