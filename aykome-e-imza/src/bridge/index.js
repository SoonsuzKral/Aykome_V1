const { spawnSync } = require('child_process');
const path = require('path');
const os = require('os');

const BRIDGE = path.join(__dirname, '..', '..', 'x64-worker', 'pkcs11-bridge');

function toHex(str) {
  return Buffer.from(str, 'utf8').toString('hex');
}

function fromHex(hex) {
  return Buffer.from(hex, 'hex');
}

function parseOutput(stdout) {
  const lines = stdout.trim().split('\n');
  const result = {};
  for (const line of lines) {
    if (line.startsWith('SLOTS ')) {
      result.slots = parseInt(line.split(' ')[1]);
    } else if (line.startsWith('TOKEN ')) {
      if (!result.tokens) result.tokens = [];
      const tok = {};
      const parts = line.match(/(\w+)="([^"]*)"/g);
      for (const p of parts || []) {
        const [k, v] = p.split('=');
        tok[k] = v.replace(/^"|"$/g, '');
      }
      result.tokens.push(tok);
    } else if (line.startsWith('CERT_DER ')) {
      result.certDer = fromHex(line.substring(9));
    } else if (line.startsWith('CERT_OK')) {
      result.certOk = true;
    } else if (line.startsWith('SIGNATURE ')) {
      result.signature = fromHex(line.substring(10));
    } else if (line.startsWith('ERR ')) {
      result.error = line.substring(4);
    }
  }
  return result;
}

function ecdsaRawToDer(rawSig) {
  if (rawSig.length === 0) return rawSig;
  const half = rawSig.length / 2;
  const r = rawSig.slice(0, half);
  const s = rawSig.slice(half);
  const rLead = (r[0] & 0x80) ? 1 : 0;
  const sLead = (s[0] & 0x80) ? 1 : 0;
  const rLen = half + rLead;
  const sLen = half + sLead;
  const derLen = 2 + 2 + rLen + 2 + sLen;
  const der = Buffer.alloc(derLen);
  let pos = 0;
  der[pos++] = 0x30;
  der[pos++] = derLen - 2;
  der[pos++] = 0x02;
  der[pos++] = rLen;
  if (rLead) der[pos++] = 0x00;
  der.set(r, pos); pos += half;
  der[pos++] = 0x02;
  der[pos++] = sLen;
  if (sLead) der[pos++] = 0x00;
  der.set(s, pos); pos += half;
  return der.slice(0, pos);
}

class WinPkcs11Bridge {
  constructor(p11Path) {
    this.p11Path = p11Path;
    this.pkcs11 = null;
    this.session = null;
  }

  _openSession(pin) {
    const pkcs11js = require('pkcs11js');
    this.pkcs11 = new pkcs11js.PKCS11();
    this.pkcs11.load(this.p11Path);
    this.pkcs11.C_Initialize();
    const slots = this.pkcs11.C_GetSlotList(true);
    if (!slots || slots.length === 0) throw new Error('No slot found');
    this.session = this.pkcs11.C_OpenSession(slots[0], pkcs11js.CKF_SERIAL_SESSION | pkcs11js.CKF_RW_SESSION);
    if (pin) {
      this.pkcs11.C_Login(this.session, pkcs11js.CKU_USER, pin);
    }
  }

  _close() {
    try { if (this.session) { this.pkcs11.C_Logout(this.session); this.pkcs11.C_CloseSession(this.session); } } catch {}
    try { this.pkcs11.C_Finalize(); this.pkcs11.close(); } catch {}
    this.session = null;
    this.pkcs11 = null;
  }

  listTokens() {
    const pkcs11js = require('pkcs11js');
    try {
      const mod = new pkcs11js.PKCS11();
      mod.load(this.p11Path);
      mod.C_Initialize();
      const slots = mod.C_GetSlotList(true);
      const result = { slots: slots.length, tokens: [] };
      for (const slot of slots) {
        try {
          const info = mod.C_GetTokenInfo(slot);
          result.tokens.push({
            label: (info.label || '').replace(/\0/g, '').trim(),
            manufacturer: (info.manufacturerID || '').replace(/\0/g, '').trim(),
            serial: (info.serialNumber || '').replace(/\0/g, '').trim(),
            model: (info.model || '').replace(/\0/g, '').trim(),
            flags: info.flags,
          });
        } catch {}
      }
      return result;
    } finally {
      try { mod.C_Finalize(); mod.close(); } catch {}
    }
  }

  getCertificate(pin) {
    try {
      this._openSession(pin);
      const pkcs11js = require('pkcs11js');
      this.pkcs11.C_FindObjectsInit(this.session, [{ type: pkcs11js.CKA_CLASS, value: pkcs11js.CKO_CERTIFICATE }]);
      const objs = this.pkcs11.C_FindObjects(this.session);
      this.pkcs11.C_FindObjectsFinal(this.session);
      if (!objs || objs.length === 0) throw new Error('No certificate found');
      const attrs = this.pkcs11.C_GetAttributeValue(this.session, objs[0], [{ type: pkcs11js.CKA_VALUE }]);
      return { certDer: Buffer.from(attrs[0].value) };
    } finally {
      this._close();
    }
  }

  signData(pin, data) {
    try {
      this._openSession(pin);
      const pkcs11js = require('pkcs11js');
      this.pkcs11.C_FindObjectsInit(this.session, [{ type: pkcs11js.CKA_CLASS, value: pkcs11js.CKO_PRIVATE_KEY }]);
      const keys = this.pkcs11.C_FindObjects(this.session);
      this.pkcs11.C_FindObjectsFinal(this.session);
      if (!keys || keys.length === 0) throw new Error('No private key found');

      this.pkcs11.C_SignInit(this.session, { mechanism: pkcs11js.CKM_ECDSA }, keys[0]);
      const sigBuf = Buffer.alloc(256);
      const sig = this.pkcs11.C_Sign(this.session, data, sigBuf);

      const derSig = ecdsaRawToDer(sig);
      return Buffer.from(derSig);
    } finally {
      this._close();
    }
  }
}

class BridgeWorker {
  constructor(p11Path) {
    this.p11Path = p11Path || '/usr/local/lib/libakisp11.dylib';
    this.platform = os.platform();
  }

  _getBridge() {
    if (this.platform === 'win32') {
      return new WinPkcs11Bridge(this.p11Path);
    }
    return null;
  }

  runBridge(args) {
    if (this.platform === 'darwin') {
      const result = spawnSync('arch', ['-x86_64', BRIDGE, this.p11Path, ...args], {
        encoding: 'utf8',
        maxBuffer: 50 * 1024 * 1024,
      });
      if (result.error) throw new Error('Bridge error: ' + result.error.message);
      if (result.status !== 0) throw new Error('Bridge exit ' + result.status + ': ' + (result.stderr || 'unknown'));
      return result.stdout;
    }
    throw new Error('Unsupported platform: ' + this.platform);
  }

  listTokens() {
    if (this.platform === 'win32') {
      return this._getBridge().listTokens();
    }
    const out = this.runBridge(['list']);
    return parseOutput(out);
  }

  getCertificate(pin) {
    if (this.platform === 'win32') {
      return this._getBridge().getCertificate(pin);
    }
    const pinHex = toHex(pin);
    const out = this.runBridge(['cert', pinHex]);
    const r = parseOutput(out);
    if (r.error) throw new Error(r.error);
    if (!r.certOk) throw new Error('Certificate could not be retrieved');
    return { certDer: r.certDer };
  }

  signData(pin, data) {
    if (this.platform === 'win32') {
      return this._getBridge().signData(pin, data);
    }
    const pinHex = toHex(pin);
    const dataHex = data.toString('hex');
    const out = this.runBridge(['sign', pinHex, dataHex]);
    const r = parseOutput(out);
    if (r.error) throw new Error(r.error);
    if (!r.signature) throw new Error('No signature returned');
    return r.signature;
  }
}

module.exports = { BridgeWorker };
