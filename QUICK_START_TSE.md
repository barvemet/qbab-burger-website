# 🚀 Fiskaly TSE - Hızlı Başlangıç

## ⚠️ ÖNEMLİ: Authentication Düzeltildi!

**Sorun giderildi:** Bearer token authentication eklendi. Artık çalışmalı! 🎉

---

## ⚡ 3 Adımda Çalıştır

### 1️⃣ Konfigürasyon Kontrol
`.env` dosyasında şunlar olmalı:
```env
FISKALY_API_KEY=test_7adkqher3qb4g58zsu13dq097_q-bab
FISKALY_API_SECRET=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM
FISKALY_TSS_ID=df15a626-6b42-45ce-8016-9cb5083dae8a
FISKALY_CLIENT_ID=qbab-pos-001
```

### 2️⃣ Önce Debug ile Kontrol Et
```bash
# Browser'da aç:
https://q-bab.de/api/kasse/fiskaly-debug.php

# Kontrol et:
# - api_tests.auth.success = true ✅
# - api_tests.get_tss.success = true ✅
# - api_tests.get_tss.response.state = "INITIALIZED" ✅
```

### 3️⃣ TSS'yi Aktifleştir (Gerekirse)
```bash
# Browser'da aç:
https://q-bab.de/api/kasse/initialize-tss.php

# Eğer zaten initialized ise:
{
  "success": true,
  "message": "TSS is already initialized!",
  "state": "INITIALIZED"
}
```

### 4️⃣ Tam Akış Testi
```bash
# Tam akış testi:
https://q-bab.de/api/kasse/test-complete-flow.php

# Başarılı ise:
{
  "success": true,
  "message": "Complete TSE flow test successful!",
  "results": {
    "step2_health": { "healthy": true },
    "step3_init_transaction": { "transaction_id": "..." },
    "step4_sign_transaction": { "signature": "MEU..." }
  }
}
```

---

## ✅ Hızlı Kontrol

### Debug Bilgileri
```
https://q-bab.de/api/kasse/fiskaly-debug.php
```
**Bakmak istediğin:**
- `environment.FISKALY_API_KEY.loaded` → `true`
- `api_tests.get_tss.http_code` → `200`
- `api_tests.get_tss.response.state` → `"INITIALIZED"`

### TSS Durumu
```
https://q-bab.de/api/kasse/check-tss-status.php
```
**Olması gereken:**
- `current_state` → `"INITIALIZED"` ✅

---

## 🎯 Gerçek Satış Testi

1. **Kasa aç:** https://q-bab.de/kasse/
2. **POS** → Ürün seç → **Bezahlen**
3. **Nakit** → Ödeme tamamla
4. **Başarılı!** Console'da TSE bilgileri görünmeli

### Database'de Kontrol
```sql
SELECT
  order_number,
  tse_transaction_id,
  tse_signature
FROM orders
WHERE order_source = 'KASSE'
ORDER BY created_at DESC
LIMIT 1;
```

**Dolu olmalı:**
- `tse_transaction_id` → UUID formatında
- `tse_signature` → Base64 string

---

## 🐛 Sorun mu var?

### "TSE Service: Not configured"
→ `.env` dosyasını kontrol et, Fiskaly bilgileri eksik

### "HTTP 401"
→ API Key/Secret yanlış, Fiskaly Dashboard'dan yeni key al

### "TSS state is CREATED"
→ `initialize-tss.php` çalıştır (yukarıda Adım 2)

### "Failed to initialize transaction"
→ `fiskaly-debug.php` ile detaylı bilgi al

---

## 📁 Önemli Dosyalar

| Dosya | Açıklama |
|-------|----------|
| [includes/tse-service.php](includes/tse-service.php) | Ana TSE servisi (DÜZELTİLDİ ✅) |
| [api/kasse/fiskaly-debug.php](api/kasse/fiskaly-debug.php) | Debug bilgileri |
| [api/kasse/test-complete-flow.php](api/kasse/test-complete-flow.php) | Tam akış testi |
| [api/kasse/initialize-tss.php](api/kasse/initialize-tss.php) | TSS başlatma |
| [api/kasse/create-cash-order.php](api/kasse/create-cash-order.php) | Gerçek satış (TSE imzalı) |

---

## 🔧 Yapılan Düzeltmeler

### İlk Düzeltme:
1. ✅ API endpoint: `middleware.fiskaly.com` → `kassensichv.fiskaly.com`
2. ✅ TSS initialization otomatiği eklendi
3. ✅ Client management iyileştirildi
4. ✅ Hata yönetimi geliştirildi

### İkinci Düzeltme (SON):
5. ✅ **Authentication FIX:** Basic Auth → Bearer Token (JWT)
6. ✅ İki aşamalı auth eklendi (`/auth` → JWT → API calls)
7. ✅ Token caching eklendi (performance)
8. ✅ Tüm test dosyaları güncellendi

**Detaylı bilgiler:**
- [FISKALY_AUTH_FIX.md](FISKALY_AUTH_FIX.md) - Son authentication düzeltmesi
- [FISKALY_TSE_FIX.md](FISKALY_TSE_FIX.md) - İlk düzeltmeler

---

## 📞 Support

- **Fiskaly Dashboard:** https://dashboard.fiskaly.com
- **Fiskaly Docs:** https://docs.fiskaly.com
- **Test Files:** `/api/kasse/*.php`

---

**Status:** ✅ Hazır, test edilebilir!
**Son güncelleme:** 2024-11-15
