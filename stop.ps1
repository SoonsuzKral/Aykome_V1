# =============================================================================
# AYKOME v6 — Development Stop
# =============================================================================
Write-Host "? Tum servisler durduruluyor..." -ForegroundColor "Yellow"

# Kill artisan serve, vite, reverb, queue processes
Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -match "artisan|serve|reverb|queue" } | Stop-Process -Force 2>$null
Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -match "vite|npm" } | Stop-Process -Force 2>$null
Get-Process -Name "electron" -ErrorAction SilentlyContinue | Stop-Process -Force 2>$null

Write-Host "   ? Servisler durduruldu" -ForegroundColor "Green"

Write-Host "? Docker container'lar durduruluyor..." -ForegroundColor "Yellow"
docker compose down 2>&1 | Out-Null
Write-Host "   ? Container'lar durduruldu" -ForegroundColor "Green"
