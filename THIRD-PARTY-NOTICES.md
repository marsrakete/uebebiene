# Third-Party Notices

Dieses Projekt liefert einige Drittkomponenten lokal mit oder nutzt in einem Alt-Plugin einen externen QR-Dienst. Diese Hinweise dokumentieren Herkunft, Lizenz und Einsatzort.

## jsQR

- Projekt: `jsQR`
- Repository: [https://github.com/cozmo/jsQR](https://github.com/cozmo/jsQR)
- Paket: [https://www.npmjs.com/package/jsqr](https://www.npmjs.com/package/jsqr)
- Copyright: siehe Originalprojekt
- Lizenz: Apache License 2.0, siehe [https://github.com/cozmo/jsQR/blob/master/LICENSE](https://github.com/cozmo/jsQR/blob/master/LICENSE)
- Verwendet für: QR-Erkennung in der Lernenden-App als Fallback zu `BarcodeDetector`
- Lokale Dateien:
  - `vendor-jsQR.js`
  - `wordpress-plugin/uebebiene-learner-app/assets/vendor-jsQR.js`

Hinweis:

`jsQR` wird in ÜbeBiene als Drittbibliothek verwendet. Für `jsQR` gelten die Bedingungen der Apache-2.0-Lizenz des Originalprojekts.

## QRCode.js

- Projekt: `QRCode.js`
- Repository: [https://github.com/davidshimjs/qrcodejs](https://github.com/davidshimjs/qrcodejs)
- Paket: [https://www.npmjs.com/package/qrcodejs](https://www.npmjs.com/package/qrcodejs)
- Copyright: Copyright (c) 2012 davidshimjs
- Lizenz: MIT License, siehe [https://github.com/davidshimjs/qrcodejs/blob/master/LICENSE](https://github.com/davidshimjs/qrcodejs/blob/master/LICENSE)
- Verwendet für: lokale QR-Code-Erzeugung in Apps und WordPress-Adminbereichen
- Lokale Dateien:
  - `vendor-qrcodejs.js`
  - `wordpress-plugin/uebebiene-learner-app/assets/vendor-qrcodejs.js`
  - `wordpress-plugin/uebebiene-teacher-app/assets/vendor-qrcodejs.js`
  - `wordpress-plugin/uebebiene-sync-bridge/assets/vendor-qrcodejs.js`

Hinweis:

`QRCode.js` wird lokal ausgeliefert. Beim Weitergeben der Software muss der MIT-Copyright- und Lizenzhinweis erhalten bleiben.

## goQR.me QR Code API

- Dienst: `goQR.me QR Code API`
- Dokumentation: [https://goqr.me/api/doc/create-qr-code/](https://goqr.me/api/doc/create-qr-code/)
- Datenschutz-/Sicherheitshinweise: [https://goqr.me/privacy-safety-security/](https://goqr.me/privacy-safety-security/)
- Verwendet für: QR-Code-Bilder im alten `fleisstakt-sync-bridge`-Plugin über `https://api.qrserver.com/v1/create-qr-code/`
- Art der Nutzung: externer Dienstaufruf, keine lokal eingebundene Bibliothek

Hinweis:

Dieser Dienst betrifft den alten FleissTakt-Pluginbereich. Die aktuellen ÜbeBiene-Plugins erzeugen ihre Admin-QR-Codes lokal mit `QRCode.js`.
