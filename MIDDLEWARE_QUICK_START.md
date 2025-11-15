# ⚡ Fiskaly Middleware - Hızlı Başlangıç

## 🎯 Senaryo

TSS'niz **Middleware TSE** tipi olduğu için middleware kurulumu gerekiyor.

**Hata:**
```
E_USE_MIDDLEWARE - The requested resource is available only through the fiskaly sign Middleware
```

---

## 🚀 En Hızlı Yol: 3 Adımda Kur

### Adım 1: Strato'ya SSH Bağlan

```bash
ssh username@5018723982.ssh.w2.strato.hosting
```

### Adım 2: Middleware Binary İndir ve Çalıştır

```bash
# Ana dizine git
cd ~

# Fiskaly Middleware indir
wget https://github.com/fiskaly/middleware/releases/download/v2.0.0/fiskaly-middleware-linux-amd64-v2.0.0.tar.gz

# Extract
tar -xzf fiskaly-middleware-linux-amd64-v2.0.0.tar.gz
cd fiskaly-middleware

# Direkt çalıştır (test için)
./fiskaly-middleware \
  --api-key=test_7adkqher3qb4g58zsu13dq097_q-bab \
  --api-secret=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM \
  --port=8000
```

**Not:** Terminaliniz açık kalmalı! Arka planda çalıştırmak için Adım 3'e bak.

### Adım 3: .env Dosyasını Güncelle

FTP veya FileZilla ile `.env` dosyasını aç ve ekle:

```env
# Fiskaly Middleware
FISKALY_USE_MIDDLEWARE=true
FISKALY_MIDDLEWARE_URL=http://localhost:8000
```

**Kaydet ve upload et!**

---

## ✅ Test Et

### 1. Middleware Sağlık Kontrolü
```
https://q-bab.de/api/kasse/middleware-health.php
```

**Beklenen:**
```json
{
  "success": true,
  "message": "Middleware is healthy and operational!",
  "results": {
    "health_check": { "success": true },
    "api_auth": { "success": true },
    "tss_access": { "success": true }
  }
}
```

### 2. TSE Flow Testi
```
https://q-bab.de/api/kasse/test-complete-flow.php
```

**Artık çalışmalı!** ✅

---

## 🔄 Arka Planda Çalıştır (Kalıcı)

Middleware'in server restart'ta bile çalışmaya devam etmesi için:

```bash
# Systemd service oluştur
sudo nano /etc/systemd/system/fiskaly-middleware.service
```

**Dosya içeriği:**
```ini
[Unit]
Description=Fiskaly Middleware
After=network.target

[Service]
Type=simple
User=USERNAME_BURAYA
WorkingDirectory=/home/USERNAME_BURAYA/fiskaly-middleware
ExecStart=/home/USERNAME_BURAYA/fiskaly-middleware/fiskaly-middleware --api-key=test_7adkqher3qb4g58zsu13dq097_q-bab --api-secret=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM --port=8000
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Not:** `USERNAME_BURAYA` yerine kendi kullanıcı adını yaz!

**Servisi başlat:**
```bash
sudo systemctl daemon-reload
sudo systemctl enable fiskaly-middleware
sudo systemctl start fiskaly-middleware

# Kontrol et
sudo systemctl status fiskaly-middleware
```

---

## 🐛 Sorun Giderme

### "Permission denied" hatası
```bash
chmod +x ./fiskaly-middleware
```

### "Port 8000 already in use"
Başka port kullan:
```bash
./fiskaly-middleware --port=8001

# .env'de güncelle:
FISKALY_MIDDLEWARE_URL=http://localhost:8001
```

### Middleware çalışmıyor
```bash
# Loglara bak
./fiskaly-middleware --debug

# veya systemd ile çalıştırıyorsan:
journalctl -u fiskaly-middleware -f
```

---

## 📊 Strato Shared Hosting Sorunları

Strato shared hosting'de:
- ❌ `sudo` erişimi olmayabilir
- ❌ Systemd kullanamayabilirsin
- ❌ Port 8000 kapalı olabilir

**Çözüm:**
1. **VPS'e geç** (Middleware için ideal)
2. **Veya:** Cloud TSS kullan (middleware gerekmez!)

---

## 🎯 Alternatif: Cloud TSS Kullan

Eğer middleware kurulumu çok karmaşık gelirse:

1. **Fiskaly Dashboard'a git:** https://dashboard.fiskaly.com
2. **TSS → Create New TSS**
3. **Type: Cloud TSE** seç (Middleware değil!)
4. Yeni TSS ID'yi kopyala
5. `.env` güncelle:
   ```env
   FISKALY_TSS_ID=<yeni-cloud-tss-id>
   FISKALY_USE_MIDDLEWARE=false
   ```

Bu yol **çok daha kolay!** Middleware kurulumuna gerek kalmaz.

---

## 📁 Dosya Yapısı

```
~/fiskaly-middleware/
├── fiskaly-middleware         (binary)
├── config.yaml                (opsiyonel)
└── data/                      (otomatik oluşur)
    └── tss_data.db
```

---

## 🎬 Özet Komutlar

```bash
# 1. İndir ve extract
wget https://github.com/fiskaly/middleware/releases/download/v2.0.0/fiskaly-middleware-linux-amd64-v2.0.0.tar.gz
tar -xzf fiskaly-middleware-linux-amd64-v2.0.0.tar.gz
cd fiskaly-middleware

# 2. Çalıştır
chmod +x ./fiskaly-middleware
./fiskaly-middleware \
  --api-key=test_7adkqher3qb4g58zsu13dq097_q-bab \
  --api-secret=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM \
  --port=8000

# 3. Başka terminal'de test et
curl http://localhost:8000/health
```

---

## 📞 Yardım

- **Detaylı rehber:** [FISKALY_MIDDLEWARE_SETUP.md](FISKALY_MIDDLEWARE_SETUP.md)
- **Middleware health test:** `/api/kasse/middleware-health.php`
- **Fiskaly Docs:** https://docs.fiskaly.com/docs/middleware

---

**SSH erişimin var mı? Varsa bu adımları takip et. Yoksa Cloud TSS'e geç!**
