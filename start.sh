#!/bin/bash
# =============================================================================
# AYKOME v6.21 — Oracle + MySQL Dual DB
# =============================================================================

set -e
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'

echo -e "${CYAN}╔═══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║     AYKOME v6.21 — Oracle + MySQL         ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════╝${NC}"

# Eski container'lari temizle (sadece orphan kalmissa)
docker rm -f aykome-v6-serve aykome-v6-php 2>/dev/null || true

# Tum container'lari docker compose ile baslat
echo -e "${YELLOW}⏳ Container'lar baslatiliyor...${NC}"
docker compose up -d --remove-orphans 2>&1 | grep -E "(Started|Healthy|Built|Error)" || true

# Oracle'in hazir olmasini bekle
echo -n "⏳ Oracle bekleniyor"
for i in $(seq 1 60); do
    if docker ps --format '{{.Names}}' | grep -q "^aykome-v6-oracle$"; then
        if docker exec aykome-v6-oracle sqlplus -s "aykome_user/aykome123@FREEPDB1" "SELECT 1 FROM DUAL" >/dev/null 2>&1; then
            echo -e "\n${GREEN}✅ Oracle hazir${NC}"
            break
        fi
    fi
    sleep 5
    echo -n "."
done

# Migration
echo -e "${YELLOW}⏳ Migration calistiriliyor...${NC}"
docker exec aykome-v6-serve php artisan migrate --force 2>&1 | tail -3

# Servis durumunu goster
echo -e "\n${CYAN}═══════════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  ✅ Laravel (Oracle)     → http://localhost:8001${NC}"
echo -e "${CYAN}  ✅ Adminer (GUI)        → http://localhost:8080${NC}"
echo -e "${CYAN}  ✅ Redis                → localhost:6379${NC}"
echo -e "${CYAN}  ✅ Reverb (WS)          → ws://localhost:8090${NC}"
echo -e "${CYAN}  ✅ DB Switch GUI        → http://localhost:8001/db-switch${NC}"
echo -e "${CYAN}  Cikmak icin: Ctrl+C${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════════${NC}"

# Vite dev server (arka planda)
echo -e "${YELLOW}⏳ Vite dev server baslatiliyor...${NC}"
npm run dev &>/dev/null &
echo -e "${CYAN}  ✅ Vite (Dev)            → http://localhost:5173${NC}"

# Laravel log'u takip et
docker logs -f aykome-v6-serve
