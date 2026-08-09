# AYKOME SSL KURULUM PLANI

## 1. MEVCUT DURUM

| Özellik | Değer |
|---|---|
| Sunucu | Windows Server 2025 — 172.16.1.41 |
| Web Server | PHP built-in (`php -S 0.0.0.0:80`) |
| Docker | Oracle + Redis çalışıyor |
| PHP Container | `aykome-v6-serve` (port 8001) |
| DNS | `aykome.eyyubiye.bel.tr` → 172.16.1.41 ✅ |
| SSL Sertifikası | Belediye IT'den alındı (STAR_eyyubiye klasörü) |
| Dış erişim | Açıldı ama **404 veriyor** |

## 2. SORUN: NEDEN 404?

PHP built-in server (`php -S`):
- **Apache/Nginx gibi URL rewriting YAPMAZ**
- `.htaccess` dosyasını OKUMAZ
- Gelen URL'i doğrudan dosya olarak arar: `/maps` → `C:\Aykome\public\maps` arar, bulamazsa 404
- Laravel'in URL rewriting'i (`index.php`'ye yönlendirme) çalışmaz

**Çözüm:** Nginx reverse proxy kullanarak URL rewriting + SSL terminator yap.

## 3. ÇÖZÜM: Docker Nginx + SSL

### Adım 1: docker-compose.yml'e Nginx EKLE

`C:\Aykome\docker-compose.yml` dosyasında `services:` bloğunun içine (en son servis olarak) şunu ekle:

```yaml
  nginx:
    image: nginx:alpine
    container_name: aykome-v6-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf:ro
      - ./public:/app/public:ro
      - ./storage:/app/storage:ro
      - ./docker/nginx/ssl:/etc/nginx/ssl:ro
    depends_on:
      - serve
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

### Adım 2: Nginx Config Dosyası Oluştur

`C:\Aykome\docker\nginx\nginx.conf`:

```nginx
server {
    listen 80;
    server_name aykome.eyyubiye.bel.tr;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name aykome.eyyubiye.bel.tr;

    ssl_certificate     /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    root /app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        proxy_pass http://serve:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /storage/ {
        alias /app/storage/;
    }
}
```

### Adım 3: SSL Klasörü Oluştur

```powershell
mkdir C:\Aykome\docker\nginx\ssl
```

`STAR_eyyubiye` klasöründen şu dosyaları kopyala:
- `fullchain.pem` olarak → sertifika (eğer certificate.crt + ca_bundle.crt ayrıysa birleştir)
- `privkey.pem` olarak → private key

Birleştirme (eğer ayrıysa):
```powershell
copy /B certificate.crt + ca_bundle.crt C:\Aykome\docker\nginx\ssl\fullchain.pem
copy private.key C:\Aykome\docker\nginx\ssl\privkey.pem
```

### Adım 4: PHP Built-in Server'ı Durdur

```powershell
# Port 80'deki PHP'yi bul
netstat -ano | findstr :80

# PID'den durdur (örnek: 12356)
taskkill /PID 12356 /F

# Port 443'teki sahte PHP'yi durdur
netstat -ano | findstr :443
taskkill /PID [PORT443_PID] /F
```

### Adım 5: Serve Container'ı Başlat

```powershell
cd C:\Aykome
docker compose up -d serve
```

### Adım 6: Nginx Container'ı Başlat

```powershell
docker compose up -d nginx
```

### Adım 7: .env Güncelle

`C:\Aykome\.env`:
```
APP_URL=https://aykome.eyyubiye.bel.tr
```

### Adım 8: Test

```powershell
# İç ağdan
curl -I https://localhost:443
curl -I https://aykome.eyyubiye.bel.tr
```

## 4. ALTERNATİF: Windows Nginx (Docker'sız)

```powershell
# 1. İndir: https://nginx.org/en/download.html → nginx/Windows
# 2. C:\nginx\ klasörüne çıkart
# 3. Yukarıdaki nginx.conf'u C:\nginx\conf\nginx.conf yap
#    proxy_pass http://127.0.0.1:80 yaz (serve:8000 yerine)
# 4. SSL'leri C:\nginx\ssl\ klasörüne kopyala
# 5. Çalıştır:
cd C:\nginx
start nginx
# 6. Durdurmak için:
nginx -s stop
```

## 5. SORUN GİDERME

| Sorun | Çözüm |
|---|---|
| 404 devam ediyor | `docker logs aykome-v6-nginx` ile log kontrol |
| PHP bağlanamıyor | `docker logs aykome-v6-serve` ile kontrol |
| Sertifika hatası | fullchain.pem + privkey.pem doğru eşleşiyor mu kontrol et |
| Port 80 çakışıyor | `netstat -ano \| findstr :80` ile hangi process var bul |
| Windows Docker çalışmıyor | `docker compose ps` ile container durumunu kontrol et |
