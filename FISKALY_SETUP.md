# 🚀 Fiskaly TSE - Kurulum Rehberi

## ✅ Fiskaly'ye Geçiş Tamamlandı!

TSE servisi **Fiskaly** için güncellendi. Artık hazırsın!

---

## 📝 Adım 1: Fiskaly Hesabı Aç

### 1.1 Kayıt Ol
1. **Website:** https://dashboard.fiskaly.com/signup
2. Email ile kayıt ol
3. Email'ini doğrula
4. Dashboard'a giriş yap

⏱️ **Süre:** 2-3 dakika

---

## 🔑 Adım 2: API Credentials Al

### 2.1 API Key Oluştur

1. Dashboard'da → **Settings** (sol menü)
2. **API Keys** sekmesi
3. **Create API Key** butonuna tıkla
4. **Şunları not et:**
   - ✅ `API Key` (örn: `test_abc123def456...`)
   - ✅ `API Secret` (örn: `secret_xyz789abc123...`)

⚠️ **ÖNEMLİ:** API Secret sadece bir kez gösterilir! Kaydet!

### 2.2 TSS ID Al

1. Dashboard'da → **TSS** (sol menü)
2. Otomatik bir TSS oluşturulmuş olmalı
3. TSS'ye tıkla
4. **TSS ID'yi kopyala** (örn: `01234567-89ab-cdef-0123-456789abcdef`)

---

## ⚙️ Adım 3: Backend'i Konfigüre Et

### 3.1 .env Dosyasını Güncelle

Strato'daki `.env` dosyasını düzenle (FTP veya File Manager ile):

```env
# ==============================================
# FISKALY TSE CONFIGURATION
# ==============================================

FISKALY_API_KEY=your_api_key_here          ← Buraya yapıştır
FISKALY_API_SECRET=your_api_secret_here    ← Buraya yapıştır
FISKALY_TSS_ID=your_tss_id_here            ← Buraya yapıştır
FISKALY_CLIENT_ID=qbab-pos-001             ← Bu değişmeyebilir
```

**Örnek:**
```env
FISKALY_API_KEY=test_abc123def456ghi789jkl012
FISKALY_API_SECRET=secret_xyz789abc123def456ghi789
FISKALY_TSS_ID=01234567-89ab-cdef-0123-456789abcdef
FISKALY_CLIENT_ID=qbab-pos-001
```

### 3.2 Güncellenen Dosyayı Upload Et

1. **FTP ile bağlan:** `5018723982.ssh.w2.strato.hosting`
2. `/public/includes/tse-service.php` dosyasını upload et
3. `.env` dosyasını upload et
4. Dosya izinlerini ayarla:
   ```bash
   chmod 644 includes/tse-service.php
   chmod 600 .env
   ```

---

## ✅ Adım 4: Test Et

### 4.1 Backend Test

```bash
# Browser veya curl ile:
curl https://q-bab.de/api/kasse/get-products.php
```

**Beklenen:** JSON response, hata yok

### 4.2 TSE Test - Satış Yap

1. **Kasa sistemine giriş yap:** https://q-bab.de/kasse/
2. **POS'a git** → Verkauf (POS)
3. **Ürün ekle** → Bezahlen → Nakit ödeme
4. **Başarılı olursa:**
   - "Zahlung erfolgreich!" mesajı
   - Console'da TSE bilgileri görünür

### 4.3 Database'de Kontrol

```sql
SELECT 
  order_number,
  order_source,
  tse_transaction_id,     -- Dolu olmalı
  tse_signature,          -- Dolu olmalı
  tse_qr_code,            -- Dolu olmalı
  created_at
FROM orders 
WHERE order_source = 'KASSE'
ORDER BY created_at DESC 
LIMIT 5;
```

**Beklenen:** TSE alanları dolu olmalı (artık `MOCK_` ile başlamamalı)

### 4.4 Fiskaly Dashboard'da Kontrol

1. https://dashboard.fiskaly.com → **Transactions**
2. Az önce yaptığın satış görünmeli
3. Transaction'a tıkla → detayları gör

---

## 🆚 Fiskaly vs Deutsche Fiskal - Farklar

| Özellik | Fiskaly | Deutsche Fiskal |
|---------|---------|-----------------|
| **Kayıt** | ✅ Online self-service | ⏳ İletişim gerekiyor |
| **Test Ortamı** | ✅ Otomatik | ⏳ İstek gerekiyor |
| **API Dokümantasyonu** | ✅ Çok iyi | ✅ İyi |
| **Fiyat** | €5-10/ay per kasa | Benzer |
| **Entegrasyon** | ✅ Kolay | ✅ Kolay |
| **Destek** | ✅ Chat + Email | 📞 Telefon + Email |

---

## 🔧 Troubleshooting

### Problem 1: "TSE Service: Not configured"

**Çözüm:**
- `.env` dosyasında `FISKALY_*` değerleri doğru mu?
- Dosya Strato'ya upload edildi mi?
- `chmod 600 .env` yapıldı mı?

### Problem 2: "Fiskaly API error: HTTP 401"

**Çözüm:**
- API Key ve Secret doğru kopyalandı mı?
- Extra boşluk var mı? (başta/sonda)
- Tırnak işareti yok değil mi?

### Problem 3: "Invalid signature response"

**Çözüm:**
- TSS ID doğru mu?
- TSS aktif mi? (Fiskaly Dashboard'dan kontrol et)
- Test mode'da mısın? (Production'a geçmek gerekebilir)

### Problem 4: Hala Mock Mode'da çalışıyor

**Çözüm:**
```bash
# Backend'i restart et (FTP'den tse-service.php'yi yeniden upload et)
# Veya Apache'yi restart et (eğer erişim varsa)
```

---

## 📊 Fiskaly Dashboard - Önemli Özellikler

### 1. **Transactions**
- Her satışı görebilirsin
- TSE imzalarını kontrol edebilirsin
- Export yapabilirsin (DSFinV-K)

### 2. **TSS Management**
- TSS durumunu izle
- Yeni TSS oluştur (multi-location için)
- Certificate bilgilerini gör

### 3. **Analytics**
- Günlük/aylık transaction sayısı
- API kullanım istatistikleri
- Error rate

### 4. **Export**
- DSFinV-K formatında export
- Steuerberater için
- Finanzamt için

---

## 💰 Fiyatlandırma

**Test Mode:** Ücretsiz (sınırsız)
**Production:**
- €5/ay - Tek kasa
- €10/ay - 3 kasaya kadar
- €25/ay - 10 kasaya kadar

**İlk 3 ay:** Genelde indirimli veya ücretsiz trial

---

## 🎯 Production'a Geçiş

### Test → Production

1. Fiskaly Dashboard → **Billing**
2. Plan seç (€5/ay)
3. Ödeme bilgilerini gir
4. **Production API Keys** oluştur
5. `.env` dosyasını güncelle (production keys ile)
6. Test yap

**Önemli:** Test mode'da yapılan satışlar silinmez, sadece "test" olarak işaretlenir.

---

## 📞 Destek

### Fiskaly Support
- **Email:** support@fiskaly.com
- **Chat:** Dashboard'da sağ alt köşe
- **Dokümantasyon:** https://docs.fiskaly.com

### Q-Bab Kasse System
- **Deployment Guide:** `KASSE_DEPLOYMENT_GUIDE.md`
- **Backend Logs:** `/logs/error.log`

---

## ✅ Kurulum Tamamlandı Kontrolü

- [ ] Fiskaly hesabı açıldı
- [ ] API Key ve Secret alındı
- [ ] TSS ID kopyalandı
- [ ] `.env` dosyası güncellendi
- [ ] Backend'e upload edildi
- [ ] Test satışı yapıldı
- [ ] TSE imzası database'de
- [ ] Fiskaly Dashboard'da transaction görünüyor

**Hepsi ✅ ise → Production'a geçmeye hazırsın!** 🎉

---

## 📝 Sonraki Adımlar

1. **Frontend Deploy Et** → `q-bab.de/kasse/`
2. **Offline Mode Test Et** → WiFi kapalıyken satış yap
3. **DATEV Export Test Et** → Aylık rapor al
4. **Production'a Geç** → Gerçek satışları kaydet

---

**Herhangi bir sorun olursa bu dosyayı kontrol et veya Fiskaly support'a yaz!** 🚀

