# =============================================================================
# AYKOME Deploy Script — Eyyübiye Belediyesi Sunucusu (Windows + Docker)
# Kullanim:
#   Ilk kurulum: .\deploy.ps1
#   Tag deploy:   .\deploy.ps1 v6.22
# =============================================================================

param(
    [Parameter(Mandatory=$false)]
    [string]$Tag = ""
)

$RED = "Red"; $GREEN = "Green"; $YELLOW = "Yellow"; $CYAN = "Cyan"

Write-Host "╔═══════════════════════════════════════════════╗" -ForegroundColor $CYAN
Write-Host "║     AYKOME v6 — DEPLOY                       ║" -ForegroundColor $CYAN
Write-Host "╚═══════════════════════════════════════════════╝" -ForegroundColor $CYAN
Write-Host ""

# 0. .env kontrol — yoksa .env.docker'dan olustur
Write-Host "⏳ .env kontrol ediliyor..." -ForegroundColor $YELLOW
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.docker") {
        Copy-Item ".env.docker" ".env"
        Write-Host "   ✅ .env.docker -> .env kopyalandi" -ForegroundColor $GREEN
    } elseif (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "   ⚠️  .env.example -> .env kopyalandi" -ForegroundColor $YELLOW
    } else {
        Write-Host "   ❌ .env dosyasi bulunamadi!" -ForegroundColor $RED
        exit 1
    }
} else {
    Write-Host "   ✅ .env mevcut" -ForegroundColor $GREEN
}

# APP_KEY bos mu?
$appKey = Select-String -Path ".env" -Pattern "^APP_KEY=" | ForEach-Object { $_ -replace "^APP_KEY=", "" }
if (-not $appKey -or $appKey -eq "") {
    Write-Host "⏳ APP_KEY olusturuluyor..." -ForegroundColor $YELLOW
    docker exec aykome-v6-serve php artisan key:generate 2>$null
    # container henuz yoksa direkt php ile dene
    if ($LASTEXITCODE -ne 0) {
        php artisan key:generate 2>$null
    }
    Write-Host "   ✅ APP_KEY olusturuldu" -ForegroundColor $GREEN
}

# 1. Git tag (opsiyonel)
if ($Tag) {
    Write-Host "⏳ Git tag aliniyor: $Tag ..." -ForegroundColor $YELLOW
    git fetch --tags 2>&1 | Out-Null
    $checkout = git checkout $Tag 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "   ❌ Tag bulunamadi: $Tag" -ForegroundColor $RED
        exit 1
    }
    Write-Host "   ✅ $Tag checkout edildi" -ForegroundColor $GREEN
} else {
    Write-Host "⏳ Son commit kullaniliyor: $(git log --oneline -1)" -ForegroundColor $YELLOW
}

# 2. Container'lari build et
Write-Host "⏳ Container'lar build ediliyor..." -ForegroundColor $YELLOW
docker compose down 2>&1 | Out-Null
docker compose up -d --build --remove-orphans 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ❌ Build hatasi!" -ForegroundColor $RED
    exit 1
}
Write-Host "   ✅ Container'lar hazir" -ForegroundColor $GREEN

# 3. Oracle'in hazir olmasini bekle
Write-Host "⏳ Oracle bekleniyor (5 dk timeout)..." -ForegroundColor $YELLOW
$oracleReady = $false
for ($i = 0; $i -lt 60; $i++) {
    $ready = docker exec aykome-v6-oracle sqlplus -s "aykome_user/aykome123@FREEPDB1" "SELECT 1 FROM DUAL" 2>$null
    if ($ready -match "1") {
        Write-Host ""
        Write-Host "   ✅ Oracle hazir ($($i * 5) sn)" -ForegroundColor $GREEN
        $oracleReady = $true
        break
    }
    Write-Host "." -NoNewline
    Start-Sleep -Seconds 5
}
if (-not $oracleReady) {
    Write-Host ""
    Write-Host "   ⚠️  Oracle timeout! Devam ediliyor..." -ForegroundColor $YELLOW
}

# 4. APP_KEY generate (container uzerinden)
$appKey = Select-String -Path ".env" -Pattern "^APP_KEY=" | ForEach-Object { $_ -replace "^APP_KEY=", "" }
if (-not $appKey -or $appKey -eq "") {
    Write-Host "⏳ APP_KEY olusturuluyor..." -ForegroundColor $YELLOW
    docker exec aykome-v6-serve php artisan key:generate 2>&1
    Write-Host "   ✅ APP_KEY olusturuldu" -ForegroundColor $GREEN
}

# 5. Migration
Write-Host "⏳ Migration calistiriliyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-serve php artisan migrate --force 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ⚠️  Migration hatasi! Log kontrol edin." -ForegroundColor $YELLOW
} else {
    Write-Host "   ✅ Migration tamam" -ForegroundColor $GREEN
}

# 6. Seed (roller bos ise)
Write-Host "⏳ Seed kontrol ediliyor..." -ForegroundColor $YELLOW
$roleCount = docker exec aykome-v6-serve php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>$null
if ($roleCount -lt 6) {
    docker exec aykome-v6-serve php artisan db:seed --class=DatabaseSeeder 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "   ⚠️  Seed hatasi!" -ForegroundColor $YELLOW
    } else {
        Write-Host "   ✅ Seed tamam" -ForegroundColor $GREEN
    }
} else {
    Write-Host "   ✅ Roller mevcut ($roleCount adet), seed atlandi" -ForegroundColor $GREEN
}

# 7. Cache temizleme
Write-Host "⏳ Cache temizleniyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-serve php artisan config:clear 2>&1 | Out-Null
docker exec aykome-v6-serve php artisan route:clear 2>&1 | Out-Null
docker exec aykome-v6-serve php artisan view:clear 2>&1 | Out-Null
Write-Host "   ✅ Cache temizlendi" -ForegroundColor $GREEN

# 8. Vite build
Write-Host "⏳ Vite build aliniyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-serve npm run build 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ⚠️  Vite build hatasi!" -ForegroundColor $YELLOW
} else {
    Write-Host "   ✅ Vite build tamam" -ForegroundColor $GREEN
}

# 9. E-Imza Electron baslat
Write-Host "⏳ Aykome E-Imza baslatiliyor..." -ForegroundColor $YELLOW
$eimzaDir = Join-Path -Path $PWD -ChildPath "aykome-e-imza"
if (Test-Path $eimzaDir) {
    Push-Location $eimzaDir

    # node_modules yoksa npm install
    if (-not (Test-Path "node_modules")) {
        Write-Host "   → npm install yukleniyor..." -ForegroundColor $YELLOW
        npm install 2>&1 | Out-Null
    }

    # Electron'u arkaplanda baslat
    $env:ELECTRON_IS_DEV = 1
    $electronProcess = Start-Process -FilePath "npx" -ArgumentList "electron ." -PassThru -NoNewWindow -WindowStyle Hidden
    Write-Host "   ✅ E-Imza calisiyor (PID: $($electronProcess.Id))" -ForegroundColor $GREEN

    Pop-Location
} else {
    Write-Host "   ⚠️  aykome-e-imza klasoru bulunamadi!" -ForegroundColor $YELLOW
}

# Sonuc
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor $CYAN
if ($Tag) { Write-Host "  ✅ DEPLOY TAMAM: $Tag" -ForegroundColor $GREEN }
else { Write-Host "  ✅ AYKOME HAZIR" -ForegroundColor $GREEN }
Write-Host "  🌐 Laravel        → http://localhost:8000" -ForegroundColor $CYAN
Write-Host "  🗄  Adminer (GUI) → http://localhost:8080" -ForegroundColor $CYAN
Write-Host "  📦 Redis          → localhost:6379" -ForegroundColor $CYAN
Write-Host "  🔌 Reverb (WS)    → ws://localhost:8090" -ForegroundColor $CYAN
Write-Host "  🖊  E-Imza        → systray'de (arkaplan)" -ForegroundColor $CYAN
Write-Host ""
Write-Host "  ❌ Durdurmak icin: docker compose down" -ForegroundColor $YELLOW
Write-Host "═══════════════════════════════════════════════════════════════════" -ForegroundColor $CYAN
