# 🚀 Live Server Setup Guide (q-bab.de)

## Aktuelle Situation

✅ **Dateien hochgeladen**: Alle Dateien erfolgreich auf q-bab.de hochgeladen
⚠️ **Problem gefunden**: `.env` Konfiguration verwendet Middleware statt Cloud TSE
🔧 **Lösung**: `.env` Datei muss angepasst werden

---

## 🔴 KRITISCHER FEHLER - SOFORT BEHEBEN

### Problem
Die `.env` Datei auf dem Live-Server ist auf **Middleware TSE** konfiguriert, sollte aber **Cloud TSE** verwenden:

```env
# ❌ FALSCH (aktuell auf Server):
FISKALY_USE_MIDDLEWARE=true
FISKALY_MIDDLEWARE_URL=http://localhost:8000

# ✅ RICHTIG (sollte so sein):
FISKALY_USE_MIDDLEWARE=false
FISKALY_MIDDLEWARE_URL=
```

### Warum ist das ein Problem?
- System versucht, `http://localhost:8000` zu kontaktieren (existiert nicht auf Production)
- Alle TSE-Signaturen schlagen fehl
- Health Check zeigt: `"healthy": false`
- Keine Transaktionen können signiert werden

---

## 📝 SCHRITT-FÜR-SCHRITT ANLEITUNG

### Schritt 1: Auf Server zugreifen

**Option A: FTP/SFTP**
1. Öffne deinen FTP-Client (FileZilla, WinSCP, etc.)
2. Verbinde zu q-bab.de
3. Navigiere zum Hauptverzeichnis (wo `index.php` liegt)

**Option B: cPanel File Manager**
1. Gehe zu deinem Hosting Control Panel
2. Öffne "File Manager"
3. Navigiere zum Website-Root

**Option C: SSH (falls verfügbar)**
```bash
ssh dein-username@q-bab.de
cd /pfad/zum/website/root
```

### Schritt 2: `.env` Datei bearbeiten

1. **Finde die Datei**: `.env` (im Hauptverzeichnis)
2. **WICHTIG**: Falls `.env` nicht existiert, kopiere `.env.live.example` zu `.env`

3. **Öffne `.env` zum Bearbeiten**

4. **Suche diese Zeilen**:
```env
FISKALY_USE_MIDDLEWARE=true
FISKALY_MIDDLEWARE_URL=http://localhost:8000
```

5. **Ändere zu**:
```env
FISKALY_USE_MIDDLEWARE=false
FISKALY_MIDDLEWARE_URL=
```

6. **Speichere die Datei**

### Schritt 3: Komplette `.env` Konfiguration prüfen

Stelle sicher, dass deine `.env` Datei diese Einstellungen hat:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=qbab_database
DB_USER=dein_db_user
DB_PASS=dein_db_password
DB_CHARSET=utf8mb4

# Site URLs
SITE_URL=https://q-bab.de
ADMIN_URL=https://q-bab.de/admin
ASSETS_URL=https://q-bab.de/assets
UPLOADS_URL=https://q-bab.de/assets/uploads
ASSET_VERSION=3.0.0

# Fiskaly TSE Configuration (PRODUCTION - Cloud TSE)
FISKALY_API_KEY=test_7adkqher3qb4g58zsu13dq097_q-bab
FISKALY_API_SECRET=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM
FISKALY_TSS_ID=df15a626-6b42-45ce-8016-9cb5083dae8a
FISKALY_CLIENT_ID=e6719d2e-b409-638b-e3c2-bffd3d35fda7

# ⚠️ WICHTIG: Auf false setzen für Cloud TSE (KEIN Middleware)
FISKALY_USE_MIDDLEWARE=false
FISKALY_MIDDLEWARE_URL=

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://q-bab.de

# Debug Mode (auf false in Production setzen)
DEBUG_MODE=false
DISPLAY_ERRORS=0

# Email Configuration
SMTP_HOST=smtp.strato.de
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=your_email@q-bab.de
SMTP_PASSWORD=your_email_password

# Admin Contact
ADMIN_EMAIL=admin@q-bab.de
ADMIN_PHONE=+49123456789

# Security
ADMIN_SESSION_TIMEOUT=3600
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900
```

### Schritt 4: Datei-Berechtigungen prüfen

```bash
# Falls du SSH-Zugriff hast:
chmod 644 .env
```

**WICHTIG**: `.env` sollte NICHT öffentlich zugänglich sein!
- Überprüfe, dass `.htaccess` die `.env` Datei schützt
- Versuche https://q-bab.de/.env zu öffnen → sollte "403 Forbidden" zeigen

---

## ✅ TESTEN NACH DER ÄNDERUNG

### Test 1: Health Check
```bash
curl https://q-bab.de/api/kasse/health.php
```

**Erwartetes Ergebnis**:
```json
{
    "success": true,
    "health": {
        "status": "online",
        "healthy": true,
        "message": "TSE system operational"
    },
    "configuration": {
        "enabled": true,
        "api_base_url": "https://kassensichv.fiskaly.com/api/v2",
        "has_token": true
    }
}
```

**Wichtig**: `"healthy": true` und `"api_base_url"` sollte `https://kassensichv.fiskaly.com/api/v2` sein!

### Test 2: Vollständiger Test
```bash
curl https://q-bab.de/api/kasse/test.php
```

**Erwartetes Ergebnis**:
```json
{
    "message": "Q-Bab Kasse - Production Test Suite",
    "tests": {
        "config": { "status": "PASS" },
        "health": { "status": "PASS" },
        "signature": { "status": "PASS" }
    },
    "summary": {
        "total_tests": 3,
        "passed": 3,
        "failed": 0,
        "success_rate": "100%"
    }
}
```

### Test 3: Admin Dashboard (Browser)
1. Öffne: https://q-bab.de/admin/tse-status.php
2. Login mit Admin-Credentials
3. Du solltest sehen:
   - 🟢 **TSE Status: ONLINE**
   - **API URL**: https://kassensichv.fiskaly.com/api/v2
   - **Token Status**: Aktiv
   - Statistiken von heute

### Test 4: Test-Transaktion erstellen

```bash
curl -X POST https://q-bab.de/api/kasse/create-cash-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "name_de": "Test Burger",
        "price": 10.00,
        "quantity": 1
      }
    ],
    "totalAmount": 10.00,
    "paymentMethod": "CASH",
    "cashAmount": 10.00,
    "changeAmount": 0,
    "cashierName": "Test Kassier"
  }'
```

**Erwartetes Ergebnis**:
```json
{
    "success": true,
    "orderId": 123,
    "orderNumber": "KASSE-20251116-001",
    "tseSignature": {
        "transaction_id": "...",
        "signature": "...",
        "qr_code_data": "..."
    }
}
```

### Test 5: Beleg drucken (Browser)
Nach Erstellung einer Test-Bestellung:
```
https://q-bab.de/api/kasse/print-receipt.php?order_id=123
```

Sollte zeigen:
- ✅ Beleg mit TSE-Signatur
- ✅ QR-Code (BSI TR-03153 konform)
- ✅ Alle KassenSichV-Pflichtfelder

---

## 🎯 ERFOLGS-CHECKLISTE

Nach `.env` Änderung sollte alles funktionieren:

- [ ] `.env` Datei bearbeitet (`FISKALY_USE_MIDDLEWARE=false`)
- [ ] Health Check zeigt `"healthy": true`
- [ ] Test Suite zeigt `"success_rate": "100%"`
- [ ] Admin Dashboard zeigt "TSE Status: ONLINE"
- [ ] API URL ist `https://kassensichv.fiskaly.com/api/v2` (NICHT localhost:8000)
- [ ] Test-Transaktion erstellt erfolgreich TSE-Signatur
- [ ] QR-Code wird auf Beleg angezeigt

---

## ⚠️ HÄUFIGE PROBLEME

### Problem 1: "Failed to get auth token"
**Ursache**: FISKALY_USE_MIDDLEWARE ist noch auf `true`
**Lösung**: Auf `false` setzen und Server neu starten (oder PHP Session löschen)

### Problem 2: "localhost:8000" in Health Check
**Ursache**: Alte Konfiguration wird noch verwendet
**Lösung**:
1. `.env` prüfen
2. PHP Session löschen: `curl -X POST https://q-bab.de/api/kasse/clear-token-cache.php`

### Problem 3: ".env not found"
**Ursache**: Datei existiert nicht
**Lösung**: `.env.live.example` zu `.env` kopieren und anpassen

### Problem 4: "401 Unauthorized"
**Ursache**: Fiskaly Credentials ungültig (warten auf Cloud TSE Umstellung)
**Lösung**: Warte auf Antwort von Fiskaly Support, dann Credentials aktualisieren

---

## 🔄 NACH FISKALY SUPPORT ANTWORT

Wenn Fiskaly die Cloud TSE Credentials sendet:

1. **Update `.env` mit neuen Credentials**:
```env
FISKALY_API_KEY=prod_neue_api_key
FISKALY_API_SECRET=neue_api_secret
FISKALY_TSS_ID=neue_tss_id
FISKALY_CLIENT_ID=neue_client_id
```

2. **Token Cache löschen**:
```bash
curl -X POST https://q-bab.de/api/kasse/clear-token-cache.php
```

3. **Erneut testen** (alle Tests von oben)

---

## 📞 SUPPORT

### Dokumentation
- **Haupt-Dokumentation**: `README.md`
- **API Dokumentation**: `FISKALY_API_DOCUMENTATION.md`
- **Changelog**: `FISKALY_CHANGELOG.md`
- **Production Ready**: `PRODUCTION_READY_SUMMARY.md`

### Quick Links
- **Health Check**: https://q-bab.de/api/kasse/health.php
- **Test Suite**: https://q-bab.de/api/kasse/test.php
- **Admin Dashboard**: https://q-bab.de/admin/tse-status.php
- **DSFinV-K Export**: https://q-bab.de/admin/export-dsfinvk.php

### Fiskaly Support
- **Dashboard**: https://dashboard.fiskaly.com
- **Docs**: https://developer.fiskaly.com
- **Email**: support@fiskaly.com

---

## 🎉 ZUSAMMENFASSUNG

**Hauptproblem**: `.env` verwendet Middleware statt Cloud TSE

**Lösung**:
```env
FISKALY_USE_MIDDLEWARE=false
FISKALY_MIDDLEWARE_URL=
```

**Nach der Änderung**: Alle Tests sollten auf 100% Success Rate springen!

**Status**: ⏳ Warte auf Fiskaly Cloud TSE Credentials, dann komplett produktionsbereit! 🚀
