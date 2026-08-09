const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('eImza', {
  signPdf: (data) => ipcRenderer.invoke('sign-pdf', data),
  cancelSign: () => ipcRenderer.invoke('cancel-sign'),
  getSettings: () => ipcRenderer.invoke('get-settings'),
  saveSettings: (settings) => ipcRenderer.invoke('save-settings', settings),
  scanTokens: () => ipcRenderer.invoke('scan-tokens'),
  listCerts: (data) => ipcRenderer.invoke('list-certs', data),
  tokenDurumu: () => ipcRenderer.invoke('token-durumu'),
  closeSetup: () => ipcRenderer.invoke('close-setup'),
});
