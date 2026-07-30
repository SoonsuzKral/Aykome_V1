const fs = require('fs');
const os = require('os');

const COMMON_P11_PATHS = {
  win32: [
    'C:\\Windows\\System32\\akisp11.dll',
    'C:\\Windows\\SysWOW64\\akisp11.dll',
    'C:\\Windows\\System32\\egsp11.dll',
    'C:\\Windows\\SysWOW64\\egsp11.dll',
    'C:\\Windows\\System32\\turksp11.dll',
    'C:\\Windows\\SysWOW64\\turksp11.dll',
    'C:\\Windows\\System32\\liteP11.dll',
    'C:\\Windows\\SysWOW64\\liteP11.dll',
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
          if (p.includes('akis')) label = 'Kamu SM';
          else if (p.includes('egs')) label = 'E-Güven';
          else if (p.includes('turk')) label = 'TÜRKTRUST';

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
