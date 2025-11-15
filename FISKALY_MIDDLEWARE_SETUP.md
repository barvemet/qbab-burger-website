# 🔧 Fiskaly Middleware Kurulum Rehberi

## ⚠️ Neden Middleware?

TSS'niz **Middleware TSE** tipi - doğrudan Cloud API'den erişilemez.
```
Error: E_USE_MIDDLEWARE - The requested resource is available only through the fiskaly sign Middleware
```

---

## 📋 Gereksinimler

- **Server:** Strato hosting (SSH erişimi)
- **Platform:** Linux (Strato genelde Ubuntu/Debian kullanır)
- **Port:** 8000 (veya başka bir port)
- **Fiskaly API Credentials:** ✅ Mevcut

---

## 🚀 Kurulum Seçenekleri

### Seçenek 1: Docker (ÖNERİLEN - Kolay)

#### 1.1 Docker Kurulu mu Kontrol Et
```bash
ssh user@5018723982.ssh.w2.strato.hosting
docker --version
```

**Eğer Docker yoksa:**
```bash
# Strato'da Docker kurulu olmayabilir
# Bu durumda Seçenek 2'ye geç
```

#### 1.2 Docker ile Middleware Çalıştır
```bash
docker run -d \
  --name fiskaly-middleware \
  --restart always \
  -p 8000:8000 \
  -e FISKALY_API_KEY=test_7adkqher3qb4g58zsu13dq097_q-bab \
  -e FISKALY_API_SECRET=JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM \
  fiskaly/middleware:latest
```

#### 1.3 Test Et
```bash
curl http://localhost:8000/health
# Beklenen: {"status":"ok"}
```

---

### Seçenek 2: Binary (Manuel Kurulum)

#### 2.1 Fiskaly Middleware Binary İndir
```bash
# Linux için binary indir
cd ~
wget https://github.com/fiskaly/middleware/releases/download/v2.0.0/fiskaly-middleware-linux-amd64-v2.0.0.tar.gz

# Extract
tar -xzf fiskaly-middleware-linux-amd64-v2.0.0.tar.gz
cd fiskaly-middleware
```

#### 2.2 Konfigürasyon Dosyası Oluştur
```bash
nano config.yaml
```

**config.yaml içeriği:**
```yaml
# Fiskaly Middleware Configuration
api:
  host: 0.0.0.0
  port: 8000

fiskaly:
  api_key: test_7adkqher3qb4g58zsu13dq097_q-bab
  api_secret: JCLZXkJgot5c7pUBzCb6WT1yL7VYR8sFzP0SRQVN9NM
  environment: test  # veya production

logging:
  level: info
  format: json

storage:
  path: /var/lib/fiskaly-middleware/data
```

#### 2.3 Storage Dizini Oluştur
```bash
sudo mkdir -p /var/lib/fiskaly-middleware/data
sudo chown -R $USER:$USER /var/lib/fiskaly-middleware
```

#### 2.4 Middleware'i Çalıştır
```bash
./fiskaly-middleware --config config.yaml
```

#### 2.5 Systemd Service Oluştur (Arka planda çalışsın)
```bash
sudo nano /etc/systemd/system/fiskaly-middleware.service
```

**Service dosyası:**
```ini
[Unit]
Description=Fiskaly Middleware
After=network.target

[Service]
Type=simple
User=your-username
WorkingDirectory=/home/your-username/fiskaly-middleware
ExecStart=/home/your-username/fiskaly-middleware/fiskaly-middleware --config /home/your-username/fiskaly-middleware/config.yaml
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Service'i başlat:**
```bash
sudo systemctl daemon-reload
sudo systemctl enable fiskaly-middleware
sudo systemctl start fiskaly-middleware
sudo systemctl status fiskaly-middleware
```

---

### Seçenek 3: PHP Built-in (Basit Test - Kalıcı Değil)

**Not:** Bu sadece test içindir, production'da kullanmayın!

```bash
# Fiskaly PHP SDK kullan
composer require fiskaly/fiskaly-sdk-php
```

---

## 🔧 Backend Konfigürasyonu

### .env Dosyasına Ekle
```env
# Fiskaly Middleware
FISKALY_USE_MIDDLEWARE=true
FISKALY_MIDDLEWARE_URL=http://localhost:8000

# Eğer farklı server'daysa:
# FISKALY_MIDDLEWARE_URL=http://5018723982.ssh.w2.strato.hosting:8000
```

---

## 🔄 TSE Service Kodunu Güncelle

Kod otomatik olarak middleware kullanacak şekilde güncellenecek.

**Ana değişiklik:** API endpoint değişecek:
```php
// Önceki (direkt API)
$url = 'https://kassensichv.fiskaly.com/api/v2/...';

// Yeni (middleware üzerinden)
$url = 'http://localhost:8000/api/v2/...';
```

---

## ✅ Test Adımları

### 1. Middleware Health Check
```bash
curl http://localhost:8000/health
# veya
curl https://q-bab.de/api/kasse/middleware-health.php
```

**Beklenen:**
```json
{
  "status": "ok",
  "version": "2.0.0"
}
```

### 2. TSS Info Al (Middleware üzerinden)
```bash
curl http://localhost:8000/api/v2/tss/df15a626-6b42-45ce-8016-9cb5083dae8a \
  -H "Authorization: Bearer {jwt_token}"
```

### 3. Client Oluştur
```bash
curl -X PUT http://localhost:8000/api/v2/tss/{tss_id}/client/{client_id} \
  -H "Authorization: Bearer {jwt_token}" \
  -H "Content-Type: application/json" \
  -d '{"serial_number":"qbab-pos-001"}'
```

---

## 🐛 Sorun Giderme

### Problem 1: Middleware başlamıyor
```bash
# Loglara bak
journalctl -u fiskaly-middleware -f

# veya direkt binary çalıştırıp hatayı gör
./fiskaly-middleware --config config.yaml
```

### Problem 2: Port 8000 kullanımda
```bash
# Başka port kullan
# config.yaml'da port'u değiştir (örn: 8001)
api:
  port: 8001

# .env'de de güncelle
FISKALY_MIDDLEWARE_URL=http://localhost:8001
```

### Problem 3: "Connection refused"
```bash
# Middleware çalışıyor mu?
systemctl status fiskaly-middleware

# Port açık mı?
netstat -tlnp | grep 8000

# Firewall?
sudo ufw allow 8000
```

---

## 📊 Strato Özel Notlar

### SSH Erişimi
```bash
ssh username@5018723982.ssh.w2.strato.hosting
```

### Strato'da Docker Yok mu?

Strato shared hosting'de Docker olmayabilir. Alternatifler:

1. **VPS'e geç** (Strato VPS paketi al)
2. **Binary kullan** (Seçenek 2)
3. **Cloud TSS'e geç** (En kolay - yeni TSS oluştur)

---

## 🎯 Özet Akış

```
PHP App → Middleware (localhost:8000) → Fiskaly Cloud API → TSS
```

**Avantajlar:**
- ✅ Offline çalışma desteği
- ✅ Lokal cache
- ✅ Daha hızlı response
- ✅ Güvenli (lokal imzalama)

**Dezavantajlar:**
- ❌ Ekstra kurulum
- ❌ Maintenance gerekiyor
- ❌ Server resource kullanımı

---

## 📞 Destek

- **Fiskaly Middleware Docs:** https://docs.fiskaly.com/docs/middleware/introduction
- **GitHub:** https://github.com/fiskaly/middleware
- **Support:** support@fiskaly.com

---

## 🚨 Alternatif: Cloud TSS Kullan

**Daha kolay yol:**
1. Fiskaly Dashboard → Create New TSS
2. Type: **Cloud TSE** seç
3. Yeni TSS ID'yi `.env`'e ekle
4. Middleware'e gerek yok!

Middleware kurulumu zaman alacaksa, bu yolu dene!

---

**Sonraki adımlar için bana SSH erişimin var mı söyle, ona göre devam edelim!**
