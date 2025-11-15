# ✅ Fiskaly Authentication Hatası Düzeltildi

## 🔴 Sorun

Authentication hatası:
```json
{
  "status_code": 401,
  "error": "Unauthorized",
  "code": "E_UNAUTHORIZED",
  "message": "Authorization header must follow the format \"Authorization: Bearer ...\""
}
```

## 🔧 Neden Oluştu?

Fiskaly API v2 **iki aşamalı authentication** kullanıyor:
1. **Önce** API Key + Secret ile `/auth` endpoint'inden **JWT token** alınmalı
2. **Sonra** bu JWT token ile `Authorization: Bearer {token}` header'ı gönderilmeli

Önceki kod direkt Basic Auth kullanmaya çalışıyordu - bu yanlış!

## ✅ Düzeltme

### Authentication Flow

#### ❌ Önceki (Yanlış):
```php
// Direct Basic Auth (ÇALIŞMAZ!)
$authToken = base64_encode($apiKey . ':' . $apiSecret);
Authorization: Basic {authToken}
```

#### ✅ Yeni (Doğru):
```php
// Step 1: Get JWT token
POST https://kassensichv.fiskaly.com/api/v2/auth
Content-Type: application/json
Body: {
  "api_key": "test_...",
  "api_secret": "..."
}

Response: {
  "access_token": "eyJhbGciOiJSUzI1NiIs..."
}

// Step 2: Use JWT token
GET https://kassensichv.fiskaly.com/api/v2/tss/{tss_id}
Authorization: Bearer eyJhbGciOiJSUzI1NiIs...
```

## 📁 Güncellenen Dosyalar

### 1. [includes/tse-service.php](includes/tse-service.php)
**Fonksiyon:** `getAuthToken()`
- ✅ `/auth` endpoint'e POST request
- ✅ API Key + Secret gönderiliyor
- ✅ JWT token alınıyor ve cache'leniyor
- ✅ Authorization header: `Bearer {jwt_token}`

**Satırlar:** [105-152](includes/tse-service.php#L105-L152)

### 2. [api/kasse/fiskaly-debug.php](api/kasse/fiskaly-debug.php)
- ✅ Auth test eklendi
- ✅ JWT token alma testi
- ✅ TSS bilgileri Bearer token ile çekiliyor

### 3. [api/kasse/check-tss-status.php](api/kasse/check-tss-status.php)
- ✅ İki aşamalı auth
- ✅ Hata kontrolü iyileştirildi

### 4. [api/kasse/initialize-tss.php](api/kasse/initialize-tss.php)
- ✅ İki aşamalı auth
- ✅ TSS state kontrolü eklendi
- ✅ INITIALIZED state için hata vermez artık

## 🚀 Nasıl Test Edilir?

### 1. Debug Test
```bash
curl https://q-bab.de/api/kasse/fiskaly-debug.php
```

**Beklenen çıktı:**
```json
{
  "api_tests": {
    "auth": {
      "endpoint": "POST /auth",
      "http_code": 200,
      "success": true,
      "token_obtained": true  ← ✅ Bu true olmalı
    },
    "get_tss": {
      "http_code": 200,
      "success": true,
      "response": {
        "state": "INITIALIZED"  ← ✅ Bu INITIALIZED olmalı
      }
    }
  }
}
```

### 2. TSS Status Kontrolü
```bash
curl https://q-bab.de/api/kasse/check-tss-status.php
```

**Beklenen:**
```json
{
  "success": true,
  "http_code": 200,
  "current_state": "INITIALIZED"
}
```

### 3. Initialize TSS (Gerekirse)
```bash
curl https://q-bab.de/api/kasse/initialize-tss.php
```

**Eğer zaten initialized ise:**
```json
{
  "success": true,
  "message": "TSS is already initialized!",
  "state": "INITIALIZED",
  "info": "No action needed. TSS is ready to use."
}
```

### 4. Tam Akış Testi
```bash
curl https://q-bab.de/api/kasse/test-complete-flow.php
```

**Başarılı yanıt:**
```json
{
  "success": true,
  "message": "Complete TSE flow test successful!",
  "results": {
    "step2_health": {
      "status": "online",
      "healthy": true
    },
    "step3_init_transaction": {
      "transaction_id": "abc-123-...",
      "transaction_number": 1
    },
    "step4_sign_transaction": {
      "signature": "MEUCIQDabc...",
      "qr_code_data": "V0;..."
    }
  }
}
```

## 🎯 Token Caching

JWT token **cache'leniyor** (performance için):
```php
private $authToken = null;

private function getAuthToken() {
    if ($this->authToken) {
        return $this->authToken; // ✅ Cached token
    }

    // Get new token...
    $this->authToken = $data['access_token'];
    return $this->authToken;
}
```

**Avantaj:** Her API request için yeniden auth yapmıyor!

## 🐛 Sorun Giderme

### Hala "HTTP 401 Unauthorized" alıyorsanız:

1. **API credentials kontrol:**
```bash
# .env dosyasında:
FISKALY_API_KEY=test_7adkqher3qb4g58zsu13dq097_q-bab
FISKALY_API_SECRET=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM
```

2. **Auth endpoint test:**
```bash
curl -X POST https://kassensichv.fiskaly.com/api/v2/auth \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "test_7adkqher3qb4g58zsu13dq097_q-bab",
    "api_secret": "JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM"
  }'
```

**Beklenen:** HTTP 200 + `access_token`

3. **API Key expired mi?**
   - Fiskaly Dashboard → Settings → API Keys
   - Yeni key oluştur
   - `.env` güncelle

### "TSS state is CREATED" hatası:

TSS farklı bir state'de olabilir:
- `UNINITIALIZED` → `initialize-tss.php` çalıştır
- `INITIALIZED` → ✅ Hazır!
- `CREATED` → Fiskaly support'a danış
- `DISABLED` → TSS deaktif, yeniden aktif et

## 📊 Authentication Flow Diagram

```
┌─────────────┐
│  Your Code  │
└──────┬──────┘
       │
       │ 1. POST /auth
       │    {api_key, api_secret}
       ▼
┌─────────────────┐
│  Fiskaly API    │
│  /auth endpoint │
└──────┬──────────┘
       │
       │ 2. Returns JWT
       │    {access_token: "eyJ..."}
       ▼
┌─────────────┐
│  Your Code  │
│  (cache JWT)│
└──────┬──────┘
       │
       │ 3. All requests use JWT
       │    Authorization: Bearer eyJ...
       ▼
┌─────────────────┐
│  Fiskaly API    │
│  /tss, /tx, etc │
└─────────────────┘
```

## ✅ Checklist

- [x] Authentication method düzeltildi (Basic → Bearer)
- [x] JWT token caching eklendi
- [x] Tüm test dosyaları güncellendi
- [x] TSS state kontrolü eklendi
- [ ] `fiskaly-debug.php` ile test et
- [ ] `check-tss-status.php` ile TSS durumunu kontrol et
- [ ] `test-complete-flow.php` ile full flow test et
- [ ] Gerçek satış testi yap (POS)

## 📞 Support

- **Fiskaly Dashboard:** https://dashboard.fiskaly.com
- **Fiskaly API Docs:** https://docs.fiskaly.com/api/v2
- **Test Files:** `/api/kasse/*.php`

---

**Status:** ✅ Authentication düzeltildi, test edilebilir!
**Son güncelleme:** 2024-11-15
