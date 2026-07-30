const { app, BrowserWindow, Tray, Menu, nativeImage, ipcMain } = require('electron');
const path = require('path');
const Store = require('electron-store');
const os = require('os');

const store = new Store({
  defaults: {
    pkcs11_path: '',
    cert_serial: '',
    server_url: 'https://aykome.eyyubiye.bel.tr',
    api_key: 'eimza_aykome_dev_2026',
    setup_complete: false,
  }
});

const { handleSignUrl } = require('./src/protocol');

let tray = null;
let pinWindow = null;
let currentTransaction = null;

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
    title: 'Aykome E-İmza',
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
    title: 'Aykome E-İmza - İlk Kurulum',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
    }
  });

  setupWin.loadFile(path.join(__dirname, 'renderer', 'setup.html'));
}

function createTray() {
  const iconPath = path.join(__dirname, 'assets', 'icon.png');
  const icon = nativeImage.createFromPath(iconPath);
  tray = new Tray(icon.resize({ width: 16, height: 16 }));

  const contextMenu = Menu.buildFromTemplate([
    { label: 'Aykome E-İmza', enabled: false },
    { type: 'separator' },
    { label: 'Kurulum Sihirbazı', click: () => createSetupWindow() },
    { label: 'Durum: Çalışıyor', enabled: false },
    { type: 'separator' },
    { label: 'Çıkış', click: () => app.quit() },
  ]);

  tray.setToolTip('Aykome E-İmza');
  tray.setContextMenu(contextMenu);
  tray.on('double-click', () => createSetupWindow());
}

app.setAsDefaultProtocolClient('aykome');

app.on('open-url', (event, url) => {
  event.preventDefault();
  handleSignUrl(url, store, createPinWindow);
});

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

  if (!store.get('setup_complete')) {
    createSetupWindow();
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
    server_url: store.get('server_url'),
    api_key: store.get('api_key'),
    setup_complete: store.get('setup_complete'),
  };
});

ipcMain.handle('save-settings', (event, settings) => {
  if (settings.pkcs11_path !== undefined) store.set('pkcs11_path', settings.pkcs11_path);
  if (settings.cert_serial !== undefined) store.set('cert_serial', settings.cert_serial);
  if (settings.server_url !== undefined) store.set('server_url', settings.server_url);
  if (settings.api_key !== undefined) store.set('api_key', settings.api_key);
  store.set('setup_complete', true);
  return { saved: true };
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

    return {
      certs: [{
        serial: info.serialHex || 'unknown',
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
