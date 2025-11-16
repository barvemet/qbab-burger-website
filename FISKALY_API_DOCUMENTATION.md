# Fiskaly TSE API Documentation
## Q-Bab Burger POS System - KassenSichV Compliant

**Version:** 3.0
**Last Updated:** 2025-11-16
**Compliance:** German KassenSichV & §146a AO

---

## 📋 Overview

This document describes the Fiskaly TSE (Technische Sicherheitseinrichtung) integration for the Q-Bab Burger POS system. The integration is **fully backend-based** - no sensitive credentials or API calls are exposed to the frontend.

### Architecture

```
Frontend (POS UI)
    ↓ POST /api/kasse/create-cash-order.php
Backend (PHP)
    ↓ TSEService Class
Fiskaly Cloud TSE API
    ↓ Bearer JWT Authentication
Secure TSE Signature
```

---

## 🔧 Configuration

### Environment Variables (.env)

```env
# Fiskaly TSE Configuration (REQUIRED)
FISKALY_API_KEY=test_your_api_key_here
FISKALY_API_SECRET=your_api_secret_here
FISKALY_TSS_ID=your-tss-uuid-here
FISKALY_CLIENT_ID=your-client-uuid-here

# Middleware (OPTIONAL - only for Middleware TSE)
FISKALY_USE_MIDDLEWARE=false
FISKALY_MIDDLEWARE_URL=http://localhost:8000
```

### Security Notes

✅ **CORRECT:**
- All credentials in `.env` file (backend only)
- `.env` file in `.gitignore`
- Frontend calls only backend endpoints
- Bearer JWT token cached in PHP session

❌ **INCORRECT:**
- Never put API keys in frontend JavaScript
- Never commit `.env` to Git
- Never expose Fiskaly endpoints to frontend

---

## 📡 API Endpoints

### 1. Health Check

**Endpoint:** `GET /api/kasse/health.php`
**Purpose:** Check TSE connectivity and status
**Authentication:** None (public endpoint)

**Response (200 OK):**
```json
{
  "success": true,
  "timestamp": "2025-11-16 14:30:00",
  "health": {
    "status": "online",
    "healthy": true,
    "tss_id": "df15a626-6b42-45ce-8016-9cb5083dae8a",
    "tss_state": "INITIALIZED",
    "client_id": "e4da3b7f-bbce-3e99-bd82-a5e3e2cf9f13",
    "api_url": "https://kassensichv.fiskaly.com/api/v2",
    "token_cached": true,
    "token_expires_in": 3542
  },
  "configuration": {
    "enabled": true,
    "has_token": true
  }
}
```

**Response (503 Service Unavailable):**
```json
{
  "success": true,
  "health": {
    "status": "disabled",
    "healthy": false,
    "message": "TSE service is not configured"
  }
}
```

**Usage Example:**
```javascript
// Frontend health check
fetch('/api/kasse/health.php')
  .then(res => res.json())
  .then(data => {
    if (data.health.healthy) {
      console.log('TSE Online ✅');
    } else {
      console.warn('TSE Offline ⚠️');
    }
  });
```

---

### 2. Create Cash Order (with TSE Signature)

**Endpoint:** `POST /api/kasse/create-cash-order.php`
**Purpose:** Create order and get TSE signature
**Authentication:** None (CORS enabled)

**Request Body:**
```json
{
  "items": [
    {
      "id": 1,
      "name_de": "Classic Burger",
      "price": 8.50,
      "quantity": 2,
      "extras": [
        {
          "id": 5,
          "name_de": "Extra Käse",
          "price": 1.00
        }
      ]
    }
  ],
  "totalAmount": 19.00,
  "discountAmount": 0.00,
  "paymentMethod": "CASH",
  "cashAmount": 20.00,
  "changeAmount": 1.00,
  "cashierName": "Max Mustermann",
  "orderNotes": "",
  "isOffline": false
}
```

**Field Descriptions:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `items` | Array | Yes | Array of order items |
| `items[].id` | Integer | No | Product ID from database |
| `items[].name_de` | String | Yes | Product name (German) |
| `items[].price` | Float | Yes | Unit price (€) |
| `items[].quantity` | Integer | Yes | Quantity |
| `items[].extras` | Array | No | Additional items (cheese, bacon, etc.) |
| `totalAmount` | Float | Yes | Total including tax (€) |
| `discountAmount` | Float | No | Discount amount (€) |
| `paymentMethod` | String | Yes | "CASH" or "CARD" |
| `cashAmount` | Float | Conditional | Amount given (required for CASH) |
| `changeAmount` | Float | Conditional | Change to return (required for CASH) |
| `cashierName` | String | Yes | Name of cashier |
| `orderNotes` | String | No | Additional notes |
| `isOffline` | Boolean | No | Skip TSE if true (offline mode) |

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Bestellung erfolgreich erstellt!",
  "data": {
    "order_id": 12345,
    "order_number": "KASSE-20251116-00042",
    "total_amount": 19.00,
    "payment_method": "CASH",
    "cash_change": 1.00,
    "tse_data": {
      "transaction_id": "a7b8c9d0-1234-5678-abcd-ef1234567890",
      "transaction_number": 4523,
      "signature": "MEUCIQDx...base64...",
      "signature_counter": 4523,
      "signature_algorithm": "ecdsa-plain-SHA256",
      "public_key": "BG5V...base64...",
      "time_start": 1700145832,
      "time_end": 1700145835,
      "serial_number": "df15a626-6b42-45ce-8016-9cb5083dae8a",
      "qr_code_data": "V0;ZGYxNWE2MjYtNmI0Mi00NWNlLTgwMTYtOWNiNTA4M2RhZThhO...full_qr...",
      "log_time": "2025-11-16 14:30:35",
      "client_id": "e4da3b7f-bbce-3e99-bd82-a5e3e2cf9f13"
    },
    "created_at": "2025-11-16 14:30:35"
  }
}
```

**Response (500 Error):**
```json
{
  "success": false,
  "message": "Fehler bei der Bestellerstellung: Failed to initialize TSE transaction"
}
```

**Usage Example:**
```javascript
// Frontend POS system
const orderData = {
  items: cart.items,
  totalAmount: cart.total,
  paymentMethod: 'CASH',
  cashAmount: givenAmount,
  changeAmount: change,
  cashierName: currentCashier,
  isOffline: !navigator.onLine
};

fetch('/api/kasse/create-cash-order.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(orderData)
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    // Print receipt with TSE QR code
    printReceipt(data.data);
    console.log('TSE Signature:', data.data.tse_data.signature);
  }
});
```

---

## 🔐 TSEService Class (Backend)

### Location
`includes/tse-service.php`

### Usage

```php
<?php
// Initialize TSE service
define('ALLOW_INCLUDE', true);
require_once 'includes/config.php';
require_once 'includes/tse-service.php';

$tseService = getTSEService();

// Check if enabled
if ($tseService->isEnabled()) {
    echo "TSE is configured ✅\n";
}

// Sign a transaction
$signature = $tseService->signTransaction(
    orderId: 'ORDER-12345',
    amount: 25.50,
    paymentType: 'Bar',      // 'Bar' or 'Karte'
    transactionId: null,     // Optional: pre-initialized TX ID
    items: []                // Optional: detailed items
);

if ($signature && !isset($signature['error'])) {
    echo "Signature: " . $signature['signature'] . "\n";
    echo "QR Code: " . $signature['qr_code_data'] . "\n";
} else {
    echo "Error: " . ($signature['message'] ?? 'Unknown error') . "\n";
}

// Check health
$health = $tseService->checkHealth();
print_r($health);

// Get config info
$config = $tseService->getConfig();
print_r($config);

// Clear token cache (if needed)
$tseService->clearTokenCache();
?>
```

### Public Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `isEnabled()` | - | bool | Check if TSE is configured |
| `getConfig()` | - | array | Get configuration info |
| `checkHealth()` | - | array | Check TSE connectivity |
| `signTransaction()` | orderId, amount, paymentType, transactionId, items | array\|null | Sign transaction with TSE |
| `initTransaction()` | processType | array\|null | Initialize new transaction |
| `exportDSFinVK()` | startDate, endDate | array | Export for tax authorities |
| `clearTokenCache()` | - | void | Clear cached auth token |

---

## 🧪 Testing

### 1. Test Health Check
```bash
curl http://localhost/api/kasse/health.php
```

### 2. Test Cash Order
```bash
curl -X POST http://localhost/api/kasse/create-cash-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{
      "name_de": "Test Burger",
      "price": 10.00,
      "quantity": 1
    }],
    "totalAmount": 10.00,
    "paymentMethod": "CASH",
    "cashAmount": 10.00,
    "changeAmount": 0.00,
    "cashierName": "Test",
    "isOffline": false
  }'
```

### 3. Test with Mock Mode (TSE disabled)
Set in `.env`:
```env
FISKALY_API_KEY=
FISKALY_API_SECRET=
FISKALY_TSS_ID=
```

The system will automatically use mock mode and return test signatures.

---

## 📊 TSE Data Fields

### Signature Response Fields

| Field | Type | Description | Required for Receipt |
|-------|------|-------------|---------------------|
| `transaction_id` | String (UUID) | Unique transaction ID | ✅ |
| `transaction_number` | Integer | Sequential transaction number | ✅ |
| `signature` | String (Base64) | Cryptographic signature | ✅ |
| `signature_counter` | Integer | Signature counter | ✅ |
| `signature_algorithm` | String | Algorithm used (ecdsa-plain-SHA256) | ✅ |
| `public_key` | String (Base64) | TSE public key | ✅ |
| `time_start` | Integer (Unix) | Transaction start timestamp | ✅ |
| `time_end` | Integer (Unix) | Transaction end timestamp | ✅ |
| `serial_number` | String (UUID) | TSS serial number | ✅ |
| `qr_code_data` | String | Complete QR code data (BSI TR-03153) | ✅ |
| `client_id` | String (UUID) | Client ID (POS terminal ID) | ✅ |
| `log_time` | String (DateTime) | Logging timestamp | - |

### QR Code Format (BSI TR-03153)

```
V0;base64(TssId);base64(ProcessType);base64(ProcessData);TxNumber;SignatureCounter;StartTime;EndTime;Algorithm;TimeFormat;base64(Signature);base64(PublicKey)
```

Example:
```
V0;ZGYxNWE2MjYtNmI0Mi00NWNlLTgwMTYtOWNiNTA4M2RhZThhO;S2Fzc2VuYmVsZWctVjE=;;4523;4523;1700145832;1700145835;ecdsa-plain-SHA256;unixTime;TUVVQ0lRRHg...;Qkc1Vi4uLg==
```

---

## 🛠️ Token Caching

### How It Works

1. **First Request:** TSEService authenticates with Fiskaly API, receives JWT token
2. **Token Storage:** Token stored in PHP `$_SESSION` with expiry time
3. **Subsequent Requests:** Cached token reused (valid for 1 hour)
4. **Token Refresh:** Automatic refresh if token expired or 401 error received
5. **Retry Logic:** Automatic retry with new token on auth failure

### Benefits

- ⚡ **Performance:** Reduces API calls by ~95%
- 🔒 **Security:** Token never exposed to frontend
- 📈 **Reliability:** Automatic token refresh on expiry
- 💰 **Cost Savings:** Fewer auth requests to Fiskaly

### Cache Duration

```php
// Default: 1 hour (can be configured)
private const TOKEN_CACHE_DURATION = 3600;
```

---

## ⚠️ Error Handling

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| `Failed to get auth token` | Invalid API credentials | Check `.env` credentials |
| `TSE service is not configured` | Missing .env variables | Add Fiskaly credentials to `.env` |
| `E_USE_MIDDLEWARE` (HTTP 432) | Middleware TSE requires middleware | Install middleware or contact Fiskaly |
| `HTTP 401` | Token expired | Automatic retry with new token |
| `Failed to initialize TSE transaction` | TSS not initialized | System auto-initializes on first use |

### Error Response Format

```json
{
  "error": true,
  "message": "Fiskaly API error: HTTP 400 - ...",
  "order_id": "KASSE-20251116-00042"
}
```

---

## 📜 Compliance

### German KassenSichV Requirements

✅ **Implemented:**
- TSE signature on every transaction
- Transaction counter (signature_counter)
- Cryptographic security (ecdsa-plain-SHA256)
- QR code generation (BSI TR-03153)
- DSFinV-K export capability
- Tamper-proof storage in database

### Receipt Requirements

Every receipt MUST contain:
1. TSE transaction number
2. TSE serial number
3. Signature (as QR code)
4. Signature counter
5. Start/end timestamps
6. Payment type
7. Total amount with VAT

---

## 🚀 Production Checklist

Before going live:

- [ ] Set production Fiskaly credentials in `.env`
- [ ] Verify `.env` is in `.gitignore`
- [ ] Test TSE signature on live server (`/api/kasse/health.php`)
- [ ] Ensure database backups enabled
- [ ] Test receipt printing with QR code
- [ ] Train staff on offline mode (isOffline: true)
- [ ] Contact Fiskaly if using Middleware TSE
- [ ] Enable SSL/HTTPS on production server
- [ ] Test DSFinV-K export for tax office
- [ ] Document emergency procedures

---

## 📞 Support

### Fiskaly Support
- Dashboard: https://dashboard.fiskaly.com
- Documentation: https://developer.fiskaly.com
- Email: support@fiskaly.com

### System Support
- GitHub: https://github.com/barvemet/qbab-burger-website
- Error Logs: `logs/error.log`

---

**Document Version:** 1.0
**Last Updated:** 2025-11-16
**Maintained By:** Q-Bab Development Team
