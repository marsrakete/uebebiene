# ÜbeBiene Sync Bridge

Aktuelle Plugin-Version: `0.28.3`

WordPress-Plugin für zentrale ÜbeBiene-Verwaltung mit:

- Lehrkräften
- Klassen
- Lernenden
- Unterrichten
- Lehrkraft-Zuordnungen
- Kärtchenbibliothek
- direkt verliehene Kärtchen mit Notiz
- signierten Berichtspaketen
- Web-Push-Erinnerungen für installierte Lernenden-PWAs

Das Plugin ist auf den Server unter [https://sarahhansenmusik.de/](https://sarahhansenmusik.de/) zugeschnitten und arbeitet als zentrale Sync-Brücke zwischen Lernenden-App und Lehrkräfte-App.

In der Oberfläche sprechen wir bewusst von `Unterrichten`, weil das für den Alltag verständlicher ist. Technisch dürfen diese Datensätze intern weiterhin `Profile` heißen.

## Sync-Sicherheit

Die beiden kritischen Schreibwege der Lehrkräfte-App laufen jetzt transaktional:

- `teacher-roster`
- `teacher-cards`

Das bedeutet:

- ein vollständiger Snapshot wird komplett übernommen
- oder bei einem Fehler vollständig zurückgerollt
- es bleibt kein halber Zwischenstand aus Stammdaten oder Kärtchen auf dem Server zurück

Berichte von Lernenden bleiben zusätzlich dedupliziert über `report_uuid` und Prüfsumme.

## Installation

1. Ordner `uebebiene-sync-bridge` als ZIP verpacken.
2. In WordPress unter `Plugins -> Installieren -> Plugin hochladen` hochladen.
3. Aktivieren.
4. Im Menü `ÜbeBiene Sync` zuerst `Einstellungen` prüfen.
5. Danach Lehrkräfte, Klassen, Lernende, Unterrichte und Zuordnungen anlegen.
6. Für jede Lehrkraft den `API-Key` in der Lehrkräfte-App hinterlegen.
7. Für jeden Unterricht Lernenden-ID und Verbindungscode in der Lehrkräfte-App anzeigen oder teilen.
8. Unter `Push` die Dispatch-URL für den Web-Cronjob prüfen.
9. Unter `Einstellungen` bei Bedarf ein komplettes Plugin-Backup exportieren oder auf einem anderen Server wieder importieren.

## Web-Cronjob für Push-Erinnerungen

Für Sperrbildschirm-Erinnerungen braucht die Lernenden-App einen serverseitigen Dispatch. Der Timer wird beim Start in der Datenbank als Push-Erinnerung geplant. Der externe Web-Cronjob ruft regelmäßig den Dispatch-Endpunkt auf; das Sync-Plugin versendet dann alle fälligen Pushes an aktive Geräte.

Der notwendige Web-Cronjob ist bei [cron-job.org](https://cron-job.org/) kostenfrei angelegt.

Konfiguration bei cron-job.org:

- Job-Typ: `URL-Aufruf`
- Methode: `GET`
- Intervall: `jede Minute`
- Ziel-URL: im WordPress-Admin unter `ÜbeBiene Sync -> Push -> Push-Erinnerungen -> Dispatch-URL für Cron`
- Erwartete Antwort: HTTP `200` mit JSON, zum Beispiel `ok: true`, `reminderCount`, `sentCount`, `failedCount`

Die Dispatch-URL enthält den geheimen Parameter `key`. Diese URL ist ein Betriebsgeheimnis und darf nicht öffentlich dokumentiert, in Screenshots gezeigt oder in Git eingecheckt werden.

Der Dispatch-Endpunkt:

- verarbeitet nur bereits geplante fällige Push-Erinnerungen
- erzeugt keine Übeeinträge
- liest keine Lernendendaten aus
- versendet ohne gültigen Key nichts
- liefert Zähler zurück, damit cron-job.org den Lauf protokollieren kann

Kontrolle im WordPress-Admin:

- `ÜbeBiene Sync -> Push -> Push-Erinnerungen` zeigt Dispatch-URL und VAPID Public Key.
- `ÜbeBiene Sync -> Push -> Push-Status` zeigt bekannte Push-Geräte, offene Timer, fällige offene Timer, versendete Timer, Fehler und Abbrüche.

Aufräumung:

- erledigte Push-Timer mit Status `sent`, `failed` oder `canceled` werden beim Dispatch nach 14 Tagen gelöscht
- offene `pending`-Timer bleiben erhalten, auch wenn sie überfällig sind
- überfällige offene Timer sind ein Diagnosehinweis darauf, dass der Cronjob nicht läuft oder den Server nicht erreicht

## Backup und Serverwechsel

Im Tab `Einstellungen` gibt es jetzt:

- `Backup exportieren`
- `Backup importieren`

Das Backup enthält:

- Einstellungen
- Lehrkräfte
- Klassen
- Lernende
- Unterrichte
- Zuordnungen
- Kärtchen
- direkt verliehene Kärtchen
- Berichte

Der Import ersetzt die aktuellen Plugin-Daten auf dem Zielserver vollständig. Deshalb verlangt das Plugin:

- eine Checkbox als Sicherheitsabfrage
- eine zusätzliche Browser-Bestätigung vor dem Import

Empfohlener Ablauf für einen Serverwechsel:

1. Auf dem alten Server `Backup exportieren`.
2. Plugin auf dem neuen WordPress-Server installieren.
3. Unter `Einstellungen` das Backup importieren.
4. Danach Lehrkräfte- und Lernenden-Apps wieder mit dem neuen Server testen.

## Empfohlene Reihenfolge

1. Lehrkraft im Plugin anlegen
2. Serverkontext im Plugin prüfen
3. Lehrkräfte-App installieren
4. Lehrkräfte-Key in der Lehrkräfte-App hinterlegen
5. Klassen, Lernende, Unterrichte und Kärtchen in der Lehrkräfte-App anlegen
6. In der Lehrkräfte-App vollständig synchronisieren
7. Lernenden-ID und Verbindungscode in der Lehrkräfte-App anzeigen oder teilen
8. Unterricht in der Lernenden-App mit diesen Kopplungsdaten verbinden

## Plugin als Zentrale

Das Plugin ist nicht nur eine technische Sync-Brücke, sondern die zentrale Verwaltungsstelle vor der Pilotphase:

- Stammdaten von Lehrkräften und Lernenden
- Unterrichte pro Lehrkraft-Kontext
- Kärtchenbibliothek und direkt verliehene Kärtchen
- Berichte und Aufbewahrung
- Backup für Serverwechsel

Im normalen Alltag werden Lernende, Unterrichte und Kärtchen bevorzugt in der Lehrkräfte-App gepflegt und dann mit dem Server synchronisiert. Das Plugin bleibt die Admin-Zentrale, der Ausnahmeweg und das Werkzeug für Serverwechsel, Kontrolle und Korrekturen.

Die Lehrkräfte-App bleibt die angenehmere Arbeitsoberfläche im Alltag. Das Plugin ist die verlässliche Admin-Zentrale, die im Zweifel immer alle Inhalte sehen und bearbeiten kann.

Für Übekategorien gilt dabei: Das Plugin hält nur die Startvorgabe für neue Lehrkräfte bereit. Die konkrete Kategorienliste wird anschließend pro Lehrkraft in der Lehrkräfte-App gepflegt und auch nur in diesem Lehrkraft-Kontext synchronisiert.

Neu im Admin-Überblick:

- Startseite mit Server-/Sync-Überblick
- Profiltabelle mit Klasse, zugeordneten Lehrkräften und Anzahl der Zuordnungen
- Kärtchen-Tab mit direkter Vergabeübersicht inklusive Notiz und Verleihzeit
- Filter und Verlauf für direkte Kärtchen-Vergaben direkt im Kärtchen-Tab

## REST-Endpunkte und URLs

- Lernenden-App Upload:
  `/wp-json/uebebiene-sync/v1/report`
- Lernenden-App Sync:
  `/wp-json/uebebiene-sync/v1/student-sync`
- Lernenden-App Erstkopplung:
  `/wp-json/uebebiene-sync/v1/connect-profile`
- Lehrkräfte-App Sync:
  `/wp-json/uebebiene-sync/v1/teacher-sync`
- Lehrkräfte-App Profilpaket:
  `/wp-json/uebebiene-sync/v1/teacher-profile-package`
- Lehrkräfte-App Stammdaten speichern:
  `/wp-json/uebebiene-sync/v1/teacher-roster`
- Lehrkräfte-App Kärtchen speichern:
  `/wp-json/uebebiene-sync/v1/teacher-cards`
- Lehrkräfte-App direkte Kärtchen-Vergabe:
  `/wp-json/uebebiene-sync/v1/teacher-card-awards`
- Öffentlicher Profilpaket-Link:
  `/wp-json/uebebiene-sync/v1/profile-package`
- Push-Konfiguration für Lernenden-App:
  `/wp-json/uebebiene-sync/v1/push/config`
- Push-Subscription speichern:
  `/wp-json/uebebiene-sync/v1/push/subscribe`
- Push-Subscription entfernen:
  `/wp-json/uebebiene-sync/v1/push/unsubscribe`
- Push-Test:
  `/wp-json/uebebiene-sync/v1/push/test`
- Timer-Push planen:
  `/wp-json/uebebiene-sync/v1/push/timer/schedule`
- Timer-Push abbrechen:
  `/wp-json/uebebiene-sync/v1/push/timer/cancel`
- Fällige Pushes per Web-Cronjob versenden:
  `/wp-json/uebebiene-sync/v1/push/dispatch?key=...`

Komplette Basis-URL für beide Apps:

- `https://sarahhansenmusik.de/wp-json/uebebiene-sync/v1`

## Header

Lernenden-App:

- `X-Uebebiene-Upload-Token`

Lehrkräfte-App:

- `X-Uebebiene-Teacher-Key`

## Datenhaltung

Das Plugin legt eigene WordPress-Tabellen an für:

- Lehrkräfte
- Klassen
- Lernende
- Unterrichte
- Lehrkraft-Zuordnungen
- Kärtchen
- direkt verliehene Kärtchen
- Berichte
- Push-Subscriptions
- Push-Erinnerungen

Berichte werden nicht als Datei in die Mediathek gelegt, sondern als geprüfte JSON-Datensätze in der Datenbank gespeichert.

## Drittkomponenten

Dieses Plugin liefert `QRCode.js` lokal mit. Die Hinweise zu Herkunft und Lizenz stehen in [THIRD-PARTY-NOTICES.md](./THIRD-PARTY-NOTICES.md).

## Deduplizierung

Doppelte Uploads werden serverseitig verhindert über:

- `report_uuid`
- Kombination aus `student_profile_id` und `checksum`

Wenn ein Bericht doppelt ankommt, liefert das Plugin `duplicate_ignored` zurück statt einen zweiten Datensatz anzulegen.

## Aufbewahrung

Die Berichte werden standardmäßig `180` Tage aufbewahrt. Die Frist ist im Tab `Einstellungen` anpassbar.

Im Plugin-Tab `Berichte` bedeutet `Gesendet` der Zeitstempel aus der Lernenden-App. `Empfangen` ist der Zeitpunkt, an dem WordPress den Bericht gespeichert hat.

## App-Integration

Lernenden-App:

- manueller Berichtsexport bleibt erhalten
- zusätzlich kann ein Bericht per Knopfdruck online gesendet werden
- zusätzlich kann die App Unterricht und Lehrkräfte-Kärtchen direkt vom Server synchronisieren
- die Erstkopplung läuft bevorzugt über Lernenden-ID und 4-stelligen Verbindungscode

Lehrkräfte-App:

- manueller Import von Berichtspaketen bleibt erhalten
- Backup-Export und -Import bleiben erhalten
- zusätzlich kann die App Berichte, Klassen und Kärtchen direkt vom Server laden
- zusätzlich kann die App Kärtchen direkt an einzelne Unterrichte verleihen und mit Notiz versehen
- der normale Ablauf für neue Geräte geht über QR-Code oder Lernenden-ID plus Verbindungscode

## Release-Stand

`0.27.3`

- zentrale Verwaltung für Lehrkräfte, Klassen, Lernende und Unterrichte
- signierter Berichtsempfang per REST-API
- Lehrkräfte-Sync für Klassen, Berichte und Kärtchen
- Lehrkräfte-App kann Klassen und persönliche Daten der Lernenden direkt auf den Server speichern
- Lehrkräfte-App kann eigene Kärtchen direkt auf den Server speichern
- Kärtchen können für alle, für eine Klasse oder für eine einzelne Person zugewiesen werden
- WordPress-Admin zeigt und bearbeitet jetzt auch Vorname, Nachname, E-Mail und Messenger-ID der Lernenden
- WordPress-Admin kann Kärtchen jetzt ebenfalls gezielt für alle, für eine Klasse oder für eine einzelne Person zuweisen
- Profilpakete tragen im Dateinamen jetzt auch den Namen der Lernenden
- Lehrkräfte-App kann Profilpakete jetzt direkt vom Server laden, teilen und als QR-Code bereitstellen
- Plugin liefert dafür geschützte Lehrkräfte-Endpunkte und öffentliche Freigabelinks für einzelne Profilpakete
- Lernenden-App kann jetzt Unterricht und zugewiesene Lehrkräfte-Kärtchen direkt per `student-sync` vom Server nachladen
- Lehrkräfte können Kärtchen jetzt direkt an einzelne Unterrichte verleihen
- direkt verliehene Kärtchen können eine Notiz der Lehrkraft enthalten
- Lernenden-App zeigt direkt verliehene Kärtchen sofort als erreicht an
- Plugin-Startseite zeigt jetzt zusätzlich Server-/Sync-Kontext und die letzten direkt verliehenen Kärtchen
- Profil- und Kärtchen-Tab geben mehr Überblick über Lehrkraft-Zuordnungen und direkte Vergaben
- direkte Kärtchen-Vergaben lassen sich jetzt im Plugin nach Kärtchen, Lehrkraft und Unterricht filtern
- pro ausgewähltem Kärtchen gibt es jetzt einen eigenen Vergabeverlauf direkt im Kärtchen-Tab
- Plugin-Backups enthalten jetzt auch direkt verliehene Kärtchen
- jeder Unterricht hat jetzt zusätzlich einen dauerhaften 4-stelligen Verbindungscode für die Erstkopplung
- Lernenden-App kann sich damit direkt per `connect-profile` an einen Unterricht anbinden
- Lehrkräfte-App zeigt Lernenden-ID und Verbindungscode direkt an und kann diese Kopplungsdaten teilen
- Profilpakete für die Lernenden-App
- serverseitige Deduplizierung und Aufbewahrungslogik




