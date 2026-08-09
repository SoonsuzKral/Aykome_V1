# =============================================================================
# AYKOME v6 - Development Start (Windows Native + Docker)
# Kullanim: PowerShell'de .\start.ps1
#
# Bu script:
#   1. Docker: oracle + redis container'larini baslatir
#   2. Oracle hazir olana kadar bekler
#   3. php artisan serve (port 8001) yeni pencerede baslatir
#   4. npm run dev (Vite HMR) yeni pencerede baslatir
#   5. php artisan reverb:start yeni pencerede baslatir
#   6. php artisan queue:work yeni pencerede baslatir
#   7. E-Imza Electron uygulamasini baslatir
# =============================================================================

$CYAN = "Cyan"
$GREEN = "Green"
$YELLOW = "Yellow"
$RED = "Red"
$ScriptDir = $PSScriptRoot

if (-not $ScriptDir) {
    $ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}

Set-Location $ScriptDir

Write-Host ""
Write-Host "==========================================================" -ForegroundColor $CYAN
Write-Host "        AYKOME v6 - DEVELOPMENT START" -ForegroundColor $CYAN
Write-Host "   Laravel (native) + Oracle + Redis (Docker)" -ForegroundColor $CYAN
Write-Host "==========================================================" -ForegroundColor $CYAN
Write-Host ""

# ---------------------------------------------------------------------------
# 1. Docker Container'lar
# ---------------------------------------------------------------------------
Write-Host "[1/7] Docker container'lar baslatiliyor..." -ForegroundColor $YELLOW

# Once container'lari kontrol et, durumlari
$oracleStatus = docker ps -a --filter "name=aykome-v6-oracle" --format "{{.Status}}" 2>$null
$redisStatus = docker ps -a --filter "name=aykome-v6-redis" --format "{{.Status}}" 2>$null

# Container'lari baslat
docker start aykome-v6-oracle aykome-v6-redis 2>$null | Out-Null

if ($LASTEXITCODE -ne 0) {
    Write-Host "   [HATA] Docker baslatilamadi!" -ForegroundColor $RED
    Write-Host "   Docker'in calistigindan emin olun." -ForegroundColor $RED
    exit 1
}

Write-Host "   [OK] Oracle + Redis container'lari baslatildi" -ForegroundColor $GREEN

# ---------------------------------------------------------------------------
# 2. Oracle Bekle
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "[2/7] Oracle hazir olana kadar bekleniyor..." -ForegroundColor $YELLOW
$oracleReady = $false

for ($i = 1; $i -le 60; $i++) {
    try {
        $output = docker exec aykome-v6-oracle bash -c "echo 'SELECT 1 FROM DUAL;' | sqlplus -s aykome_user/aykome123@FREEPDB1" 2>$null
        if ($output -match "1") {
            Write-Host ""
            Write-Host "   [OK] Oracle hazir!" -ForegroundColor $GREEN
            $oracleReady = $true
            break
        }
    } catch {}

    Write-Host "." -NoNewline
    if ($i % 10 -eq 0) {
        Write-Host " ($($i * 2) sn)" -ForegroundColor $YELLOW
    }
    Start-Sleep -Seconds 2
}

if (-not $oracleReady) {
    Write-Host ""
    Write-Host "   [UYARI] Oracle timeout! Laravel calismayabilir." -ForegroundColor $YELLOW
}

# ---------------------------------------------------------------------------
# 3. php artisan serve
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "[3/7] Laravel serve baslatiliyor (port 8001)..." -ForegroundColor $YELLOW

$laravelJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; php artisan serve --port=8001 --host=0.0.0.0" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 3

if ($laravelJob.HasExited) {
    Write-Host "   [HATA] Laravel baslatilamadi!" -ForegroundColor $RED
} else {
    Write-Host "   [OK] php artisan serve (PID: $($laravelJob.Id))" -ForegroundColor $GREEN
}

# ---------------------------------------------------------------------------
# 4. npm run dev (Vite HMR)
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "[4/7] Vite dev server baslatiliyor (port 5173)..." -ForegroundColor $YELLOW

$viteJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; npm.cmd run dev" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 3

if ($viteJob.HasExited) {
    Write-Host "   [HATA] Vite baslatilamadi!" -ForegroundColor $RED
} else {
    Write-Host "   [OK] npm run dev (PID: $($viteJob.Id))" -ForegroundColor $GREEN
}

# ---------------------------------------------------------------------------
# 5. php artisan reverb:start
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "[5/7] Reverb baslatiliyor (port 8090)..." -ForegroundColor $YELLOW

$reverbJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; php artisan reverb:start --host=0.0.0.0 --port=8090" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 3

if ($reverbJob.HasExited) {
    Write-Host "   [HATA] Reverb baslatilamadi!" -ForegroundColor $RED
} else {
    Write-Host "   [OK] php artisan reverb:start (PID: $($reverbJob.Id))" -ForegroundColor $GREEN
}

# ---------------------------------------------------------------------------
# 6. php artisan queue:work
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "[6/7] Queue worker baslatiliyor..." -ForegroundColor $YELLOW

$queueJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; php artisan queue:work --sleep=3 --tries=3" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 3

if ($queueJob.HasExited) {
    Write-Host "   [HATA] Queue worker baslatilamadi!" -ForegroundColor $RED
} else {
    Write-Host "   [OK] php artisan queue:work (PID: $($queueJob.Id))" -ForegroundColor $GREEN
}

# ---------------------------------------------------------------------------
# 7. E-Imza Electron
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "[7/7] E-Imza baslatiliyor..." -ForegroundColor $YELLOW

$eimzaDir = Join-Path $ScriptDir "aykome-e-imza"
if (Test-Path $eimzaDir) {
    $env:ELECTRON_IS_DEV = "1"

    $electronJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$eimzaDir'; `$env:ELECTRON_IS_DEV='1'; npx.cmd electron ." -WindowStyle Minimized -PassThru
    Start-Sleep -Seconds 5

    if ($electronJob.HasExited) {
        Write-Host "   [HATA] E-Imza baslatilamadi!" -ForegroundColor $RED
    } else {
        Write-Host "   [OK] E-Imza Electron baslatildi (PID: $($electronJob.Id))" -ForegroundColor $GREEN
    }
} else {
    Write-Host "   [UYARI] aykome-e-imza klasoru bulunamadi" -ForegroundColor $YELLOW
}

# ---------------------------------------------------------------------------
# Sonuc
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "==========================================================" -ForegroundColor $GREEN
Write-Host "        [OK] TUM SERVISLER CALISIYOR!" -ForegroundColor $GREEN
Write-Host "==========================================================" -ForegroundColor $GREEN
Write-Host ""
Write-Host "  Laravel:    http://localhost:8001" -ForegroundColor $CYAN
Write-Host "  Vite HMR:   http://localhost:5173" -ForegroundColor $CYAN
Write-Host "  Reverb:     ws://localhost:8090" -ForegroundColor $CYAN
Write-Host "  E-Imza:     systray'de (arkaplan)" -ForegroundColor $CYAN
Write-Host ""
Write-Host "  Oracle:     aykome_user / aykome123 @ FREEPDB1" -ForegroundColor $YELLOW
Write-Host "  Redis:      localhost:6379" -ForegroundColor $YELLOW
Write-Host ""
Write-Host "==========================================================" -ForegroundColor $CYAN

