# Q-Bab Kasse System - Deployment Anleitung

## Übersicht
Dieses Dokument beschreibt die Schritte zur Bereitstellung des Kassensystems auf Strato.

## Voraussetzungen
1. ✅ Strato FTP-Zugang
2. ✅ Strato MySQL Zugang (Database: `dbs14816626`)
3. ✅ Deutsche Fiskal TSE Account (oder Demo)
4. ✅ Node.js 18+ (lokal für Build)

---

## Phase 1: Database Migration

### 1.1 SQL Migration ausführen

```bash
# Via Strato phpMyAdmin
# 1. Einloggen: https://webmail.strato.de/phpmyadmin
# 2. Database: dbs14816626 auswählen
# 3. SQL-Tab öffnen
# 4. Datei importieren: database/migrations/add_kasse_fields.sql
# 5. Ausführen
```

**Erwartetes Ergebnis:**
- `orders` Tabelle hat neue Felder: `order_source`, `tse_transaction_id`, etc.
- `kasse_sessions` Tabelle wurde erstellt
- `kasse_sync_queue` Tabelle wurde erstellt

### 1.2 TSE-Konfiguration in .env

Erstelle/Aktualisiere `.env` im Root-Verzeichnis:

```env
# Bestehende Konfiguration beibehalten...

# TSE Configuration (Fiskaly)
FISKALY_API_KEY=your_api_key_here
FISKALY_API_SECRET=your_api_secret_here
FISKALY_TSS_ID=your_tss_id_here
FISKALY_CLIENT_ID=qbab-pos-001
```

**Fiskaly Account einrichten:**
1. Registriere dich auf: https://dashboard.fiskaly.com/signup
2. Dashboard → Settings → API Keys → Create API Key
3. Kopiere API Key und API Secret
4. Dashboard → TSS → Wähle deine TSS → Kopiere TSS ID
5. Trage die Werte in .env ein

**📖 Detaillierte Anleitung:** Siehe `FISKALY_SETUP.md`

---

## Phase 2: Backend Deployment (PHP)

### 2.1 Neue Dateien via FTP hochladen

**Verzeichnisstruktur auf Strato:**
```
/public/
  /includes/
    tse-service.php          ← NEU
  /api/
    /kasse/                  ← NEUER ORDNER
      create-cash-order.php
      get-daily-summary.php
      sync-offline-orders.php
      get-products.php
      start-session.php
      end-session.php
      export-datev.php
  /database/
    /migrations/
      add_kasse_fields.sql   ← NEU
```

**FTP Upload-Schritte:**
1. Verbinde mit Strato FTP: `5018723982.ssh.w2.strato.hosting`
2. Navigiere zu `/public/`
3. Lade `includes/tse-service.php` hoch
4. Erstelle Ordner `api/kasse/`
5. Lade alle 7 PHP-Dateien aus `api/kasse/` hoch
6. Lade `.env` hoch (mit TSE-Konfiguration)

### 2.2 Berechtigungen setzen

```bash
# Via SSH (falls verfügbar) oder FileZilla
chmod 755 api/kasse/*.php
chmod 644 includes/tse-service.php
chmod 600 .env
```

### 2.3 Backend testen

```bash
# Test 1: TSE Health Check (Browser oder curl)
curl https://q-bab.de/api/kasse/get-products.php

# Test 2: Produkte abrufen
curl https://q-bab.de/api/kasse/get-products.php

# Test 3: Tagesübersicht
curl https://q-bab.de/api/kasse/get-daily-summary.php?date=2025-01-14
```

**Erwartetes Ergebnis:** JSON-Responses ohne Fehler

---

## Phase 3: Frontend Deployment (PWA)

### 3.1 Frontend Build

```bash
# Lokale Maschine
cd E:\kasse_system\frontend

# Build für Production
npm run build:web

# Output: dist/ Ordner
```

### 3.2 Frontend via FTP hochladen

**Zielverzeichnis auf Strato:** `/public/kasse/`

```
/public/
  /kasse/                    ← NEUER ORDNER
    index.html
    manifest.json            ← PWA Manifest
    sw.js                    ← Service Worker
    icon-192.png            ← PWA Icon (192x192)
    icon-512.png            ← PWA Icon (512x512)
    /assets/
      index-[hash].js
```

**Upload-Schritte:**
1. Verbinde mit Strato FTP
2. Navigiere zu `/public/`
3. Erstelle Ordner `kasse/`
4. Lade den gesamten Inhalt von `frontend/dist/` nach `/public/kasse/` hoch
5. Lade `frontend/public/manifest.json` nach `/public/kasse/` hoch
6. Lade `frontend/public/sw.js` nach `/public/kasse/` hoch

### 3.3 PWA Icons erstellen

**Benötigt:** 2 Icon-Dateien (192x192 und 512x512 PNG)

```bash
# Verwende ein Online-Tool oder ImageMagick:
# https://realfavicongenerator.net/

# Oder mit ImageMagick (falls installiert):
convert logo.png -resize 192x192 icon-192.png
convert logo.png -resize 512x512 icon-512.png
```

Lade die Icons nach `/public/kasse/` hoch.

### 3.4 .htaccess für PWA konfigurieren

Erstelle `/public/kasse/.htaccess`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /kasse/
  
  # Service Worker Cache-Control
  <FilesMatch "sw\.js$">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Pragma "no-cache"
    Header set Expires 0
  </FilesMatch>
  
  # React Router (SPA)
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /kasse/index.html [L]
</IfModule>

# MIME Types
<IfModule mod_mime.c>
  AddType application/manifest+json .json
  AddType application/x-web-app-manifest+json .webapp
  AddType text/cache-manifest .appcache
</IfModule>

# Security Headers
<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

## Phase 4: Test-Szenarien

### 4.1 Online-Test

1. **Login-Test**
   ```
   URL: https://q-bab.de/kasse/
   Login: admin@q-bab.de / [dein Passwort]
   ```

2. **POS - Produkt auswählen**
   - Navigiere zu "Verkauf (POS)"
   - Wähle Kategorie
   - Füge Produkt hinzu
   - Füge Extras hinzu
   - Prüfe Warenkorb

3. **POS - Nakit-Zahlung (Online)**
   - Klicke "Bezahlen"
   - Wähle "Nakit"
   - Gib Betrag ein (z.B. 20€)
   - Bestätige Zahlung
   - **Erwartung:** TSE-Signatur im Response, Beleg angezeigt

4. **Admin Dashboard**
   - Navigiere zu "Admin Dashboard"
   - Prüfe "Heute Umsatz" - sollte die Bestellung zeigen
   - Prüfe "Bestellungen" Tab - Bestellung sollte sichtbar sein

### 4.2 Offline-Test

1. **Netzwerk deaktivieren**
   - Chrome DevTools → Network Tab → "Offline" Checkbox
   - Oder WiFi ausschalten

2. **Produkte laden (Offline)**
   - Seite neu laden
   - **Erwartung:** Seite lädt aus Cache, Produkte sind sichtbar

3. **Bestellung erstellen (Offline)**
   - Produkt auswählen
   - Nakit-Zahlung durchführen
   - **Erwartung:** "Offline - wird synchronisiert" Nachricht
   - Bestellung wird in IndexedDB gespeichert

4. **Online wieder aktivieren**
   - Netzwerk wieder aktivieren
   - **Erwartung:** Nach ~30 Sekunden automatische Synchronisierung
   - Offline-Bestellung erscheint im Backend mit TSE-Signatur

### 4.3 DATEV Export Test

```bash
# Browser oder curl
curl "https://q-bab.de/api/kasse/export-datev.php?start_date=2025-01-01&end_date=2025-01-31&format=csv" > datev_export.csv

# Öffne datev_export.csv in Excel
# Prüfe Spalten: umsatz, konto, gegenkonto, bu_schluessel, belegdatum, etc.
```

### 4.4 TSE Compliance Test

1. **Prüfe TSE-Daten in Bestellung**
   ```sql
   SELECT 
     order_number, 
     tse_transaction_id, 
     tse_signature, 
     tse_qr_code
   FROM orders 
   WHERE order_source = 'KASSE' 
   ORDER BY created_at DESC 
   LIMIT 5;
   ```
   
2. **TSE Health Check**
   - Fiskaly Dashboard: https://dashboard.fiskaly.com
   - Gehe zu: **Transactions**
   - Deine Bestellung sollte dort erscheinen
   - Klicke auf Transaction → Prüfe Signature Details

---

## Phase 5: Fehlerbehandlung

### 5.1 Häufige Fehler

**Problem:** "TSE Service: Not configured"
- **Lösung:** Prüfe `.env` Datei, setze `FISKALY_API_KEY`, `FISKALY_API_SECRET` und `FISKALY_TSS_ID`

**Problem:** Service Worker registriert nicht
- **Lösung:** Prüfe `/kasse/sw.js` ist erreichbar, keine 404-Fehler in Console

**Problem:** Offline-Bestellungen synchronisieren nicht
- **Lösung:** 
  1. DevTools → Application → IndexedDB → QBabKasseDB prüfen
  2. Console-Logs prüfen
  3. Background Sync API unterstützt? (Chrome/Edge: Ja, Safari: Nein)

**Problem:** DATEV Export leer
- **Lösung:** 
  1. Prüfe Datumsbereich
  2. Prüfe ob `payment_status = 'completed'`
  3. SQL-Abfrage manuell ausführen

### 5.2 Debug-Logs

**PHP Backend:**
```bash
# Strato Error Logs (via FTP oder cPanel)
/logs/error.log
```

**Browser (Frontend):**
```javascript
// Chrome DevTools Console
// Filter: "SW" oder "SyncService" oder "IndexedDB"
```

---

## Phase 6: Produktiv-Checkliste

- [ ] Database Migration erfolgreich
- [ ] TSE-Konfiguration aktiv (nicht Mock-Modus)
- [ ] Backend-APIs erreichbar und testen
- [ ] Frontend deployed auf `/kasse/`
- [ ] PWA installierbar (Manifest + Service Worker)
- [ ] Online-Zahlung funktioniert mit TSE
- [ ] Offline-Zahlung speichert und synced
- [ ] Admin Dashboard zeigt Bestellungen
- [ ] DATEV Export generiert CSV
- [ ] Strato Backups aktiviert (automatisch)

---

## Support & Wartung

### Tägliche Aufgaben
- Prüfe Dashboard für Tagesabschluss
- Z-Bericht exportieren (DATEV)

### Wöchentliche Aufgaben
- Offline-Sync-Queue prüfen (sollen leer sein)
- TSE-Transaktionen validieren

### Monatliche Aufgaben
- DATEV Export an Steuerberater
- Database-Backup herunterladen (Strato Backup-Tool)
- Logs prüfen und archivieren

---

## Kontakt für Probleme

- **Technischer Support:** [Email/Telefon]
- **Deutsche Fiskal Support:** support@deutsche-fiskal.de
- **Strato Support:** https://www.strato.de/support/

---

## Changelog

### Version 1.0 (2025-01-14)
- ✅ Initial Release
- ✅ TSE Integration (Deutsche Fiskal)
- ✅ Offline-Modus mit PWA
- ✅ DATEV Export (SKR03/SKR04)
- ✅ Multi-Source Konsolidierung (Website + Kassa)

