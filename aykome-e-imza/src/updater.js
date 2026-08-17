/**
 * COZUM_09 §3 - OTOMATIK GUNCELLEME (electron-updater)
 *
 * Feed (latest.yml + .exe + .blockmap) AYKOME panelinin kendi sunucusunda
 * barinir: <server_url>/storage/downloads/eimza/
 *
 * Neden runtime'da setFeedURL?
 *   package.json > build.publish icindeki URL derleme aninda gomulur. Bu urun
 *   on-prem kurulur (her belediye kendi sunucusu). Bu yuzden feed adresi
 *   kurulum sihirbazinda kaydedilen store.server_url'den TURETILIR; tek bir
 *   .exe her kurumda kendi panelinden guncelleme alir.
 *
 * Neden statik /storage yolu (PHP controller degil)?
 *   electron-updater differential download icin HTTP Range istegi gonderir.
 *   Statik dosya sunumu (nginx/apache + storage symlink) Range'i destekler;
 *   PHP stream response desteklemez ve tam indirmeye duser.
 */

const { app, dialog } = require('electron');

let autoUpdater = null;
let store = null;
let getTray = () => null;
let status = { state: 'idle', text: 'Guncelleme denetlenmedi', version: null };
let onStatus = () => {};
let timer = null;
let manual = false;
let notifiedVersion = null;

const CHECK_INTERVAL_MS = 6 * 60 * 60 * 1000;  // 6 saat
const FIRST_CHECK_DELAY_MS = 20 * 1000;        // acilista local server/tray oturana kadar bekle

function log(...args) {
  console.log('[E-Imza][updater]', ...args);
}

function setStatus(state, text, version = null) {
  status = { state, text, version };
  try {
    onStatus(status);
  } catch { /* tray menusu yenilenemezse akis bozulmasin */ }
}

function getStatus() {
  return status;
}

/**
 * store.server_url = panel origin (orn. https://aykome.eyyubiye.bel.tr).
 * Windows IPv6 sorunu icin localhost -> 127.0.0.1 (projenin diger
 * yerlerindeki davranisla ayni).
 */
function feedUrl() {
  const raw = String(store?.get('server_url') || '').trim();
  if (!raw) return null;
  const base = raw.replace('//localhost', '//127.0.0.1').replace(/\/+$/, '');
  if (!/^https?:\/\//i.test(base)) return null;
  return `${base}/storage/downloads/eimza/`;
}

function balloon(title, content) {
  try {
    const tray = getTray();
    if (tray && process.platform === 'win32') {
      tray.displayBalloon({ title, content });
    }
  } catch { /* balloon kritik degil */ }
}

function bindEvents() {
  autoUpdater.on('checking-for-update', () => {
    setStatus('checking', 'Guncelleme denetleniyor...');
    log('checking for update');
  });

  autoUpdater.on('update-available', (info) => {
    setStatus('downloading', `Yeni surum indiriliyor: ${info?.version}`, info?.version || null);
    log('update available:', info?.version);
    if (notifiedVersion !== info?.version) {
      notifiedVersion = info?.version || null;
      balloon('E-Imza guncellemesi', `Surum ${info?.version} indiriliyor.`);
    }
  });

  autoUpdater.on('update-not-available', (info) => {
    setStatus('idle', `Guncel surum kullaniliyor (${app.getVersion()})`, info?.version || null);
    log('no update; current', app.getVersion());
    if (manual) {
      dialog.showMessageBox({
        type: 'info',
        title: 'E-Imza Guncelleme',
        message: 'Uygulama guncel.',
        detail: `Yuklu surum: ${app.getVersion()}`,
        buttons: ['Tamam'],
      });
    }
  });

  autoUpdater.on('download-progress', (p) => {
    const pct = Math.round(p?.percent || 0);
    setStatus('downloading', `Indiriliyor: %${pct}`, status.version);
  });

  autoUpdater.on('update-downloaded', (info) => {
    setStatus('downloaded', `Surum ${info?.version} kuruluma hazir`, info?.version || null);
    log('update downloaded:', info?.version);
    balloon('E-Imza guncellemesi hazir', 'Yeniden baslatildiginda kurulacak.');

    dialog.showMessageBox({
      type: 'question',
      title: 'E-Imza Guncelleme',
      message: `Yeni surum hazir: ${info?.version}`,
      detail: 'Kurulum icin uygulamanin yeniden baslatilmasi gerekiyor. Simdi yeniden baslatilsin mi? '
        + '(Daha sonra derseniz guncelleme, uygulama kapatildiginda otomatik kurulur.)',
      buttons: ['Simdi yeniden baslat', 'Daha sonra'],
      defaultId: 0,
      cancelId: 1,
    }).then(({ response }) => {
      if (response === 0) {
        app.isQuitting = true;
        // isSilent=false: NSIS arayuzu gorunur, kullanici ne oldugunu anlar.
        autoUpdater.quitAndInstall(false, true);
      }
    }).catch(() => { /* dialog kapatildi */ });
  });

  autoUpdater.on('error', (err) => {
    const msg = err?.message || String(err);
    setStatus('error', `Guncelleme hatasi: ${msg}`);
    log('error:', msg);
    if (manual) {
      dialog.showMessageBox({
        type: 'error',
        title: 'E-Imza Guncelleme',
        message: 'Guncelleme denetlenemedi.',
        detail: msg,
        buttons: ['Tamam'],
      });
    }
  });
}

/**
 * @param {boolean} isManual Tray menusunden tetiklendiyse true - sonucu
 *                           dialog ile bildir (sessiz denetimde bildirilmez).
 */
async function checkNow(isManual = false) {
  manual = isManual;

  if (!autoUpdater) {
    log('autoUpdater yuklenemedi, denetim atlandi');
    return;
  }

  // Paketlenmemis (npm start / electron .) ortamda electron-updater zaten
  // hata firlatir; gereksiz gurultu yapmadan atla.
  if (!app.isPackaged) {
    setStatus('skipped', 'Gelistirme modunda guncelleme denetimi kapali');
    if (isManual) {
      dialog.showMessageBox({
        type: 'info',
        title: 'E-Imza Guncelleme',
        message: 'Gelistirme modunda guncelleme denetlenmez.',
        detail: 'Bu denetim yalnizca kurulu (paketlenmis) surumde calisir.',
        buttons: ['Tamam'],
      });
    }
    return;
  }

  const url = feedUrl();
  if (!url) {
    setStatus('error', 'Sunucu adresi tanimli degil (Kurulum Sihirbazi)');
    if (isManual) {
      dialog.showMessageBox({
        type: 'warning',
        title: 'E-Imza Guncelleme',
        message: 'Sunucu adresi tanimli degil.',
        detail: 'Kurulum Sihirbazi ekranindan AYKOME panel adresini kaydedin.',
        buttons: ['Tamam'],
      });
    }
    return;
  }

  try {
    autoUpdater.setFeedURL({
      provider: 'generic',
      url,
      channel: 'latest',
      useMultipleRangeRequest: false,
    });
    log('feed:', url);
    await autoUpdater.checkForUpdates();
  } catch (err) {
    // 'error' event'i de tetiklenir; burada sadece promise reddini yutuyoruz.
    log('checkForUpdates basarisiz:', err?.message || err);
  } finally {
    manual = false;
  }
}

/**
 * app.on('ready') icinde createTray()'den SONRA cagrilir.
 * @param {object} opts
 * @param {import('electron-store')} opts.store
 * @param {() => Electron.Tray|null} opts.getTray
 * @param {(status: {state: string, text: string, version: string|null}) => void} [opts.onStatus]
 */
function init({ store: s, getTray: g, onStatus: cb }) {
  store = s;
  if (typeof g === 'function') getTray = g;
  if (typeof cb === 'function') onStatus = cb;

  try {
    ({ autoUpdater } = require('electron-updater'));
  } catch (err) {
    log('electron-updater yuklenemedi:', err?.message || err);
    setStatus('error', 'electron-updater yuklenemedi');
    return;
  }

  autoUpdater.logger = { info: log, warn: log, error: log, debug: () => {} };
  autoUpdater.autoDownload = true;
  autoUpdater.autoInstallOnAppQuit = true;   // "Daha sonra" -> cikista kurulur
  autoUpdater.allowDowngrade = false;

  bindEvents();

  if (timer) clearInterval(timer);
  setTimeout(() => checkNow(false), FIRST_CHECK_DELAY_MS);
  timer = setInterval(() => checkNow(false), CHECK_INTERVAL_MS);
}

function stop() {
  if (timer) clearInterval(timer);
  timer = null;
}

/**
 * Tray menusunden "Yeniden baslat ve kur". Yalnizca update-downloaded
 * durumunda cagrilmali (aksi halde electron-updater hata verir).
 */
function installNow() {
  if (!autoUpdater || status.state !== 'downloaded') return;
  try {
    autoUpdater.quitAndInstall(false, true);
  } catch (err) {
    log('quitAndInstall basarisiz:', err?.message || err);
  }
}

module.exports = { init, checkNow, getStatus, installNow, stop };
