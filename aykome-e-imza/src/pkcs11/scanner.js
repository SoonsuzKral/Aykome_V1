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
      const pkcs11 = require('pkcs11js');
      const detected = await this.detect();
      if (!detected) return null;

      const mod = pkcs11.require(detected.path);
      mod.initialize();

      const slots = mod.getSlots(true);
      const tokens = [];

      for (const slot of slots) {
        try {
          const tokenInfo = slot.getTokenInfo();
          tokens.push({
            label: tokenInfo.label.trim(),
            manufacturer: tokenInfo.manufacturerID.trim(),
            serial: tokenInfo.serialNumber.trim(),
            model: tokenInfo.model.trim(),
            flags: tokenInfo.flags,
            slotId: slot.slotId,
          });
        } catch {
          continue;
        }
      }

      mod.finalize();
      return { libraryPath: detected.path, tokens };
    } catch {
      return null;
    }
  }
};

module.exports = { scanner };
