# =============================================================================
# AYKOME v6 - Stop All Services
# Kullanim: PowerShell'de .\stop.ps1
# =============================================================================

$CYAN = "Cyan"
$GREEN = "Green"
$YELLOW = "Yellow"
$RED = "Red"

Write-Host ""
Write-Host "==========================================================" -ForegroundColor $YELLOW
Write-Host "        AYKOME v6 - SERVISLER DURDURULUYOR" -ForegroundColor $YELLOW
Write-Host "==========================================================" -ForegroundColor $YELLOW
Write-Host ""

# 1. Docker container'lari durdur
Write-Host "[1/3] Docker container'lari durduruluyor..." -ForegroundColor $YELLOW
docker stop aykome-v6-oracle aykome-v6-redis 2>$null | Out-Null
if ($LASTEXITCODE -eq 0) {
    Write-Host "   [OK] Oracle + Redis durduruldu" -ForegroundColor $GREEN
} else {
    Write-Host "   [UYARI] Container'lar zaten durgun olabilir" -ForegroundColor $YELLOW
}

# 2. PHP artisan serve (port 8001)
Write-Host ""
Write-Host "[2/3] Laravel serve kapatiliyor..." -ForegroundColor $YELLOW
Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*artisan serve*" } | Stop-Process -Force -ErrorAction SilentlyContinue
Write-Host "   [OK] Laravel kapatildi" -ForegroundColor $GREEN

# 3. Node prosesleri (Vite, Electron)
Write-Host ""
Write-Host "[3/3] Node/Electron prosesleri kapatiliyor..." -ForegroundColor $YELLOW
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Get-Process -Name "electron" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Get-Process -Name "electron.exe" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Write-Host "   [OK] Node/Electron prosesleri kapatildi" -ForegroundColor $GREEN

Write-Host ""
Write-Host "==========================================================" -ForegroundColor $GREEN
Write-Host "        [OK] TUM SERVISLER DURDURULDU!" -ForegroundColor $GREEN
Write-Host "==========================================================" -ForegroundColor $GREEN
Write-Host ""
