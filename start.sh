#!/bin/bash
# ======================================================
# AYKOME + E-İmza Başlatma Scripti (Full Stack)
# Kullanım: ./start.sh
# ======================================================

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

echo "═══════════════════════════════════════════════"
echo "       AYKOME v6 - Başlatılıyor...           "
echo "═══════════════════════════════════════════════"

# 1. PID dosyalarını temizle
kill_files() {
    for f in /tmp/laravel.pid /tmp/electron.pid; do
        [ -f "$f" ] && kill $(cat "$f") 2>/dev/null; rm -f "$f"
    done
}
kill_files

# 2. Docker container'ları başlat (Oracle + Redis + Adminer)
echo ""
echo "[1/4] Docker container'lar başlatılıyor..."
if command -v docker &> /dev/null; then
    docker compose up -d 2>&1 | tail -3
    echo "  → Container'lar başlatıldı (Oracle, Redis, Adminer)"
    echo "  → Oracle'ın hazır olması bekleniyor... (30sn)"
    # Oracle'ın hazır olmasını bekle (30sn timeout)
    for i in $(seq 1 30); do
        if docker exec aykome-v6-oracle sqlplus -s aykome_user/aykome123@FREEPDB1 "SELECT 1 FROM DUAL" >/dev/null 2>&1; then
            echo "  ✅ Oracle hazır!"
            break
        fi
        sleep 1
    done
else
    echo "  ⚠️  Docker bulunamadı, SQLite ile devam ediliyor..."
fi

# 3. Laravel hazırlık
echo ""
echo "[2/4] Laravel hazırlığı..."

php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

# Migration
echo "  → Migration çalıştırılıyor..."
php artisan migrate --force 2>&1 | tail -2

# Seed (sadece SQLite'da, tablo boşsa)
ROLE_COUNT=$(php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>/dev/null)
if [ "$ROLE_COUNT" -lt 6 ] 2>/dev/null; then
    echo "  → Veritabanı seed'leniyor..."
    php artisan db:seed --class=DatabaseSeeder 2>&1 | tail -3
else
    echo "  → Roller zaten mevcut, seed atlanıyor"
fi

# 4. Laravel sunucusu
echo ""
echo "[3/4] Laravel sunucu başlatılıyor (port 8001)..."
php artisan serve --port=8001 > /tmp/laravel.log 2>&1 &
echo $! > /tmp/laravel.pid
sleep 2

if curl -s -o /dev/null -w "%{http_code}" http://localhost:8001 | grep -q "200\|302\|404"; then
    echo "  ✅ Laravel: http://localhost:8001"
else
    echo "  ⚠️  Laravel başlatılamadı. Log: /tmp/laravel.log"
    tail -5 /tmp/laravel.log
fi

# 5. Electron
echo ""
echo "[4/4] Aykome E-İmza başlatılıyor..."

cd aykome-e-imza
if [ ! -d node_modules ]; then
    echo "  → npm install yükleniyor..."
    npm install --silent 2>&1 | tail -3
fi

ELECTRON_IS_DEV=1 npx electron . > /tmp/electron.log 2>&1 &
echo $! > /tmp/electron.pid
sleep 2

if ps -p $(cat /tmp/electron.pid) > /dev/null 2>&1; then
    echo "  ✅ Aykome E-İmza çalışıyor (systray'de 🖊 simgesi)"
else
    echo "  ⚠️  E-İmza başlatılamadı. Log: /tmp/electron.log"
fi

cd "$SCRIPT_DIR"

echo ""
echo "═══════════════════════════════════════════════"
echo "       TÜM SİSTEMLER ÇALIŞIYOR ✅             "
echo "═══════════════════════════════════════════════"
echo ""
echo "  🌐 Aykome:     http://localhost:8001"
echo "  🖊  E-İmza:    systray'de (menü çubuğu)"
echo "  🗄  Adminer:   http://localhost:8080"
echo "  📋 Laravel log: /tmp/laravel.log"
echo "  📋 Electron log: /tmp/electron.log"
echo ""
echo "  ❌ Durdurmak için: kill \$(cat /tmp/laravel.pid) \$(cat /tmp/electron.pid)"
echo "═══════════════════════════════════════════════"
