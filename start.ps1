# =============================================================================
# AYKOME v6 — Development Start (Windows Native + Docker)
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

$CYAN = "Cyan"; $GREEN = "Green"; $YELLOW = "Yellow"; $RED = "Red"
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

Write-Host "-======================================================¬" -ForegroundColor $CYAN
Write-Host "¦       AYKOME v6 — DEVELOPMENT START                 ¦" -ForegroundColor $CYAN
Write-Host "¦   Laravel (native) + Oracle + Redis (Docker)        ¦" -ForegroundColor $CYAN
Write-Host "L======================================================-" -ForegroundColor $CYAN

# ---------------------------------------------------------------------------
# 1. Docker Container'lar
# ---------------------------------------------------------------------------
Write-Host "`n[1/7] Docker container'lar baslatiliyor..." -ForegroundColor $YELLOW
docker compose up -d oracle redis 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ? Docker hatasi!" -ForegroundColor $RED
    exit 1
}
Write-Host "   ? Oracle + Redis container'lari baslatildi" -ForegroundColor $GREEN

# ---------------------------------------------------------------------------
# 2. Oracle Bekle
# ---------------------------------------------------------------------------
Write-Host "[2/7] Oracle hazir olana kadar bekleniyor..." -ForegroundColor $YELLOW
$oracleReady = $false
for ($i = 1; $i -le 60; $i++) {
    $ready = echo "SELECT 1 FROM DUAL;" | docker exec -i aykome-v6-oracle sqlplus -s aykome_user/aykome123@FREEPDB1 2>$null
    if ($ready -match "1") {
        Write-Host "`n   ? Oracle hazir ($($i * 5) sn)" -ForegroundColor $GREEN
        $oracleReady = $true
        break
    }
    Write-Host "." -NoNewline
    if ($i % 12 -eq 0) { Write-Host (" {0} sn" -f ($i * 5)) -ForegroundColor $YELLOW }
    Start-Sleep -Seconds 5
}
if (-not $oracleReady) { Write-Host "`n   ??  Oracle timeout!" -ForegroundColor $YELLOW }

# ---------------------------------------------------------------------------
# 3. php artisan serve
# ---------------------------------------------------------------------------
Write-Host "[3/7] Laravel serve baslatiliyor (port 8001)..." -ForegroundColor $YELLOW
$serveJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; php artisan serve --port=8001 --host=0.0.0.0" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 2
Write-Host "   ? php artisan serve (PID: $($serveJob.Id))" -ForegroundColor $GREEN

# ---------------------------------------------------------------------------
# 4. npm run dev (Vite HMR)
# ---------------------------------------------------------------------------
Write-Host "[4/7] Vite dev server baslatiliyor (port 5173)..." -ForegroundColor $YELLOW
$viteJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; npm run dev" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 2
Write-Host "   ? npm run dev (PID: $($viteJob.Id))" -ForegroundColor $GREEN

# ---------------------------------------------------------------------------
# 5. php artisan reverb:start
# ---------------------------------------------------------------------------
Write-Host "[5/7] Reverb baslatiliyor (port 8090)..." -ForegroundColor $YELLOW
$reverbJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; php artisan reverb:start --host=0.0.0.0 --port=8090" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 2
Write-Host "   ? php artisan reverb:start (PID: $($reverbJob.Id))" -ForegroundColor $GREEN

# ---------------------------------------------------------------------------
# 6. php artisan queue:work
# ---------------------------------------------------------------------------
Write-Host "[6/7] Queue worker baslatiliyor..." -ForegroundColor $YELLOW
$queueJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$ScriptDir'; php artisan queue:work --sleep=3 --tries=3" -WindowStyle Minimized -PassThru
Start-Sleep -Seconds 2
Write-Host "   ? php artisan queue:work (PID: $($queueJob.Id))" -ForegroundColor $GREEN

# ---------------------------------------------------------------------------
# 7. E-Imza Electron
# ---------------------------------------------------------------------------
Write-Host "[7/7] E-Imza baslatiliyor..." -ForegroundColor $YELLOW
$eimzaDir = Join-Path $ScriptDir "aykome-e-imza"
if (Test-Path $eimzaDir) {
    $env:ELECTRON_IS_DEV = "1"
    $electronJob = Start-Process -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "cd '$eimzaDir'; `$env:ELECTRON_IS_DEV='1'; npx.cmd electron ." -WindowStyle Minimized -PassThru
    Start-Sleep -Seconds 3
    Write-Host "   ? E-Imza Electron baslatildi" -ForegroundColor $GREEN
} else {
    Write-Host "   ??  aykome-e-imza klasoru bulunamadi" -ForegroundColor $YELLOW
}

# ---------------------------------------------------------------------------
# Sonuc
# ---------------------------------------------------------------------------
Write-Host ""
Write-Host "-======================================================¬" -ForegroundColor $GREEN
Write-Host "¦       ? TUM SERVISLER CALISIYOR                    ¦" -ForegroundColor $GREEN
Write-Host "L======================================================-" -ForegroundColor $GREEN
Write-Host ""
Write-Host "  ?? Laravel    › http://localhost:8001" -ForegroundColor $CYAN
Write-Host "  ? Vite HMR   › http://localhost:5173" -ForegroundColor $CYAN
Write-Host "  ?? Reverb    › ws://localhost:8090" -ForegroundColor $CYAN
Write-Host "  ??  Adminer  › http://localhost:8080 (isteyen manuel baslatir)" -ForegroundColor $CYAN
Write-Host "  ??  E-Imza   › systray'de (arkaplan)" -ForegroundColor $CYAN
Write-Host ""
Write-Host "  Oracle: aykome_user / aykome123 @ FREEPDB1" -ForegroundColor $YELLOW
Write-Host "  Redis:  localhost:6379" -ForegroundColor $YELLOW
Write-Host ""
Write-Host "  ? Durdurmak icin kapatma scripti calistirin:" -ForegroundColor $RED
Write-Host "     .\stop.ps1" -ForegroundColor $RED
Write-Host ""

