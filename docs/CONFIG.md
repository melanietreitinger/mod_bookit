# BookIt Admin Einstellungen

Nach der Installation erscheint ein neuer Menüpunkt in der Hauptnavigation von Moodle:

![Screenshot](TODO)

Die Konfiguration ist ebenfalls über Plugins → Aktivitäten erreichbar:

![Screenshot](TODO)

Die Admin-Einstellungen von BookIt sind in folgende Bereiche unterteilt:
- Kalender 
- Veranstaltungen 
- Eigene Buchungsfelder 
- Räume 
- Wochenpläne 
- Institutionen 
- Benachrichtigungen 
- Allgemeine Einstellungen

Die Bereiche Kalender, Räume, Wochenpläne und Institutionen können über die Berechtigung `mod/bookit:managebasics` auch von der 
Rolle „Service Team“ verwaltet werden (Rolle in System-Kontext vorausgesetzt).

## Allgemeine Einstellungen (nur Site-Admins)
Der Bereich „Allgemeine Einstellungen“ ist nur für Administratoren (Site-Admins) zugänglich.

Nach der Installation sollten zuerst die **BookIt-Rollen** installiert werden.

Ausführliche Informationen zu den BookIt-Rollen finden sich hier: [BookIt-Rollen-Dokumentation](ROLES.md)

![Screenshot](TODO)

> [!NOTE]
> Die Rolle „Service-Team“ muss auf System-Ebene vergeben werden („Assign system roles“), 
> damit diese Rolle auch Einstellungen für BookIt vornehmen kann.

## Kalender
Hier werden Einstellungen für den Kalender vorgenommen:

|  Einstellung           | Config key      | Standard-Werte |
|------------------------|-----------------|----------------|
| Wochentage im Kalender | weekdaysvisible | Mo – Fr        | 

## Veranstaltungen
Hier werden Einstellungen für das Buchungsformular vorgenommen:

| Einstellung                          | Config key               | Standard-Werte                                                                                  |
|--------------------------------------|--------------------------|-------------------------------------------------------------------------------------------------|
| Optionale Felder                     | `calendar_optional_fields` | Semester, Institution, Weitere Prüfende,<br> Nachteilsausgleiche, Anmerkungen, Interne Hinweise |
| Minimales Jahr fuer Terminauswahl    | `eventminyear`             | ein Jahr zurück                                                                                 |
| Maximales Jahr fuer Terminauswahl    | `eventmaxyear`             | ein Jahr voraus                                                                                 |
| Standarddauer für einen Termin       | `eventdefaultduration`     | 60 Min                                                                                          |
| Maximale Dauer für einen Termin      | `eventmaxduration`         | 480 Min                                                                                         |
| Abstand für Terminauswahl            | `eventdurationstepwidth`   | 5 Min                                                                                           |
| Schrittweite fuer Startzeit          | `eventstartstepwidth`      | 5 Min                                                                                           |
| Zusätzliche Zeit vor einem Termin    | `extratimebefore`          | 15 Min                                                                                          |
| Zusätzliche Zeit nach einem Termin   | `extratimeafter`           | 15 Min                                                                                          |
| Usernamen für den Prüfenden-Pool     | `examiner_pool_usernames`  | leer                                                                                            |

## Eigene Buchungsfelder

Es ist möglich, eigene Felder für das Buchungsformular zu definieren.

Dafür wird die [Custom fields API](https://moodledev.io/docs/5.2/apis/core/customfields) genutzt.

Beispiel:

![Screenshot](TODO)

![Screenshot](TODO)

Ansicht im Buchungsformular:

![Screenshot](TODO)

## Räume

Nach der Installation ist in BookIt bereits **ein Standard-Raum mit einem Standard-Wochenplan** angelegt.

> [!NOTE]
> Für die Nutzung von BookIt muss grundsätzlich ein Raum vorhanden sein, dem ein aktiver Wochenplan zugeordnet ist, damit man Termine buchen kann.

![Screenshot](TODO)

**Der Standard-Raum hat folgende Eigenschaften**:

- Anzahl an Plätzen: 0 (=unbegrenzt)
- Raummodus: „Freie Auswahl innerhalb der Slots“
- Überschneidung von Terminen: nicht erlaubt


**Alle Raumeinstellungen im Überblick:**

| Einstellung                                        | Standard-Werte                                                                                   |
|----------------------------------------------------|--------------------------------------------------------------------------------------------------|
| Name                                               | Angezeigter Name im Termin-Formular                                                              |
| Kurzname                                           | Angezeigtes Kürzel im Kalender, max. 6 Zeichen                                                   | 
| Anzahl an Plätzen                                  | Die Anzahl der Teilnehmer ist an die verfügbaren Plätze gekoppelt. „0“ für unbegrenzt eintragen. | 
| Beschreibung                                       | Beschreibung des Raums, wird im Raumsteckbrief angezeigt                                         | 
| Ort                                                | Hier kann man Gebäude und Raumnummer eintragen                                                   | 
| Farbe                                              | Farbe von Terminen in diesem Raum im Kalender                                                    | 
| Raummodus                                          | Siehe extra Beschreibung                                                                         | 
| Überschneidung von Terminen                        | Siehe extra Beschreibung                                                                         | 
| Globale extratimebefore-Einstellung überschreiben? | Überschreibt die globale zusätzliche Zeit vor dem Termin für diesen Raum                         | 
| Globale extratimeafter-Einstellung überschreiben?  | Überschreibt die globale zusätzliche Zeit nach dem Termin für diesen Raum                        | 
| Aktiv                                              | Macht den Raum für die Terminauswahl verfügbar                                                   | 


### Raum-Modi

| Englisch                                          | Deutsch                                                | Bedeutung                                                |
|---------------------------------------------------|--------------------------------------------------------|----------------------------------------------------------| 
| Free selection inside slots                       | Freie Auswahl in den Zeitslots                         | Buchungen können zu jedem beliebigen Zeitpunkt starten   |
| Bookings can only start at the beginning of slots | Buchungen können nur am Anfang eines Zeitslots starten | Es ist nur eine Buchung für einen Zeitslots möglich.     |
| Only fill days top to bottom                      | Tage immer von oben nach unten füllen E                | s ist immer nur der nächstmögliche Zeitpunkt auswählbar. |

**Freie Auswahl in den Zeitslots**
- Es können beliebige Startzeitpunkte innerhalb der definierten Buchungszeiten (Wochenplan) gewählt werden.
- Die Dauer ist ebenfalls frei wählbar und wird durch eventmaxduration begrenzt.

**Buchungen können nur am Anfang eines Zeitslots starten (= Feste Slots)**
- Es können nur die in den Wochenplänen fest definierten Zeitslots gebucht werden.
- Beispiel: ein Hörsaal kann nur in festgelegten 2-Stunden-Slots belegt werden.
- Das bedeutet, dass auch der dazugehörige Wochenplan entsprechend in Zeitslots aufgeteilt sein muss, damit dieser Modus sinnvoll genutzt werden kann.

**Tage immer von oben nach unten füllen (= Aufeinanderfolgende Buchungen)**
- Die Prüfungen sind in ihrer Dauer flexibel, aber eine Prüfung muss sich nahtlos an das Ende der vorherigen Prüfung (bzw. deren Abrüstzeit) anschließen oder es muss die erste Prüfung des Tages sein. 
- Damit kann man einen Raum möglichst effizient ausnutzen.

### Überschneidung von Terminen

| Englisch                                    | Deutsch                                                | Bedeutung                                                                                                                                 |
|---------------------------------------------|--------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------| 
| Allow no overlaps                           | Überschneidung von Terminen nicht erlauben             | Es können keine Termine gebucht werden, wenn zu diesem Zeitpunkt bereits ein Termin eingetragen ist (egal welcher Status).                |
| Allow all overlapping events                | Überschneidung von Terminen erlauben                   | Es können Termine gebucht werden, auch wenn zu diesem Zeitpunkt bereits ein Termin eingetragen ist.                                       |
| Allow overlapping with non-confirmed events | Überschneidung von nicht bestätigten Terminen erlauben | Es können keine Termine gebucht werden, wenn zu diesem Zeitpunkt bereits ein Termin eingetragen ist mit dem Status „confirmed/bestätigt“. |


## Wochenpläne

In BookIt lassen sich Wochenpläne definieren, die festlegen, in welchen Zeiträumen überhaupt Buchungen stattfinden können.

Das bedeutet, dass jedem Raum ein Wochenplan zugewiesen werden muss, damit er gebucht werden kann.

![Screenshot](TODO)

Nach der Installation ist in BookIt bereits ein Standard-Raum mit einem Standard-Wochenplan angelegt.

**Der Standard-Wochenplan ist wie folgt definiert:**
- Mo – Fr 9:00-17:00
- Dauer: unbegrenzt

![Screenshot](TODO)

### Gültigkeit
Wochenpläne können unbegrenzt oder für einen bestimmten Zeitraum gültig sein.

Ohne einen Wochenplan, der zum gewünschten Zeitraum aktiv ist, lassen sich keine Buchungen vornehmen!

![Screenshot](TODO)

### Blocker
Die definierten regelmäßigen Zeiten können durch Blocker eingeschränkt werden.

Zu diesen Zeiten können dann **keine** Buchungen gemacht werden.

- Blocker können raumspezifisch oder global sein.
- „Global“ bedeutet, dass der Blocker für alle Räume gilt.

![Screenshot](TODO)

## Institutionen
Hier kann man die Liste der Institutionen pflegen, die im Buchungsformular zur Auswahl stehen.

Die Auswahl der Institution hat einen rein informativen, organisatorischen Zweck.

Nach der Installation des Plugins ist bereits eine „Standard-Institution“ angelegt.

![Screenshot](TODO)

Diese „Standard-Institution“ kann man umbenennen bzw. löschen und weitere Institutionen anlegen:

![Screenshot](TODO)

**Ansicht im Buchungsformular**

![Screenshot](TODO)

## Benachrichtigungen

Hier kann man Empfänger und Nachrichtenvorlagen für Änderungen am Buchungsstatus konfigurieren.

| Einstellung                            | Config key                          | Standard-Werte |
|----------------------------------------|-------------------------------------|----------------|
| Service-Adressen für Buchungsanfragen  | `bookingstatus_service_addresses`     | leer           |
| Service-Team benachrichtigen           | `bookingstatus_notify_serviceteam`    | ja             |
| Buchende Person benachrichtigen        | `bookingstatus_notify_bookingperson`  | Ja             |
| Verantwortliche Person benachrichtigen | `bookingstatus_notify_personincharge` | Ja             |
| Weitere Prüfende benachrichtigen       | `bookingstatus_notify_otherexaminers` | Ja             |


Im folgenden können die Benachrichtigungseinstellungen für die einzelnen Status-Zustände konfiguriert werden:
- Neu
- In Bearbeitung
- Bestätigt
- Storniert
- Abgelehnt

| Einstellung                    | Config key                      | Standard-Werte                                                                                                                                             |
|--------------------------------|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Nachricht für \<Status> senden | bookingstatus_enabled_\<status> | Ja                                                                                                                                                         |
| Betreff für \<Status>          | bookingstatus_subject_\<status> | Bsp. für „Neu“<br> Booking request received: `###EVENTNAME###` on `###BOOKINGDATE###`                                                                          |                                |                |
| Nachrichtentext für \<Status>  | bookingstatus_body_\<status>    | Bsp. für „Neu“<br>Thank you for your booking request `"###EVENTNAME###`" for `###BOOKINGDATE###`.<br>We have received your request and will review it shortly. |                                |                |

Folgende Platzhalter können verwendet werden:
- `###EVENTNAME###`
- `###BOOKINGDATE`
- `###BOOKINGSTATUS###`
- `###OLDBOOKINGSTATUS###`
- `###EVENTURL###`
- `###ROOM###`
- `###STARTTIME###`
- `###ENDTIME###`
- `###BOOKINGPERSON###`
- `###PERSONINCHARGE###`
- `###OTHEREXAMINERS###`

