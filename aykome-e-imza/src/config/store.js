const Store = require('electron-store');

let instance = null;

function getStore() {
  if (!instance) {
    instance = new Store({
      defaults: {
        pkcs11_path: '',
        cert_serial: '',
        server_url: 'https://aykome.eyyubiye.bel.tr',
        api_key: 'eimza_aykome_dev_2026',
        setup_complete: false,
      },
    });
  }
  return instance;
}

module.exports = { getStore };
