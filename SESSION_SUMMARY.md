# Oturum Özeti — 30 Temmuz 2026

## Önceki (27 Temmuz)
- Editable Modules (accordion düzenleme)
- Ping-Pong Signed Document Exchange (çift taraflı ıslak imza)
- Notification Leak Fix

## Bu Oturum — ECDSA Kamu SM Token ile E-İmza (PKCS#11)

### 🎯 Tamamlanan: E-İmza artık gerçek ECDSA token ile çalışıyor
**Token:** AKIS_043727D2A91F90E0 (TÜBİTAK UEKAE, secp384r1, non-extractable)  
**Sertifika:** CN=ZEYNELABİDİN AKTAŞOĞLU, TCKN=39758093026  
**İşletim Sistemi:** Apple Silicon (ARM64) — `libakisp11.dylib` x86_64, Rosetta 2 ile çalışıyor

### Ana Adımlar
1. **x86_64 Node.js Worker** — `node-x64/` binary'si indirildi (v20.18.0)
2. **pkcs11js patch** — `C_Initialize`'a NULL parametre fix'i + x86_64 rebuild
3. **C PKCS#11 Bridge** (`x64-worker/pkcs11-bridge.c`) — `list`, `cert`, `sign` komutları; raw ECDSA imzayı DER'e dönüştürme
4. **Forge Cert Bypass** — forge ECDSA certificateFromAsn1 hatası çözüldü: ASN.1 tbsCertificate + signature raw byte'ları manuel sağlanıyor, subject/issuer DN raw parse ediliyor
5. **Callback-based BuildPades** — `node-signpdf`'in `plainAddPlaceholder`'ı `/**********` placeholder kullanıyor, byte range fix yapıldı
6. **Bridge Worker JS** — `src/bridge/index.js` ile `arch -x86_64` spawn + C binary iletişimi
7. **signer.js** — simulation/real token ayrımı, bridge üzerinden sertifika çekme + ECDSA imzalama
8. **sign-pdf.js** — `buildPades(pdfBuffer, forgeCert, signCallback)` ile token delegate imza

### Kritik Çözümler
- **OID decode:** `forge.asn1.derToOid()` raw bytes bekliyor, TLV wrapper DEĞİL
- **Turkish charset:** WinAnsi font encoding "İ" hatası → transliteration (İ→I, ğ→g, vs.)
- **x86_64 vs ARM64:** main Node.js ARM64, bridge `arch -x86_64` ile çalışıyor

### Değişen Dosyalar (aykome-e-imza/)
| Dosya | İşlem |
|---|---|
| `x64-worker/pkcs11-bridge.c` | C PKCS#11 bridge (list/cert/sign) |
| `x64-worker/pkcs11-bridge` | Derlenmiş x86_64 binary |
| `x64-worker/bridge-worker.js` | Bridge JS wrapper |
| `src/bridge/index.js` | Main-process bridge (spawnSync + arch -x86_64) |
| `src/pkcs11/signer.js` | Sertifika çekme, forge cert build, ECDSA callback |
| `src/pkcs11/simulate.js` | Simulation (PKCS12) korundu |
| `src/pades/sign-pdf.js` | Callback-based buildPades + byte range fix + görsel mühür |
| `node-x64/` | x86_64 Node.js v20.18.0 |

### Sıradaki
- Electron + Laravel entegrasyonu (upload signed PDF)
- Windows build: `pkcs11js` kullan, `electron-builder` ile paketle
- SSL sertifikası sorununu çöz (kendinden imzalı sertifika Electron'da güvenli sayfa gösterimi engelliyor)
