# =============================================================================
# AYKOME Oracle Veri İçe Aktarma (Import) — Windows Sunucu
# Kullanım: .\import_oracle.ps1  [dump dosyası]
#   Varsayılan: aykome_backup.dmp
#
# Mac'te oluşturulan export'u Windows sunucudaki Oracle container'ına aktarır.
# =============================================================================

param(
    [Parameter(Mandatory=$false)]
    [string]$DumpFile = "aykome_backup.dmp"
)

$RED = "Red"; $GREEN = "Green"; $YELLOW = "Yellow"; $CYAN = "Cyan"

Write-Host "=======================================================" -ForegroundColor $CYAN
Write-Host "  AYKOME v6 — ORACLE VERI IMPORT" -ForegroundColor $CYAN
Write-Host "=======================================================" -ForegroundColor $CYAN
Write-Host ""

# 1. Dosya kontrol
if (-not (Test-Path $DumpFile)) {
    Write-Host "❌ Dump dosyasi bulunamadi: $DumpFile" -ForegroundColor $RED
    exit 1
}
$sizeMB = [math]::Round((Get-Item $DumpFile).Length / 1MB, 2)
Write-Host "✅ Dump: $DumpFile ($sizeMB MB)" -ForegroundColor $GREEN

# 2. Container kontrol
Write-Host "⏳ Oracle container kontrol..." -ForegroundColor $YELLOW
docker ps --format "{{.Names}}" | Select-String "aykome-v6-oracle" | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "   → Oracle baslatiliyor..." -ForegroundColor $YELLOW
    docker compose up -d oracle 2>&1 | Out-Null
    Start-Sleep -Seconds 5
}
Write-Host "✅ Oracle container hazir" -ForegroundColor $GREEN

# 3. Oracle'in hazir olmasini bekle
Write-Host "⏳ Oracle ayaga kalkiyor (5 dk timeout)..." -ForegroundColor $YELLOW
$oracleReady = $false
for ($i = 1; $i -le 60; $i++) {
    $ready = echo "SELECT 1 FROM DUAL;" | docker exec -i aykome-v6-oracle sqlplus -s aykome_user/aykome123@FREEPDB1 2>$null
    if ($ready -match "1") {
        Write-Host ""
        Write-Host ("   ✅ Oracle hazir ({0} sn)" -f ($i * 5)) -ForegroundColor $GREEN
        $oracleReady = $true
        break
    }
    Write-Host "." -NoNewline
    Start-Sleep -Seconds 5
}
if (-not $oracleReady) {
    Write-Host ""
    Write-Host "⚠️  Oracle timeout! Kontrol: docker logs aykome-v6-oracle" -ForegroundColor $YELLOW
    exit 1
}

# 4. Dump dizini hazirla + dosyayi kopyala
Write-Host "⏳ Dump dosyasi container'a kopyalaniyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-oracle bash -c "mkdir -p /tmp/dump && chown oracle:oinstall /tmp/dump" 2>&1 | Out-Null
docker cp $DumpFile aykome-v6-oracle:/tmp/dump/aykome_backup.dmp
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ❌ Kopyalama hatasi!" -ForegroundColor $RED
    exit 1
}
Write-Host "   ✅ Kopyalandi" -ForegroundColor $GREEN

# 5. Import
# TABLE_EXISTS_ACTION=REPLACE: sunucudaki mevcut (seed) tablolar silinir, dump'taki veri birebir gelir.
# Böylece deploy.ps1 ile kurulan boş/seed veri yerine LOCAL'deki güncel veriler yüklenir.
Write-Host "⏳ Import baslatiliyor..." -ForegroundColor $YELLOW
docker exec aykome-v6-oracle bash -c "impdp aykome_user/aykome123@FREEPDB1 directory=TMP_DUMP_DIR dumpfile=aykome_backup.dmp logfile=impdp.log schemas=AYKOME_USER TABLE_EXISTS_ACTION=REPLACE" 2>&1 | Select-Object -Last 15
if ($LASTEXITCODE -ne 0) {
    Write-Host "   ⚠️  Import cikisi icin log kontrol edin" -ForegroundColor $YELLOW
}
Write-Host "   → Tam log: docker exec aykome-v6-oracle cat /tmp/dump/impdp.log" -ForegroundColor $CYAN

# 6. Dogrulama
Write-Host "⏳ Dogrulama: tablo sayisi kontrol..." -ForegroundColor $YELLOW
$tables = docker exec aykome-v6-oracle bash -c 'echo "SELECT COUNT(*) FROM user_tables;" | sqlplus -s aykome_user/aykome123@FREEPDB1' 2>$null | Select-String "\d+"
Write-Host "   ✅ Toplam tablo: $($tables.Matches[0].Value)" -ForegroundColor $GREEN

Write-Host ""
Write-Host "=======================================================" -ForegroundColor $GREEN
Write-Host "  ✅ IMPORT TAMAM — Veriler sunucuya aktarildi" -ForegroundColor $GREEN
Write-Host "=======================================================" -ForegroundColor $GREEN
Write-Host "  Şimdi: .\deploy.ps1  (migration + seed + build + e-imza)" -ForegroundColor $CYAN
Write-Host ""
