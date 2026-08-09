# =============================================================================
# AYKOME v6 — Zero-to-Running Setup Script (Windows Native + Docker)
# Kullanim:
#   PowerShell: .\setup.ps1
#
# MIMARI:
#   Docker'da: Oracle + Redis (sadece altyapi)
#   Windows'ta: PHP/Laravel + Vite + Reverb + Queue + E-Imza
#
# Bu script:
#   1. On kosullari kontrol eder (PHP, Composer, Node, Docker)
#   2. .env dosyasini olusturur
#   3. Docker container'lari baslatir (oracle + redis)
#   4. Oracle hazir olana kadar bekler
#   5. composer install
#   6. APP_KEY olustur
#   7. Migration + Seed
#   8. Vite build
#   9. storage:link
# =============================================================================

param(
    [Parameter(Mandatory=$false)]
    [switch]$NoEImza
)

$RED = "Red"; $GREEN = "Green"; $YELLOW = "Yellow"; $CYAN = "Cyan"; $MAGENTA = "Magenta"

function Write-OK { param([string]$Msg); Write-Host "   ? $Msg" -ForegroundColor $GREEN }
function Write-Fail { param([string]$Msg); Write-Host "   ? $Msg" -ForegroundColor $RED }
function Write-Warn { param([string]$Msg); Write-Host "   ??  $Msg" -ForegroundColor $YELLOW }

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

Write-Host ""
Write-Host "-==============================================================¬" -ForegroundColor $MAGENTA
Write-Host "¦              AYKOME v6 — FULL SETUP                         ¦" -ForegroundColor $MAGENTA
Write-Host "¦   Laravel (Windows Native) + Oracle + Redis (Docker)        ¦" -ForegroundColor $MAGENTA
Write-Host "L==============================================================-" -ForegroundColor $MAGENTA

# =============================================================================
# 1. ON KOSUL KONTROLLERI
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 1. ON KOSUL KONTROLLERI" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

$allChecksPass = $true

# PHP
try {
    $phpVer = php -v 2>&1 | Select-String "^PHP"
    if ($phpVer) {
        Write-OK "PHP: $($phpVer.Matches.Value)"
        # oci8 kontrol
        $ociCheck = php -m 2>&1 | Select-String "oci8"
        if ($ociCheck) {
            Write-OK "OCI8 extension: aktif"
        } else {
            Write-Warn "OCI8 extension yuklu degil! php.ini'de extension=oci8_19 ekleyin"
        }
    } else {
        throw "PHP bulunamadi"
    }
} catch {
    Write-Fail "PHP kurulu degil! XAMPP ile kurun"
    $allChecksPass = $false
}

# Composer
try {
    $compVer = composer --version 2>&1 | Select-String "^Composer"
    if ($compVer) {
        Write-OK "Composer: $($compVer.Matches.Value)"
    } else {
        throw "Composer bulunamadi"
    }
} catch {
    Write-Fail "Composer kurulu degil! https://getcomposer.org"
    $allChecksPass = $false
}

# Node.js
try {
    $nodeVer = node --version 2>&1
    if ($nodeVer) {
        Write-OK "Node.js: $nodeVer"
    } else {
        throw "Node bulunamadi"
    }
} catch {
    Write-Fail "Node.js kurulu degil! https://nodejs.org"
    $allChecksPass = $false
}

# npm
try {
    $npmVer = npm --version 2>&1
    if ($npmVer) {
        Write-OK "npm: $npmVer"
    } else {
        throw "npm bulunamadi"
    }
} catch {
    Write-Warn "npm bulunamadi"
}

# Docker
try {
    $dockerVer = docker --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-OK "Docker: $dockerVer"
    } else {
        throw "Docker bulunamadi"
    }
} catch {
    Write-Fail "Docker Desktop kurulu degil! https://www.docker.com/products/docker-desktop"
    $allChecksPass = $false
}

if (-not $allChecksPass) {
    Write-Host "`n? Eksikler var, lutfen yukaridaki hatalari cozun." -ForegroundColor $RED
    exit 1
}

# Port kontrol
$ports = @{ 1521 = "Oracle DB"; 6379 = "Redis"; 8001 = "Laravel"; 8090 = "Reverb" }
$portConflicts = @()
foreach ($port in $ports.Keys) {
    $inUse = netstat -an | Select-String ":$port\s" | Select-String "LISTEN"
    if ($inUse) { $portConflicts += $port }
}
if ($portConflicts.Count -gt 0) {
    Write-Warn ("Su portlar mesgul: " + ($portConflicts -join ", "))
} else {
    Write-OK "Gerekli portlarin tamami bos"
}

# =============================================================================
# 2. .env OLUSTURMA
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 2. .ENV DOSYASI" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

if (-not (Test-Path ".env")) {
    if (Test-Path ".env.docker") {
        Copy-Item ".env.docker" ".env"
        Write-OK ".env.docker -> .env kopyalandi"
    } elseif (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Warn ".env.example -> .env kopyalandi"
    } else {
        Write-Fail ".env kaynak dosyasi bulunamadi"
        exit 1
    }
} else {
    Write-OK ".env mevcut"
}

# APP_KEY kontrol
$appKey = Select-String -Path ".env" -Pattern "^APP_KEY=" | ForEach-Object { $_ -replace "^APP_KEY=", "" }
if (-not $appKey) { Write-Warn "APP_KEY bos — ileride olusturulacak" }
else { Write-OK "APP_KEY mevcut" }

# =============================================================================
# 3. DOCKER CONTAINER'LARI BASLAT
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 3. DOCKER CONTAINER'LAR (oracle + redis)" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

Write-Host "   ?? Container'lar baslatiliyor..." -ForegroundColor $YELLOW
docker compose down 2>&1 | Out-Null
docker compose up -d 2>&1
if ($LASTEXITCODE -ne 0) { Write-Fail "Container baslatilamadi"; exit 1 }
Write-OK "Container'lar baslatildi"
Write-Host "      ???  Oracle : port 1521" -ForegroundColor $CYAN
Write-Host "      ? Redis   : port 6379" -ForegroundColor $CYAN

# =============================================================================
# 4. ORACLE HAZIR OLENE KADAR BEKLE
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 4. ORACLE BEKLENIYOR (max 5 dk)" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

Write-Host "   ? Oracle hazir olana kadar bekleniyor..." -ForegroundColor $YELLOW
$oracleReady = $false
for ($i = 1; $i -le 60; $i++) {
    $ready = echo "SELECT 1 FROM DUAL;" | docker exec -i aykome-v6-oracle sqlplus -s aykome_user/aykome123@FREEPDB1 2>$null
    if ($ready -match "1") {
        Write-Host "`n"
        Write-OK ("Oracle hazir! ({0} sn)" -f ($i * 5))
        $oracleReady = $true
        break
    }
    Write-Host "." -NoNewline
    if ($i % 12 -eq 0) { Write-Host (" {0} sn" -f ($i * 5)) -ForegroundColor $YELLOW }
    Start-Sleep -Seconds 5
}
if (-not $oracleReady) {
    Write-Host "`n"
    Write-Warn "Oracle timeout! Kontrol: docker logs aykome-v6-oracle"
}

# =============================================================================
# 5. COMPOSER INSTALL
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 5. COMPOSER INSTALL" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

if (-not (Test-Path "vendor")) {
    Write-Host "   ?? Composer paketleri yukleniyor..." -ForegroundColor $YELLOW
    composer install --no-interaction 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Fail "Composer hatasi!"; exit 1 }
    Write-OK "Composer paketleri yuklendi"
} else {
    Write-OK "vendor mevcut"
}

# =============================================================================
# 6. APP_KEY OLUSTUR
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 6. APP_KEY" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

$appKey = Select-String -Path ".env" -Pattern "^APP_KEY=" | ForEach-Object { $_ -replace "^APP_KEY=", "" }
if (-not $appKey) {
    Write-Host "   ?? APP_KEY olusturuluyor..." -ForegroundColor $YELLOW
    php artisan key:generate 2>&1
    if ($LASTEXITCODE -eq 0) { Write-OK "APP_KEY olusturuldu" }
    else { Write-Warn "APP_KEY olusturulamadi" }
} else {
    Write-OK "APP_KEY mevcut"
}

# =============================================================================
# 7. MIGRATION
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 7. MIGRATION" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

Write-Host "   ?? Migration calistiriliyor..." -ForegroundColor $YELLOW
php artisan migrate --force 2>&1
if ($LASTEXITCODE -ne 0) { Write-Warn "Migration hatasi! Log kontrol edin." }
else { Write-OK "Migration tamam" }

# =============================================================================
# 8. SEED
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 8. SEED" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

Write-Host "   ?? Seed kontrol ediliyor..." -ForegroundColor $YELLOW
$roleCount = 0
try {
    $roleOutput = php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>&1
    if ($roleOutput -match "\d+") {
        $roleCount = [int]($Matches[0])
    }
} catch { $roleCount = 0 }

if ($roleCount -lt 6) {
    Write-Host "   ?? Roller bos ($roleCount adet), seed baslatiliyor..." -ForegroundColor $YELLOW
    php artisan db:seed --class=DatabaseSeeder 2>&1
    if ($LASTEXITCODE -ne 0) { Write-Warn "Seed hatasi!" }
    else { Write-OK "Seed tamam" }
} else {
    Write-OK ("Roller mevcut ($roleCount adet), seed atlandi")
}

# =============================================================================
# 9. VITE BUILD
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 9. VITE BUILD" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

if (-not (Test-Path "node_modules")) {
    Write-Host "   ?? npm install yukleniyor..." -ForegroundColor $YELLOW
    npm install 2>&1
}
Write-Host "   ?? Vite build aliniyor..." -ForegroundColor $YELLOW
npm run build 2>&1
if ($LASTEXITCODE -ne 0) { Write-Warn "Vite build hatasi!" }
else { Write-OK "Vite build tamam" }

# =============================================================================
# 10. STORAGE LINK
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 10. STORAGE LINK" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

php artisan storage:link 2>&1 | Out-Null
Write-OK "storage:link tamam"

# =============================================================================
# 11. CACHE TEMIZLEME
# =============================================================================
Write-Host "`n¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN
Write-Host " 11. CACHE TEMIZLEME" -ForegroundColor $CYAN
Write-Host "¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦¦" -ForegroundColor $CYAN

php artisan config:clear 2>&1 | Out-Null
php artisan route:clear 2>&1 | Out-Null
php artisan view:clear 2>&1 | Out-Null
Write-OK "Cache temizlendi"

# =============================================================================
# SONUC
# =============================================================================
Write-Host ""
Write-Host "-==============================================================¬" -ForegroundColor $GREEN
Write-Host "¦              ?   A Y K O M E   H A Z I R                   ¦" -ForegroundColor $GREEN
Write-Host "L==============================================================-" -ForegroundColor $GREEN
Write-Host ""
Write-Host "  ??  AYKOME           › http://localhost:8001" -ForegroundColor $CYAN
Write-Host "  ???  Oracle (Docker)  › localhost:1521  (aykome_user/aykome123@FREEPDB1)" -ForegroundColor $CYAN
Write-Host "  ?  Redis (Docker)   › localhost:6379" -ForegroundColor $CYAN
Write-Host "  ??  Adminer          › http://localhost:8080 (manuel baslat: docker compose up -d adminer)" -ForegroundColor $CYAN
Write-Host ""
Write-Host "  ??  Baslatmak icin: .\start.ps1" -ForegroundColor $YELLOW
Write-Host "  ??  Durdurmak icin:  .\stop.ps1" -ForegroundColor $YELLOW
Write-Host ""
