# ÜbeBiene Learner App

Aktuelle Plugin-Version: `0.1.22`

Dieses WordPress-Plugin liefert die ÜbeBiene Lernenden-App direkt unter einer festen WordPress-Route als eigene PWA aus.

## Ziel

- keine Theme-Einbettung
- kein Shortcode
- eigene HTML-Shell
- eigenes Manifest
- eigener Service Worker
- gleiche Lernenden-App wie in der bestehenden statischen Struktur

## Standard-Route

- `/uebebiene/`

## Was das Plugin ausliefert

- Lernenden-App HTML-Shell
- `app.js`
- `styles.css`
- `version.js`
- `vendor-qrcodejs.js`
- `vendor-jsQR.js`
- Icons
- `manifest.webmanifest`
- `sw.js`

## Sync und WordPress-Betrieb

Das Plugin liest die Sync-Basis-URL bevorzugt aus der bestehenden Sync Bridge:

- `sync_base_url`

Optional kann zusätzlich die Lehrkräfte-App-URL überschrieben werden.

## Wichtige URLs im Betrieb

- App: `/uebebiene/`
- Manifest: `/uebebiene/manifest.webmanifest`
- Service Worker: `/uebebiene/sw.js`

## Cache-Hinweis

Diese Routen sollten in Cache- oder Optimierungsplugins ausgeschlossen werden:

- `/uebebiene/`
- `/uebebiene/manifest.webmanifest`
- `/uebebiene/sw.js`
- `/uebebiene/app.js`
- `/uebebiene/styles.css`
- `/uebebiene/version.js`

## Drittkomponenten

Dieses Plugin liefert QR-Bibliotheken lokal mit. Die Hinweise zu Herkunft und Lizenz stehen in [THIRD-PARTY-NOTICES.md](./THIRD-PARTY-NOTICES.md).

