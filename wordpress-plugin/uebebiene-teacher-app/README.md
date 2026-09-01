# ÜbeBiene Teacher App

Aktuelle Plugin-Version: `0.1.29`

Dieses WordPress-Plugin liefert die ÜbeBiene Lehrkräfte-App direkt unter einer festen WordPress-Route als eigene PWA aus.

## Drittkomponenten

Dieses Plugin liefert `QRCode.js` lokal mit. Die Hinweise zu Herkunft und Lizenz stehen in [THIRD-PARTY-NOTICES.md](./THIRD-PARTY-NOTICES.md).

## Ziel

- keine Theme-Einbettung
- kein Shortcode
- eigene HTML-Shell
- eigenes Manifest
- eigener Service Worker
- gleiche Lehrkräfte-App wie in der bestehenden statischen Struktur

## Standard-Route

- `/uebebiene-teacher/`

## Was das Plugin ausliefert

- Lehrkräfte-App HTML-Shell
- `teacher.js`
- `teacher.css`
- `version.js`
- Icons
- `manifest.webmanifest`
- `sw.js`

## Sync und Kopplung

Das Plugin liest die Server- und Lernenden-App-URL bevorzugt aus der bestehenden Sync Bridge:

- `sync_base_url`
- `learner_app_url`

Dadurch bleiben Kopplungslink und QR-Code der Lehrkräfte-App automatisch auf die passende Lernenden-App ausgerichtet.

## Wichtige URLs im Betrieb

- App: `/uebebiene-teacher/`
- Manifest: `/uebebiene-teacher/manifest.webmanifest`
- Service Worker: `/uebebiene-teacher/sw.js`

## Cache-Hinweis

Diese Routen sollten in Cache- oder Optimierungsplugins ausgeschlossen werden:

- `/uebebiene-teacher/`
- `/uebebiene-teacher/manifest.webmanifest`
- `/uebebiene-teacher/sw.js`
- `/uebebiene-teacher/teacher.js`
- `/uebebiene-teacher/teacher.css`














