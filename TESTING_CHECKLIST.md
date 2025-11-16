# ✅ Live Server Testing Checklist

## 🎯 Zweck
Dieses Dokument hilft dir, das komplette System auf **q-bab.de** zu testen, nachdem du die `.env` Datei korrigiert hast.

---

## 🔧 VORAUSSETZUNGEN

- [ ] `.env` Datei auf Server bearbeitet (`FISKALY_USE_MIDDLEWARE=false`)
- [ ] Dateirechte korrekt gesetzt (`chmod 644 .env`)
- [ ] Datenbank vorhanden und verbunden
- [ ] SSL-Zertifikat aktiv (HTTPS funktioniert)

---

## 📋 TEST-REIHENFOLGE

### ✅ PHASE 1: Basis-Konnektivität

#### Test 1.1: Website erreichbar
```bash
curl -I https://q-bab.de
```
**Erwartung**: HTTP 200 OK

#### Test 1.2: API-Verzeichnis erreichbar
```bash
curl -I https://q-bab.de/api/kasse/
```
**Erwartung**: Kein 404 Fehler

#### Test 1.3: Admin-Verzeichnis geschützt
```bash
curl -I https://q-bab.de/admin/
```
**Erwartung**: 401 Unauthorized oder Login-Seite

---

### ✅ PHASE 2: Fiskaly TSE Integration

#### Test 2.1: Health Check Endpoint
```bash
curl -s https://q-bab.de/api/kasse/health.php | jq
```

**Erwartetes Ergebnis**:
```json
{
  "success": true,
  "timestamp": "2025-11-16 ...",
  "health": {
    "status": "online",
    "healthy": true,
    "message": "TSE system operational"
  },
  "configuration": {
    "enabled": true,
    "tss_id": "df15a626-6b42-45ce-8016-9cb5083dae8a",
    "client_id": "e6719d2e-b409-638b-e3c2-bffd3d35fda7",
    "api_base_url": "https://kassensichv.fiskaly.com/api/v2",
    "has_token": true,
    "token_expires_in": 3600
  }
}
```

**Prüfpunkte**:
- [ ] `"healthy": true` ✅
- [ ] `"api_base_url"` = `"https://kassensichv.fiskaly.com/api/v2"` (NICHT localhost:8000) ✅
- [ ] `"has_token": true` ✅
- [ ] HTTP Status Code = 200 ✅

**Falls fehlgeschlagen**: Siehe [Troubleshooting](#troubleshooting)

---

#### Test 2.2: Vollständige Test Suite
```bash
curl -s https://q-bab.de/api/kasse/test.php | jq
```

**Erwartetes Ergebnis**:
```json
{
  "message": "Q-Bab Kasse - Production Test Suite",
  "timestamp": "2025-11-16 ...",
  "tests": {
    "config": {
      "name": "Configuration Check",
      "status": "PASS",
      "details": {
        "tss_id": "df15a626-...",
        "client_id": "e6719d2e-...",
        "api_base_url": "https://kassensichv.fiskaly.com/api/v2"
      }
    },
    "health": {
      "name": "TSE Health Check",
      "status": "PASS",
      "details": {
        "healthy": true,
        "status": "online"
      }
    },
    "signature": {
      "name": "Transaction Signature Test",
      "status": "PASS",
      "details": {
        "transaction_id": "...",
        "signature": "...",
        "qr_code_data": "..."
      }
    }
  },
  "summary": {
    "total_tests": 3,
    "passed": 3,
    "failed": 0,
    "success_rate": "100%"
  }
}
```

**Prüfpunkte**:
- [ ] `"success_rate": "100%"` ✅
- [ ] Alle 3 Tests mit `"status": "PASS"` ✅
- [ ] Signatur wurde erfolgreich erstellt ✅

---

#### Test 2.3: Token Cache löschen
```bash
curl -X POST https://q-bab.de/api/kasse/clear-token-cache.php
```

**Erwartetes Ergebnis**:
```json
{
  "success": true,
  "message": "Token cache successfully cleared...",
  "timestamp": "2025-11-16 ..."
}
```

**Prüfpunkte**:
- [ ] `"success": true` ✅

---

### ✅ PHASE 3: Transaktions-Tests

#### Test 3.1: Bargeld-Bestellung erstellen
```bash
curl -X POST https://q-bab.de/api/kasse/create-cash-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "id": 1,
        "name_de": "Test Cheeseburger",
        "name_en": "Test Cheeseburger",
        "price": 8.90,
        "quantity": 2,
        "extras": []
      },
      {
        "id": 2,
        "name_de": "Cola",
        "name_en": "Cola",
        "price": 2.50,
        "quantity": 1,
        "extras": []
      }
    ],
    "totalAmount": 20.30,
    "paymentMethod": "CASH",
    "cashAmount": 25.00,
    "changeAmount": 4.70,
    "cashierName": "Test Kassierer",
    "customerName": "",
    "notes": "Test-Bestellung vom Setup"
  }' | jq
```

**Erwartetes Ergebnis**:
```json
{
  "success": true,
  "orderId": 1,
  "orderNumber": "KASSE-20251116-001",
  "message": "Order successfully created and signed",
  "tseSignature": {
    "transaction_id": "urn:uuid:...",
    "signature": "...",
    "signature_counter": 1,
    "qr_code_data": "V0;...",
    "start_time": "2025-11-16T...",
    "end_time": "2025-11-16T..."
  },
  "printUrl": "/api/kasse/print-receipt.php?order_id=1"
}
```

**Prüfpunkte**:
- [ ] `"success": true` ✅
- [ ] `"tseSignature"` vorhanden ✅
- [ ] `"transaction_id"` beginnt mit `"urn:uuid:"` ✅
- [ ] `"signature"` ist nicht leer ✅
- [ ] `"qr_code_data"` beginnt mit `"V0;"` (BSI TR-03153 Format) ✅
- [ ] `"printUrl"` vorhanden ✅

**Notiere die Order ID** für nächsten Test!

---

#### Test 3.2: Karten-Bestellung erstellen
```bash
curl -X POST https://q-bab.de/api/kasse/create-cash-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "id": 3,
        "name_de": "Q-Bab Spezial",
        "name_en": "Q-Bab Special",
        "price": 12.50,
        "quantity": 1,
        "extras": [
          {"name": "Extra Käse", "price": 1.50}
        ]
      }
    ],
    "totalAmount": 14.00,
    "paymentMethod": "CARD",
    "cashAmount": 0,
    "changeAmount": 0,
    "cashierName": "Test Kassierer",
    "customerName": "Max Mustermann",
    "notes": "Test Kartenzahlung"
  }' | jq
```

**Prüfpunkte**: Gleiche wie Test 3.1

---

#### Test 3.3: Beleg drucken (Browser-Test)
Im Browser öffnen:
```
https://q-bab.de/api/kasse/print-receipt.php?order_id=1
```
(Ersetze `1` mit der Order ID aus Test 3.1)

**Erwartete Anzeige**:
- [ ] Firmen-Header (Q-BAB BURGER) ✅
- [ ] Bestellnummer (KASSE-20251116-001) ✅
- [ ] Bestellpositionen mit Preisen ✅
- [ ] Zwischensumme, MwSt., Gesamtsumme ✅
- [ ] Zahlungsart (Bargeld/Karte) ✅
- [ ] Bei Bargeld: Gegeben und Rückgeld ✅
- [ ] **TSE-Signatur-Bereich**: ✅
  - TSE-Seriennummer
  - Signatur (abgekürzt)
  - TSE-Transaktion-ID
  - **QR-Code** (200x200px, scannbar) ✅
- [ ] Fußzeile mit Steuernummer ✅
- [ ] "Drucken" Button funktioniert ✅

**QR-Code testen**:
- [ ] QR-Code ist sichtbar (schwarzes Quadrat mit Muster) ✅
- [ ] QR-Code kann mit Smartphone gescannt werden ✅
- [ ] Gescannte Daten beginnen mit `V0;` ✅

---

### ✅ PHASE 4: Admin-Dashboard

#### Test 4.1: Admin Login
Im Browser öffnen:
```
https://q-bab.de/admin/tse-status.php
```

**Falls Login erforderlich**:
- Login mit deinen Admin-Credentials
- Du solltest zur TSE Status Seite weitergeleitet werden

**Erwartete Anzeige**:
- [ ] Titel: "TSE Status Dashboard" ✅
- [ ] **TSE Status**: 🟢 ONLINE (grün) ✅
- [ ] **Konfiguration**:
  - API URL: `https://kassensichv.fiskaly.com/api/v2` ✅
  - TSS ID: `df15a626-...` ✅
  - Client ID: `e6719d2e-...` ✅
- [ ] **Token Status**:
  - Status: Aktiv (grün) ✅
  - Läuft ab in: ~3600 Sekunden ✅
- [ ] **Heute's Statistik**:
  - Gesamt Bestellungen: (Anzahl) ✅
  - TSE-signierte: (Anzahl) ✅
  - Gesamtumsatz: (Betrag) ✅
- [ ] **Letzte Transaktionen** (Tabelle mit 50 neuesten) ✅

---

#### Test 4.2: DSFinV-K Export
Im Browser öffnen:
```
https://q-bab.de/admin/export-dsfinvk.php
```

**Test-Durchführung**:
1. [ ] Startdatum wählen (z.B. heute) ✅
2. [ ] Enddatum wählen (z.B. heute) ✅
3. [ ] "Export Generieren" klicken ✅
4. [ ] Tabelle mit Transaktionen wird angezeigt ✅
5. [ ] "Als CSV Herunterladen" klicken ✅
6. [ ] CSV-Datei wird heruntergeladen ✅
7. [ ] CSV-Datei enthält korrekte Daten ✅
8. [ ] "Als JSON Herunterladen" klicken ✅
9. [ ] JSON-Datei wird heruntergeladen ✅

**CSV-Datei prüfen**:
```csv
Bestellnummer,TSE Transaction ID,TSE Signatur,Betrag,Zahlungsart,Kassierer,Datum
KASSE-20251116-001,urn:uuid:...,xxxxx...,20.30,CASH,Test Kassierer,2025-11-16...
```

---

### ✅ PHASE 5: Frontend-Integration

#### Test 5.1: Kasse-Seite öffnen
Im Browser öffnen:
```
https://q-bab.de/kasse.php
```

**Prüfpunkte**:
- [ ] Seite lädt ohne Fehler ✅
- [ ] Produkte werden angezeigt ✅
- [ ] Warenkorb funktioniert ✅
- [ ] "Bezahlen" Button vorhanden ✅

---

#### Test 5.2: Bestellung über Frontend
1. [ ] Produkt zum Warenkorb hinzufügen ✅
2. [ ] Warenkorb zeigt korrekten Preis ✅
3. [ ] "Bezahlen" klicken ✅
4. [ ] Zahlungsart wählen (Bargeld/Karte) ✅
5. [ ] Bei Bargeld: Betrag eingeben ✅
6. [ ] Bestellung abschicken ✅
7. [ ] Erfolgsmeldung erscheint ✅
8. [ ] Beleg-Link wird angezeigt ✅
9. [ ] Beleg öffnen und prüfen ✅
10. [ ] TSE-Signatur ist vorhanden ✅

---

### ✅ PHASE 6: Performance & Monitoring

#### Test 6.1: Performance-Check
```bash
# Erste Anfrage (neue Authentifizierung)
time curl -s https://q-bab.de/api/kasse/health.php > /dev/null

# Zweite Anfrage (gecachter Token)
time curl -s https://q-bab.de/api/kasse/health.php > /dev/null
```

**Erwartung**:
- Erste Anfrage: ~500-800ms (inkl. Auth)
- Zweite Anfrage: ~200-400ms (gecachter Token) ✅
- **Zweite sollte deutlich schneller sein!**

---

#### Test 6.2: Server-Logs prüfen
Falls du SSH-Zugriff hast:

```bash
# PHP Error Log
tail -f /pfad/zu/error.log

# TSE-spezifische Logs
grep "TSE:" /pfad/zu/error.log | tail -20
```

**Erwartete Log-Einträge**:
```
TSE: Using direct Cloud API
TSE: Using cached auth token (expires in 3540 seconds)
TSE: Transaction TEST-001 signed successfully
```

**KEINE Fehler wie**:
- ❌ "Failed to connect to localhost:8000"
- ❌ "cURL error 7"
- ❌ "Failed to get auth token"

---

## 🔍 TROUBLESHOOTING

### Problem: "api_base_url": "http://localhost:8000"

**Ursache**: `.env` hat noch `FISKALY_USE_MIDDLEWARE=true`

**Lösung**:
1. `.env` öffnen
2. Ändere `FISKALY_USE_MIDDLEWARE=true` zu `false`
3. Lösche `FISKALY_MIDDLEWARE_URL` Wert (leer lassen)
4. Speichern
5. Token Cache löschen:
   ```bash
   curl -X POST https://q-bab.de/api/kasse/clear-token-cache.php
   ```
6. Erneut testen

---

### Problem: "Failed to get auth token"

**Mögliche Ursachen**:

**A) Falsche Credentials**
- Prüfe `.env` Datei:
  - `FISKALY_API_KEY` korrekt?
  - `FISKALY_API_SECRET` korrekt?
- Credentials auf Fiskaly Dashboard verifizieren

**B) TSS noch Middleware-Type** (wartet auf Fiskaly Support)
- Warte auf Antwort von Fiskaly
- System wird funktionieren, sobald Cloud TSE Credentials da sind

**C) Netzwerk-Problem**
```bash
# Test Fiskaly API Erreichbarkeit
curl -I https://kassensichv.fiskaly.com/api/v2
```
- Sollte HTTP 401 zurückgeben (OK - braucht Auth)
- Falls Timeout: Firewall-Problem

---

### Problem: "healthy": false

**Debugging**:
```bash
curl -s https://q-bab.de/api/kasse/health.php | jq '.health'
```

Prüfe `message` Feld für Details:
- "Failed to get auth token" → Siehe oben
- "TSE service not enabled" → `.env` prüfen
- "Database connection failed" → DB-Credentials prüfen

---

### Problem: Kein QR-Code auf Beleg

**Ursache**: JavaScript-Library lädt nicht

**Lösung**:
1. Browser-Console öffnen (F12)
2. Prüfe auf Fehler:
   - `qrcode.min.js` lädt?
   - Keine CORS-Fehler?
3. Falls CDN blockiert: Lokale Kopie verwenden

---

### Problem: "Transaction signature failed"

**Debugging**:
```bash
# Detail-Log aktivieren
# In create-cash-order.php error_log aktiviert?
tail -f /pfad/zu/error.log | grep "TSE:"
```

**Mögliche Ursachen**:
- Auth Token abgelaufen → Automatischer Retry sollte funktionieren
- TSS nicht initialisiert → Fiskaly Dashboard prüfen
- Network Timeout → Timeout erhöhen

---

## 📊 ERFOLGS-KRITERIEN

### ✅ SYSTEM IST BEREIT WENN:

**Backend**:
- [x] Health Check: `"healthy": true`
- [x] Test Suite: `"success_rate": "100%"`
- [x] API URL: `https://kassensichv.fiskaly.com/api/v2`
- [x] Token Caching: Zweite Anfrage schneller

**Transaktionen**:
- [x] Bargeld-Bestellung erstellt TSE-Signatur
- [x] Karten-Bestellung erstellt TSE-Signatur
- [x] QR-Code wird generiert und angezeigt
- [x] Beleg druckbar

**Admin**:
- [x] TSE Status Dashboard zeigt "ONLINE"
- [x] Token Status "Aktiv"
- [x] Statistiken korrekt
- [x] DSFinV-K Export funktioniert

**Frontend**:
- [x] Kasse-Seite lädt
- [x] Bestellungen über UI funktionieren
- [x] TSE-Signatur wird erstellt
- [x] Beleg wird angezeigt

**Performance**:
- [x] Gecachter Token wird verwendet (schnellere Requests)
- [x] Keine Auth-Anfrage bei jeder Transaktion
- [x] Keine Errors in Logs

---

## 🎉 FINALE BESTÄTIGUNG

Wenn alle Tests ✅ sind:

**SYSTEM STATUS**: 🟢 **PRODUKTIONSBEREIT**

**Fehlende Schritte**:
1. ⏳ Warte auf Fiskaly Cloud TSE Credentials
2. Update `.env` mit Production Credentials
3. Re-run alle Tests
4. **GO LIVE!** 🚀

---

## 📝 TEST-ERGEBNIS DOKUMENTATION

**Datum**: _______________
**Getestet von**: _______________

### Basis-Konnektivität
- [ ] Website erreichbar
- [ ] API erreichbar
- [ ] Admin geschützt

### TSE Integration
- [ ] Health Check: PASS
- [ ] Test Suite: 100%
- [ ] Token Cache: Funktioniert

### Transaktionen
- [ ] Bargeld: TSE-Signatur ✅
- [ ] Karte: TSE-Signatur ✅
- [ ] QR-Code: Angezeigt ✅
- [ ] Beleg: Druckbar ✅

### Admin
- [ ] Dashboard: ONLINE
- [ ] Export: Funktioniert

### Frontend
- [ ] Kasse: Lädt
- [ ] Bestellung: Erfolgreich

### Notizen
```
_______________________________________
_______________________________________
_______________________________________
```

**Gesamtergebnis**: ⭐ ⭐ ⭐ ⭐ ⭐

---

**SYSTEM BEREIT FÜR PRODUKTION!** 🎉
