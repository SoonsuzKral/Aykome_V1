# AYKOME v6 Ultra — Kurulum Kilavuzu

## 1. Sistem Gereksinimleri

| Bilesen | Versiyon |
|---|---|
| PHP | ^8.2 |
| MySQL | 8.0+ |
| Oracle | 21c (opsiyonel) |
| Composer | 2.x |
| Node.js | 20+ |
| Docker / OrbStack | (opsiyonel, gelistirme) |

## 2. Hizli Kurulum (Docker ile — Gelistirme)

```bash
# 1. Projeyi kopyala
git clone <repo> .
cd aykome

# 2. Bagimliliklari yukle
composer install --no-interaction
npm install && npm run build

# 3. .env olustur
cp .env.example .env
php artisan key:generate

# 4. Container'lari baslat
./start.sh

# 5. Tarayicidan ac
# Laravel (MySQL):  http://localhost:8000
# Laravel (Oracle): http://localhost:8001
# Oracle Browser:   http://localhost:8080
# DB Switch GUI:    http://localhost:8000/db-switch
```

## 3. VDS / Production Kurulum

### 3.1 Temel Kurulum

```bash
# PHP ve eklentiler
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring \
    php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd unzip curl git nginx

# Oracle OCI8 (Oracle kullanilacaksa)
# sudo apt install -y php8.2-oci8   # PPA uzerinden
# veya Oracle Instant Client + pecl

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs
```

### 3.2 Proje Kurulumu

```bash
cd /var/www/aykome

composer install --no-interaction --optimize-autoloader --no-dev
npm install && npm run build

cp .env.example .env
php artisan key:generate

# .env dosyasini duzenle (DB, Mail vb.)
nano .env
```

### 3.3 Veritabani Ayarlari

#### MySQL ile Calisma
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aykome
DB_USERNAME=root
DB_PASSWORD=sifre
```

#### Oracle ile Calisma
```
DB_CONNECTION=oracle
DB_HOST=127.0.0.1
DB_PORT=1521
DB_SERVICE_NAME=freepdb1
DB_USERNAME=aykome_user
DB_PASSWORD=sifre
```

### 3.4 Migration ve Seed

```bash
# MySQL icin
php artisan migrate --force
php artisan db:seed --force

# Oracle icin (OCI8 yuklu ise)
php artisan migrate --force --database=oracle
php artisan db:seed --force --database=oracle

# MySQL'den Oracle'a veri aktarimi
# Web GUI: http://sunucu-ip/db-switch
```

### 3.5 Nginx Ayari

```nginx
server {
    listen 80;
    server_name aykome.example.com;
    root /var/www/aykome/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 4. Veritabani Degistirme

Proje hem MySQL hem Oracle ile calisabilir.

### Yontem 1: Web GUI (Onerilen)
```
http://sunucu-ip/db-switch
```
Sifre: `.env` icindeki `DB_SWITCH_PASSWORD` degeri

### Yontem 2: .env ile
`.env` dosyasinda `DB_CONNECTION=mysql` veya `DB_CONNECTION=oracle`

### Yontem 3: Docker ile
```bash
./oracle.sh artisan migrate    # Oracle'da migration
./oracle.sh serve               # Oracle ile calistir
```

## 5. Oracle ilk Kurulum (Docker)

```bash
# Container'i baslat
docker compose up -d oracle adminer

# Oracle hazir olana kadar bekle (2-3 dk)
docker logs -f aykome-v6-oracle

# Browser ile Oracle GUI
open http://localhost:8080
# Kullanici: aykome_user
# Sifre: aykome123
# Service: FREEPDB1
```

## 6. Oracle VDS Kurulumu (Docker'siz)

Oracle kullanmak icin OCI8 PHP eklentisi gerekli:

```bash
# Oracle Instant Client
wget https://download.oracle.com/otn_software/linux/instantclient/1923000/instantclient-basic-linux.x64-19.23.0.0.0dbru.zip
unzip instantclient-basic-linux.x64-19.23.0.0.0dbru.zip
sudo mv instantclient_19_23 /opt/oracle/instantclient

# LD ayari
echo /opt/oracle/instantclient | sudo tee /etc/ld.so.conf.d/oracle-instantclient.conf
sudo ldconfig

# OCI8 PHP eklentisi
sudo pecl install oci8
echo "extension=oci8.so" | sudo tee /etc/php/8.2/mods-available/oci8.ini
sudo phpenmod oci8
```

## 7. Harici Sistem Entegrasyonu

AYKOME dis sistemlerle MySQL uzerinden entegre olur.
Oracle kullaniyorsaniz, harici sistem MySQL ile iletisim kurar.

### Entegrasyon icin API Anahtarlari
Admin panel → API Anahtarlari → Yeni API Anahtari olusturun.

### Dogrudan DB Baglantisi
```php
// Harici sistemden AYKOME MySQL'ine baglanti
'mysql' => [
    'host' => env('AYKOME_DB_HOST'),
    'database' => env('AYKOME_DB_DATABASE'),
    'username' => env('AYKOME_DB_USERNAME'),
    'password' => env('AYKOME_DB_PASSWORD'),
]
```

---

## 8. Docker Komutlari

```bash
docker compose up -d          # Tum container'lari baslat
docker compose down           # Container'lari durdur
docker compose logs -f        # Loglari takip et

./start.sh                    # Tek tikla baslat
./oracle.sh migrate           # Oracle migration
./oracle.sh serve             # Oracle ile calistir
./oracle.sh tinker            # Oracle ile tinker
./oracle.sh bash              # PHP container'ina gir

docker exec -it aykome-v6-oracle sqlplus aykome_user/aykome123@FREEPDB1
```

---

*AYKOME v6 Ultra | HGB Bilisim *
