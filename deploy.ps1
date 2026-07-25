# =============================================================================
# AYKOME Deploy Script — Eyyübiye Belediyesi Sunucusu (Windows + Docker)
# Kullanim: .\deploy.ps1 [tag]
# Ornek:   .\deploy.ps1 v6.22
# =============================================================================

param(
    [Parameter(Mandatory=$false)]
    [string]$Tag = (git describe --tags --abbrev=0 2>$null)
)

if (-not $Tag) {
    Write-Host "HATA: Git tag bulunamadi. Kullanim: .\deploy.ps1 v6.22" -ForegroundColor Red
    exit 1
}

$RED = "Red"; $GREEN = "Green"; $YELLOW = "Yellow"; $CYAN = "Cyan"
$NC = "White"

Write-Host "╔═══════════════════════════════════════════════╗" -ForegroundColor $CYAN
Write-Host "║     AYKOME v6.22 — DEPLOY                   ║" -ForegroundColor $CYAN
Write-Host "╚═══════════════════════════════════════════════╝" -ForegroundColor $CYAN
Write-Host ""

Write-Host "🚀 Surum: $Tag" -ForegroundColor $YELLOW
Write-Host ""

# 1. Git tag'e git
Write-Host "⏳ Git tag aliniyor: $Tag ..." -ForegroundColor $YELLOW
git fetch --tags 2>&1 | Out-Null
$checkout = git checkout $Tag 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "HATA: Tag bulunamadi: $Tag" -ForegroundColor $RED
    exit 1
}
Write-Host "✅ $Tag checkout edildi" -ForegroundColor $GREEN

# 2. Container'lari yeniden build et
Write-Host "⏳ Container'lar build ediliyor..." -ForegroundColor $YELLOW
docker compose down 2>&1 | Out-Null
docker compose up -d --build --remove-orphans 2>&1 | Out-Null
Write-Host "✅ Container'lar hazir" -ForegroundColor $GREEN

# 3. Oracle'in hazir olmasini bekle
Write-Host "⏳ Oracle bekleniyor" -ForegroundColor $YELLOW
for ($i = 0; $i -lt 60; $i++) {
    $ready = docker exec aykome-v6-oracle sqlplus -s "aykome_user/aykome123@FREEPDB1" "SELECT 1 FROM DUAL" 2>$null
    if ($ready -match "1") {
        Write-Host ""
        Write-Host "✅ Oracle hazir" -ForegroundColor $GREEN
        break
    }
    Start-Sleep -Seconds 5
    Write-Host "." -NoNewline
}

# 4. Migration
Write-Host "⏳ Migration calistiriliyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-serve php artisan migrate --force 2>&1
Write-Host "✅ Migration tamam" -ForegroundColor $GREEN

# 5. Cache temizleme
Write-Host "⏳ Cache temizleniyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-serve php artisan config:clear 2>&1 | Out-Null
docker exec aykome-v6-serve php artisan route:clear 2>&1 | Out-Null
docker exec aykome-v6-serve php artisan view:clear 2>&1 | Out-Null
Write-Host "✅ Cache temizlendi" -ForegroundColor $GREEN

# 6. Vite build
Write-Host "⏳ Vite build aliniyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-serve npm run build 2>&1
Write-Host "✅ Vite build tamam" -ForegroundColor $GREEN

# Sonuc
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor $CYAN
Write-Host "  ✅ DEPLOY TAMAM: $Tag" -ForegroundColor $GREEN
Write-Host "  ✅ Laravel        → http://localhost:8000" -ForegroundColor $CYAN
Write-Host "  ✅ Adminer (GUI)  → http://localhost:8080" -ForegroundColor $CYAN
Write-Host "  ✅ Redis          → localhost:6379" -ForegroundColor $CYAN
Write-Host "  ✅ Reverb (WS)    → ws://localhost:8090" -ForegroundColor $CYAN
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor $CYAN
