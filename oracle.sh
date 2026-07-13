#!/bin/bash
# =============================================================================
# AYKOME Oracle Helper Script
# Kullanim: ./oracle.sh <artisan-komutu>
# Ornek:   ./oracle.sh migrate
#          ./oracle.sh db:seed --class=AykomeSeeder
#          ./oracle.sh tinker
#          ./oracle.sh bash
# =============================================================================

set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'

COMPOSE_FILE="docker-compose.yml"

# Oracle container'in calistigindan emin ol
if ! docker ps --format '{{.Names}}' | grep -q "^aykome-v6-oracle$"; then
    echo -e "${YELLOW}Oracle container calismiyor. Baslatiliyor...${NC}"
    docker compose -f "$COMPOSE_FILE" up -d oracle adminer
    echo -e "${YELLOW}Oracle baslamasi bekleniyor...${NC}"
    for i in $(seq 1 30); do
        if docker logs aykome-v6-oracle 2>&1 | grep -q "DATABASE IS READY TO USE!"; then
            echo -e "${GREEN}Oracle hazir!${NC}"
            break
        fi
        sleep 5
        echo -n "."
    done
    echo ""
fi

echo -e "${CYAN}==========================================${NC}"
echo -e "${CYAN}  AYKOME Oracle CLI${NC}"
echo -e "${CYAN}  DB: aykome_user@freepdb1${NC}"
echo -e "${CYAN}==========================================${NC}"

TTY=""
if [ -t 0 ]; then
    TTY="-it"
else
    TTY="-i"
fi

if [ "$1" = "bash" ]; then
    shift
    docker run $TTY --rm \
        --network host \
        -v "$(pwd):/app" \
        -w /app \
        -e DB_CONNECTION=oracle \
        -e DB_HOST=host.docker.internal \
        -e DB_PORT=1521 \
        -e DB_SERVICE_NAME=freepdb1 \
        -e DB_USERNAME=aykome_user \
        -e DB_PASSWORD=aykome123 \
        -e APP_KEY=$(grep ^APP_KEY .env | cut -d= -f2-) \
        -e APP_ENV=local \
        aykome-v6-php \
        bash "$@"
elif [ "$1" = "composer" ]; then
    shift
    docker run $TTY --rm \
        --network host \
        -v "$(pwd):/app" \
        -w /app \
        aykome-v6-php \
        composer "$@"
elif [ "$1" = "serve" ]; then
    PORT="${2:-8001}"
    echo -e "${YELLOW}Laravel Oracle dev server: http://localhost:${PORT}${NC}"
    echo -e "${YELLOW}Oracle Browser (GUI):      http://localhost:8080${NC}"
    echo ""
    shift 2>/dev/null
    docker run $TTY --rm \
        --network host \
        -v "$(pwd):/app" \
        -w /app \
        -e DB_CONNECTION=oracle \
        -e DB_HOST=host.docker.internal \
        -e DB_PORT=1521 \
        -e DB_SERVICE_NAME=freepdb1 \
        -e DB_USERNAME=aykome_user \
        -e DB_PASSWORD=aykome123 \
        -e APP_KEY=$(grep ^APP_KEY .env | cut -d= -f2-) \
        -e APP_ENV=local \
        -e APP_DEBUG=true \
        -e APP_URL=http://localhost:${PORT} \
        -e SESSION_DRIVER=file \
        -e CACHE_STORE=file \
        -e QUEUE_CONNECTION=sync \
        -e LOG_CHANNEL=stderr \
        aykome-v6-php \
        php artisan serve --host=0.0.0.0 --port=${PORT}
else
    docker run $TTY --rm \
        --network host \
        -v "$(pwd):/app" \
        -w /app \
        -e DB_CONNECTION=oracle \
        -e DB_HOST=host.docker.internal \
        -e DB_PORT=1521 \
        -e DB_SERVICE_NAME=freepdb1 \
        -e DB_USERNAME=aykome_user \
        -e DB_PASSWORD=aykome123 \
        -e APP_KEY=$(grep ^APP_KEY .env | cut -d= -f2-) \
        -e APP_ENV=local \
        -e APP_DEBUG=true \
        -e APP_URL=http://localhost:8001 \
        -e SESSION_DRIVER=file \
        -e CACHE_STORE=file \
        -e QUEUE_CONNECTION=sync \
        -e LOG_CHANNEL=stderr \
        aykome-v6-php \
        php artisan "$@"
fi
