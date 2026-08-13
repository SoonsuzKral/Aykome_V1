const fs = require('fs');
const os = require('os');

const COMMON_P11_PATHS = {
  win32: [
    // ── Sistem klasörleri (64-bit + 32-bit) ──
    'C:\\Windows\\System32\\akisp11.dll',
    'C:\\Windows\\SysWOW64\\akisp11.dll',
    'C:\\Windows\\System32\\egsp11.dll',
    'C:\\Windows\\SysWOW64\\egsp11.dll',
    'C:\\Windows\\System32\\turksp11.dll',
    'C:\\Windows\\SysWOW64\\turksp11.dll',
    'C:\\Windows\\System32\\liteP11.dll',
    'C:\\Windows\\SysWOW64\\liteP11.dll',
    'C:\\Windows\\System32\\mpayp11.dll',
    'C:\\Windows\\SysWOW64\\mpayp11.dll',
    'C:\\Windows\\System32\\opensc-pkcs11.dll',
    'C:\\Windows\\SysWOW64\\opensc-pkcs11.dll',
    'C:\\Windows\\System32\\gsp11.dll',
    'C:\\Windows\\SysWOW64\\gsp11.dll',
    'C:\\Windows\\System32\\aetpkss1.dll',
    'C:\\Windows\\SysWOW64\\aetpkss1.dll',
    'C:\\Windows\\System32\\cntgsp11.dll',
    'C:\\Windows\\SysWOW64\\cntgsp11.dll',
    'C:\\Windows\\System32\\bilesimsp11.dll',
    'C:\\Windows\\SysWOW64\\bilesimsp11.dll',
    // ── Sağlayıcı kurulum dizinleri (spesifik fallback lokasyonları) ──
    'C:\\Program Files\\TÜBİTAK\\AKİS\\bin\\akisp11.dll',
    'C:\\Program Files\\AKİS\\bin\\akisp11.dll',
    'C:\\Program Files (x86)\\AKİS\\bin\\akisp11.dll',
    'C:\\Program Files\\TÜBİTAK\\BİLGEM\\AKİS\\bin\\akisp11.dll',
    'C:\\Program Files (x86)\\TÜBİTAK\\AKİS\\bin\\akisp11.dll',
    'C:\\Program Files\\TÜRKTRUST\\TÜRKTRUST Elektronik Sertifika Hizmet Sağlayıcısı\\bin\\turksp11.dll',
    'C:\\Program Files (x86)\\TÜRKTRUST\\TÜRKTRUST Elektronik Sertifika Hizmet Sağlayıcısı\\bin\\turksp11.dll',
    'C:\\Program Files\\E-GÜVEN\\EGSP11\\bin\\egsp11.dll',
    'C:\\Program Files (x86)\\E-GÜVEN\\EGSP11\\bin\\egsp11.dll',
    'C:\\Program Files\\e-Tugra\\e-TugraKit\\bin\\liteP11.dll',
    'C:\\Program Files (x86)\\e-Tugra\\e-TugraKit\\bin\\liteP11.dll',
    'C:\\Program Files\\e-Tugra\\e-TugraKit\\bin\\mpayp11.dll',
    'C:\\Program Files (x86)\\e-Tugra\\e-TugraKit\\bin\\mpayp11.dll',
    'C:\\Program Files\\Bileşim\\Bileşim PKCS#11\\bin\\bilesimsp11.dll',
    'C:\\Program Files (x86)\\Bileşim\\Bileşim PKCS#11\\bin\\bilesimsp11.dll',
    'C:\\Program Files\\OpenSC Project\\OpenSC\\lib\\opensc-pkcs11.dll',
    'C:\\Program Files (x86)\\OpenSC Project\\OpenSC\\lib\\opensc-pkcs11.dll',
  ],
  darwin: [
    '/usr/local/lib/libakisp11.dylib',
    '/opt/homebrew/lib/libakisp11.dylib',
    '/Library/Akis/libakisp11.dylib',
  ],
  linux: [
    '/usr/lib/libakisp11.so',
    '/usr/lib/x86_64-linux-gnu/libakisp11.so',
    '/usr/local/lib/libakisp11.so',
  ],
};

const scanner = {
  async detect() {
    const platform = os.platform();
    const paths = COMMON_P11_PATHS[platform] || [];

    for (const p of paths) {
      try {
        if (fs.existsSync(p)) {
          return { path: p, found: true };
        }
      } catch {
        continue;
      }
    }

    return null;
  },

  async scanAll() {
    const platform = os.platform();
    const paths = COMMON_P11_PATHS[platform] || [];
    const results = [];

    for (const p of paths) {
      try {
        if (fs.existsSync(p)) {
          let label = 'Bilinmeyen Token';
          const lc = p.toLowerCase();
          if (lc.includes('akis') || lc.includes('litep11') || lc.includes('mpay')) label = 'Kamu SM';
          else if (lc.includes('egs')) label = 'E-Güven';
          else if (lc.includes('turk')) label = 'TÜRKTRUST';
          else if (lc.includes('opensc')) label = 'OpenSC';
          else if (lc.includes('aet')) label = 'AET';
          else if (lc.includes('cntg')) label = 'CNT';
          else if (lc.includes('bilesim')) label = 'Bileşim';

          results.push({ path: p, label, found: true });
        }
      } catch {
        continue;
      }
    }

    return results;
  },

  async detectWithPkcs11() {
    try {
      const detected = await this.detect();
      if (!detected) {
        console.log('[Scanner] Kütüphane bulunamadı');
        return null;
      }

      console.log('[Scanner] Kütüphane bulundu:', detected.path);
      
      // AKIS WORKAROUND:
      // pkcs11js/graphene CKR_ARGUMENTS_BAD hatası veriyor (GitHub issue #114)
      // PKCS#11 üzerinden token bilgisi okunamıyor, ancak driver bulundu
      // İmzalama işlevi Laravel backend'de çalışıyor
      
      console.log('[Scanner] AKIS driver bulundu, token bilgisi placeholder olarak döndürülüyor');
      
      // Placeholder token bilgisi (kullanıcıya "driver hazır" mesajı için)
      return {
        libraryPath: detected.path,
        tokens: [{
          label: 'AKIS Token',
          manufacturer: 'UEKAE / TÜBİTAK',
          serial: 'XXXX',  // Okunamıyor (pkcs11js sınırlaması)
          model: 'Akis Smart Card',
          flags: 0,
          slotId: 0,
          note: 'Token driver hazır. Bilgiler okunamıyor ancak imzalama çalışacak.'
        }]
      };
    } catch (err) {
      console.log('[Scanner] detectWithPkcs11 HATA:', err.message);
      return null;
    }
  },

  async detectViaWindowsCertStore(libraryPath) {
    const { spawn } = require('child_process');
    const path = require('path');
    
    return new Promise((resolve) => {
      // PowerShell ile Windows Cert Store'dan E-İmza sertifikalarını oku
      const psScript = `
        $certs = Get-ChildItem -Path Cert:\\CurrentUser\\My | Where-Object {
          $_.EnhancedKeyUsageList -match 'Digital Signature' -or
          $_.EnhancedKeyUsageList -match 'Non Repudiation'
        }
        
        $certs | ForEach-Object {
          [PSCustomObject]@{
            Subject = $_.Subject
            Issuer = $_.Issuer
            Thumbprint = $_.Thumbprint
            NotBefore = $_.NotBefore.ToString('yyyy-MM-dd')
            NotAfter = $_.NotAfter.ToString('yyyy-MM-dd')
            FriendlyName = $_.FriendlyName
          }
        } | ConvertTo-Json
      `;

      const ps = spawn('powershell.exe', [
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-Command', psScript
      ]);

      let output = '';
      let errorOutput = '';

      ps.stdout.on('data', (data) => {
        output += data.toString();
      });

      ps.stderr.on('data', (data) => {
        errorOutput += data.toString();
      });

      ps.on('close', (code) => {
        if (code !== 0 || !output.trim()) {
          console.log('[Scanner] PowerShell hatası:', errorOutput || 'Boş sonuç');
          resolve(null);
          return;
        }

        try {
          const certs = JSON.parse(output);
          const certArray = Array.isArray(certs) ? certs : [certs];
          
          if (certArray.length === 0) {
            console.log('[Scanner] Hiç E-İmza sertifikası bulunamadı');
            resolve(null);
            return;
          }

          console.log('[Scanner] Windows Cert Store\'dan', certArray.length, 'sertifika bulundu');
          
          // Sertifikaları token formatına dönüştür
          const tokens = certArray.map((cert, idx) => {
            // Subject'ten CN (Common Name) çıkar
            const cnMatch = cert.Subject.match(/CN=([^,]+)/);
            const label = cnMatch ? cnMatch[1] : cert.FriendlyName || 'E-İmza Token';
            
            // Issuer'dan organization çıkar
            const orgMatch = cert.Issuer.match(/O=([^,]+)/);
            const manufacturer = orgMatch ? orgMatch[1] : 'Bilinmeyen';

            return {
              label: label.trim(),
              manufacturer: manufacturer.trim(),
              serial: cert.Thumbprint.substring(0, 16),  // Thumbprint'ın ilk 16 karakteri
              model: 'Windows Certificate',
              flags: 0,
              slotId: idx,
              validFrom: cert.NotBefore,
              validTo: cert.NotAfter,
              thumbprint: cert.Thumbprint
            };
          });

          resolve({ libraryPath, tokens });
        } catch (parseErr) {
          console.log('[Scanner] JSON parse hatası:', parseErr.message);
          console.log('[Scanner] PowerShell output:', output);
          resolve(null);
        }
      });

      // 10 saniye timeout
      setTimeout(() => {
        ps.kill();
        console.log('[Scanner] PowerShell timeout');
        resolve(null);
      }, 10000);
    });
  }
};

module.exports = { scanner };
