const { app, BrowserWindow, Tray, Menu, nativeImage, ipcMain, dialog } = require('electron');
const path = require('path');
const Store = require('electron-store');
const os = require('os');

const store = new Store({
  defaults: {
    pkcs11_path: '',
    cert_serial: '',
    server_url: 'http://127.0.0.1:8001',
    api_key: 'eimza_aykome_dev_2026',
    setup_complete: false,
  }
});

const { handleSignUrl } = require('./src/protocol');
const localServer = require('./src/server');

let tray = null;
let pinWindow = null;
let currentTransaction = null;
let localServerPort = null;
let slotPollTimer = null;

/**
 * GOREV 1 (COZUM_08) - detectWithPkcs11() sadece "AKIS Token" placeholder
 * dondurur (pkcs11js CKR_ARGUMENTS_BAD sinirlamasi, GitHub issue #114).
 * Gercek isim/sertifika bilgisi PIN GEREKTIRMEDEN Windows Sertifika
 * Deposu'ndan (detectViaWindowsCertStore) okunabiliyor. Bu fonksiyon ikisini
 * birlestirir: PKCS11 kart varligini (fiziksel takili mi) teyit eder,
 * ardindan gercek ismi Cert Store'dan cekmeyi dener; bulunamazsa
 * placeholder'a (ve varsa cert_cn store degerine) duser.
 *
 * Cert Store sonucu CERT_STORE_CACHE_MS boyunca onbelleklenir; polling her
 * 3 saniyede calistigi icin her tur PowerShell spawn etmek gereksiz yuk
 * bindirir.
 */
let realCertCache = null;
let realCertCacheAt = 0;
const CERT_STORE_CACHE_MS = 8000;

/**
 * GOREV 1 KOK COZUM (16.08 - canli test sonrasi) - Windows Cert Store
 * (Cert:\CurrentUser\My) bos cikti (bu ortamda AKIS/E-Tugra sertifikasi
 * CAPI/CNG deposuna hic yansimiyor, sadece PKCS11 middleware kurulu).
 * GERCEK COZUM: CKO_CERTIFICATE nesneleri PKCS#11 standardinda PUBLIC veridir
 * (ozel anahtar degildir) - Pkcs11Bridge.exe/.c "cert" komutu artik login
 * basarisiz olsa da (PIN'siz onizleme) sertifika aramaya devam ediyor
 * (bkz. Pkcs11Bridge.cs GOREV 1 FIX yorumu). Bu fonksiyon PIN GONDERMEDEN
 * gercek sertifikayi okur ve Subject CN'i cikarir.
 */
function tryReadCertViaPkcs11NoPin(libraryPath) {
  try {
    const { BridgeWorker } = require('./src/bridge');
    const { parseCertificate } = require('./src/pkcs11/cert-utils');
    const bridge = new BridgeWorker(libraryPath);
    const { certDer } = bridge.getCertificate('', null);
    const info = parseCertificate(certDer);
    if (!info || !info.commonName || info.commonName === 'Bilinmeyen') return null;
    return {
      tokens: [{
        label: info.commonName,
        manufacturer: info.issuer?.O || 'Bilinmeyen',
        thumbprint: info.skiHex || info.serialHex || '',
      }],
    };
  } catch (err) {
    console.log('[TokenStatus] PIN-siz PKCS11 sertifika okuma basarisiz:', err.message);
    return null;
  }
}

/**
 * GOREV 1 KOK COZUM #2 (16.08 - kullanici bulgusu): kart FIZIKSEL OLARAK
 * cikarilinca panel hala "Token Algilandi / AKTIF" gosteriyordu. Kok neden:
 * scanner.detectWithPkcs11() SADECE akisp11.dll dosyasinin DISKTE var olup
 * olmadigina bakiyor (fs.existsSync) - surucu kurulduktan sonra bu dosya
 * kart takili olsun ya da olmasin HER ZAMAN durur, yani "placeholder token"
 * her zaman donuyordu. Gercek fiziksel varlik kontrolu icin PKCS11
 * C_GetSlotList(tokenPresent=TRUE) cagirmak gerekir (Pkcs11Bridge.exe'nin
 * "list" komutu bunu zaten kullaniyor). true/false/null (bilinmiyor - bridge
 * hata verdi, eski davranisa guven) doner.
 */
function checkRealSlotPresence(libraryPath) {
  try {
    const { BridgeWorker } = require('./src/bridge');
    const bridge = new BridgeWorker(libraryPath);
    const result = bridge.listTokens();
    return (result.slots || 0) > 0;
  } catch (err) {
    console.log('[TokenStatus] Slot kontrolu basarisiz:', err.message);
    return null;
  }
}

async function resolveTokenStatus() {
  const cn = store.get('cert_cn') || '';
  const serial = store.get('cert_serial') || '';
  const { scanner } = require('./src/pkcs11/scanner');

  try {
    const det = await scanner.detectWithPkcs11();
    if (det && det.tokens && det.tokens.length > 0) {
      const now = Date.now();
      if (!realCertCache || (now - realCertCacheAt) > CERT_STORE_CACHE_MS) {
        const slotPresent = checkRealSlotPresence(det.libraryPath);

        if (slotPresent === false) {
          // Kutuphane (dll) diskte var ama kart FIZIKSEL OLARAK TAKILI DEGIL.
          realCertCache = { slotPresent: false };
        } else {
          // Kart takili (ya da kontrol edilemedi - eski placeholder davranisi
          // korunur). Gercek ismi ONCE PIN'siz PKCS11 "cert" komutundan
          // (birincil yontem, dogrulandi), olmazsa Windows Cert Store'dan
          // (bazi ortamlarda CSP/minidriver kuruluysa) okumayi dene.
          const certResult = tryReadCertViaPkcs11NoPin(det.libraryPath)
            || await scanner.detectViaWindowsCertStore(det.libraryPath);
          realCertCache = { slotPresent: true, certResult };
        }
        realCertCacheAt = now;
      }

      if (realCertCache.slotPresent === false) {
        return { durum: 'yok', sertifikaCN: cn, certSerial: serial };
      }

      const certResult = realCertCache.certResult;
      if (certResult && certResult.tokens && certResult.tokens.length > 0) {
        const t = certResult.tokens[0];
        return {
          durum: 'aktif',
          tokenLabel: t.label,
          sertifikaCN: t.label,
          certSerial: t.thumbprint || serial,
          vendor: t.manufacturer,
        };
      }

      // Hicbir yoldan gercek isim okunamadi - placeholder + varsa
      // list-certs'ten kaydedilmis CN.
      return {
        durum: 'aktif',
        tokenLabel: det.tokens[0].label.trim(),
        sertifikaCN: cn,
        certSerial: serial,
      };
    }

    const lib = await scanner.detect();
    if (lib) {
      return { durum: 'kutuphane', tokenLabel: lib.path, sertifikaCN: cn, certSerial: serial };
    }
    return { durum: 'yok', sertifikaCN: cn, certSerial: serial };
  } catch (err) {
    return { durum: 'yok', sertifikaCN: cn, certSerial: serial, hata: err.message };
  }
}

/**
 * GOREV 4 - Token/slot durumunu renderer'a canli it (ipcRenderer.on ile dinlenir).
 * Acik pencere (pin/setup) yoksa tarama yapilmaz; her 3 saniyede bir taranir.
 */
function startSlotPolling() {
  if (slotPollTimer) return;
  slotPollTimer = setInterval(async () => {
    const wins = BrowserWindow.getAllWindows();
    if (wins.length === 0) return;
    try {
      const payload = await resolveTokenStatus();
      for (const win of wins) {
        win.webContents.send('slot-detect', payload);
      }
    } catch {}
  }, 3000);
}

function createPinWindow(transaction, serverUrl) {
  if (pinWindow) {
    pinWindow.focus();
    return;
  }

  currentTransaction = { ...transaction, serverUrl };

  pinWindow = new BrowserWindow({
    width: 420,
    height: 520,
    resizable: false,
    frame: true,
    alwaysOnTop: true,
    title: 'Eyyübiye AYKOME - E-İmza Köprüsü',
    icon: path.join(__dirname, 'assets', process.platform === 'win32' ? 'icon.ico' : 'icon.png'),
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
    }
  });

  pinWindow.loadFile(path.join(__dirname, 'renderer', 'pin.html'));

  pinWindow.on('closed', () => {
    pinWindow = null;
    currentTransaction = null;
  });
}

function createSetupWindow() {
  const setupWin = new BrowserWindow({
    width: 520,
    height: 620,
    resizable: false,
    title: 'Eyyübiye AYKOME - E-İmza Kurulumu',
    icon: path.join(__dirname, 'assets', process.platform === 'win32' ? 'icon.ico' : 'icon.png'),
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
    }
  });

  setupWin.loadFile(path.join(__dirname, 'renderer', 'setup.html'));
}

function createTray() {
  const icon = nativeImage.createFromPath(path.join(__dirname, 'assets', 'icon.png')).resize({ width: 16, height: 16 });
  tray = new Tray(icon);

  const contextMenu = Menu.buildFromTemplate([
    { label: 'Eyyübiye AYKOME - E-İmza Köprüsü', enabled: false },
    { type: 'separator' },
    { label: 'Kurulum Sihirbazı', click: () => createSetupWindow() },
    { label: 'Durum: Çalışıyor', enabled: false },
    { type: 'separator' },
    { label: 'Çıkış', click: () => app.quit() },
  ]);

  tray.setToolTip('Eyyübiye AYKOME - E-İmza Köprüsü');
  tray.setContextMenu(contextMenu);
  tray.on('double-click', () => createSetupWindow());
}

// Windows: dev modunda (npx electron .) protocol handler dogru kaydedilsin
if (process.defaultApp) {
  app.setAsDefaultProtocolClient('aykome', process.execPath, [path.resolve(__dirname)]);
} else {
  app.setAsDefaultProtocolClient('aykome');
}

// macOS: open-url event ile protocol URL yakalama
app.on('open-url', (event, url) => {
  event.preventDefault();
  handleSignUrl(url, store, createPinWindow);
});

// Windows: single instance lock — protocol URL tekrar instance acmasin
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
  app.quit();
} else {
  // Windows: ikinci instance URL ile acilirsa bu event yakalar
  app.on('second-instance', (_event, argv) => {
    const url = argv.find(a => a.startsWith('aykome://'));
    if (url) handleSignUrl(url, store, createPinWindow);
  });
}

function registerAutoStart() {
  if (os.platform() !== 'win32') return;
  try {
    const appPath = process.execPath;
    const startupFolder = path.join(os.homedir(), 'AppData', 'Roaming', 'Microsoft', 'Windows', 'Start Menu', 'Programs', 'Startup');
    const shortcutPath = path.join(startupFolder, 'Aykome E-İmza.lnk');
    if (!require('fs').existsSync(shortcutPath)) {
      const { spawnSync } = require('child_process');
      spawnSync('powershell', [
        '-Command',
        `$WS = New-Object -ComObject WScript.Shell; $SC = $WS.CreateShortcut('${shortcutPath.replace(/'/g, "''")}'); $SC.TargetPath = '${appPath.replace(/'/g, "''")}'; $SC.Save()`
      ], { timeout: 5000 });
    }
  } catch {}
}

app.on('ready', () => {
  createTray();
  registerAutoStart();

  // GÖREV 4: Token durumu polling (renderer'a push)
  startSlotPolling();

  // Local HTTP server — protocol handler'a alternatif
  localServer.start().then(port => {
    localServerPort = port;
    console.log(`[E-Imza] Local server port: ${port}`);
  }).catch(err => {
    console.error('[E-Imza] Local server baslatilamadi:', err.message);
  });

  localServer.onSignRequest((transaction) => {
    // Windows IPv6 fix: localhost -> 127.0.0.1
    const safeUrl = (transaction.serverUrl || '').replace('//localhost', '//127.0.0.1');
    if (safeUrl) {
      store.set('server_url', safeUrl);
    }
    createPinWindow(
      { transactionId: transaction.transactionId, token: transaction.token },
      safeUrl || store.get('server_url')
    );
  });

  if (!store.get('setup_complete')) {
    createSetupWindow();
  }

  // Windows: ilk acilista process.argv icinde protocol URL varsa yakala
  const protocolUrl = process.argv.find(a => a.startsWith('aykome://'));
  if (protocolUrl) {
    handleSignUrl(protocolUrl, store, createPinWindow);
  }
});

app.on('window-all-closed', (e) => {
  e.preventDefault();
});

app.on('before-quit', () => {
  app.isQuitting = true;
});

ipcMain.handle('sign-pdf', async (event, { pin }) => {
  if (!currentTransaction) {
    return { error: 'Aktif işlem bulunamadı' };
  }

  try {
    const { executeSign } = require('./src/pkcs11/signer');
    const { downloadPdf } = require('./src/network/pdf-fetcher');
    const { uploadSignedPdf } = require('./src/network/uploader');

    const pkcs11Path = store.get('pkcs11_path');
    const certSerial = store.get('cert_serial');

    if (!pkcs11Path && process.platform === 'win32') {
      const { scanner } = require('./src/pkcs11/scanner');
      const detected = await scanner.detect();
      if (detected) {
        store.set('pkcs11_path', detected.path);
      }
    }

    const pdfBuffer = await downloadPdf(currentTransaction, store);

    const { signedPdf, certInfo } = await executeSign(
      pdfBuffer,
      pkcs11Path,
      pin,
      certSerial
    );

    const result = await uploadSignedPdf(currentTransaction, signedPdf, certInfo, store);

    currentTransaction = null;

    if (pinWindow) {
      pinWindow.close();
    }

    return { success: true, ...result };
  } catch (err) {
    return { error: err.message };
  }
});

ipcMain.handle('cancel-sign', () => {
  currentTransaction = null;
  if (pinWindow) {
    pinWindow.close();
  }
  return { cancelled: true };
});

ipcMain.handle('get-settings', () => {
  return {
    pkcs11_path: store.get('pkcs11_path'),
    cert_serial: store.get('cert_serial'),
    cert_cn: store.get('cert_cn'),
    server_url: store.get('server_url'),
    api_key: store.get('api_key'),
    setup_complete: store.get('setup_complete'),
  };
});

ipcMain.handle('save-settings', (event, settings) => {
  if (settings.pkcs11_path !== undefined) store.set('pkcs11_path', settings.pkcs11_path);
  if (settings.cert_serial !== undefined) store.set('cert_serial', settings.cert_serial);
  if (settings.cert_cn !== undefined) store.set('cert_cn', settings.cert_cn);
  if (settings.server_url !== undefined) store.set('server_url', settings.server_url);
  if (settings.api_key !== undefined) store.set('api_key', settings.api_key);
  store.set('setup_complete', true);
  return { saved: true };
});

ipcMain.handle('token-durumu', async () => {
  // GOREV 1 (COZUM_08): artik placeholder yerine resolveTokenStatus() ile
  // Windows Cert Store'dan gercek isim (Subject CN) okunmaya calisilir.
  return resolveTokenStatus();
});

ipcMain.handle('close-setup', (event) => {
  const win = BrowserWindow.fromWebContents(event.sender);
  if (win) win.close();
  return { closed: true };
});

ipcMain.handle('scan-tokens', async () => {
  try {
    const { scanner } = require('./src/pkcs11/scanner');
    const result = await scanner.scanAll();
    return result;
  } catch (err) {
    return { error: err.message };
  }
});

ipcMain.handle('list-certs', async (event, { pkcs11Path, pin }) => {
  try {
    if (!pkcs11Path || pkcs11Path === 'SIMULATION') {
      return { certs: [{ serial: 'SIMULATED', label: '[Simülasyon] Ahmet YILMAZ (Kamu SM)' }] };
    }
    const config = { cert_serial: '', pkcs11_path: pkcs11Path, api_key: store.get('api_key') };
    Object.assign(config, { server_url: store.get('server_url') });

    const { BridgeWorker } = require('./src/bridge');
    const bridge = new BridgeWorker(pkcs11Path);
    const { certDer } = bridge.getCertificate(pin);

    const { parseCertificate } = require('./src/pkcs11/cert-utils');
    const info = parseCertificate(certDer);

    if (info.commonName) {
      store.set('cert_cn', info.commonName);
    }

    return {
      certs: [{
        serial: info.skiHex || info.serialHex || 'unknown',
        label: (info.commonName || 'Bilinmeyen') + ' (Kamu SM)',
        cn: info.commonName,
        tckn: info.tckn,
        issuer: info.issuer,
      }]
    };
  } catch (err) {
    return { error: err.message };
  }
});
