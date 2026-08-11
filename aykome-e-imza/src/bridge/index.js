const { spawnSync } = require('child_process');
const path = require('path');
const os = require('os');
const { app } = require('electron');

const platform = os.platform();
const isDev = !app.isPackaged;
const resourcesPath = isDev ? path.join(__dirname, '..', '..') : process.resourcesPath;
const BRIDGE = platform === 'win32'
  ? path.join(resourcesPath, 'x64-worker', 'Pkcs11Bridge.exe')
  : path.join(resourcesPath, 'x64-worker', 'pkcs11-bridge');

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
    } else if (line.includes('TOKEN ')) {
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
    } else if (line.startsWith('KEY_TYPE ')) {
      result.keyType = line.substring(9).trim();
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

class BridgeWorker {
  constructor(p11Path) {
    this.p11Path = p11Path || '/usr/local/lib/libakisp11.dylib';
  }

  _run(args) {
    const isWin = platform === 'win32';
    const isMac = platform === 'darwin';
    const cmd = isWin ? BRIDGE : 'arch';
    const cmdArgs = isMac
      ? ['-x86_64', BRIDGE, this.p11Path, ...args]
      : [this.p11Path, ...args];

    const result = spawnSync(cmd, cmdArgs, {
      encoding: 'utf8',
      maxBuffer: 50 * 1024 * 1024,
      timeout: 120000,
    });
    if (result.error) {
      if (result.error.code === 'ENOENT') {
        throw new Error('Bridge dosyası bulunamadı: ' + BRIDGE);
      }
      if (result.error.code === 'ETIMEDOUT') {
        throw new Error('Bridge zaman aşımı (120sn). Token kartınızı kontrol edin.');
      }
      throw new Error('Bridge error: ' + result.error.message);
    }
    this._lastStderr = (result.stderr || '').toString('utf8').trim();
    if (result.status !== 0) throw new Error('Bridge exit ' + result.status + ': ' + (result.stderr || 'unknown'));
    return result.stdout;
  }

  listTokens() {
    const out = this._run(['list']);
    return parseOutput(out);
  }

  getCertificate(pin, certSerial) {
    const pinHex = toHex(pin);
    const args = ['cert', pinHex];
    if (certSerial) {
      args.push(certSerial);
    }
    const out = this._run(args);
    const r = parseOutput(out);
    if (r.error) throw new Error(r.error);
    if (!r.certOk) throw new Error('Certificate could not be retrieved' + (this._lastStderr ? ' [' + this._lastStderr + ']' : ''));
    return { certDer: r.certDer, keyType: r.keyType || 'EC' };
  }

  signData(pin, data) {
    const pinHex = toHex(pin);
    const dataHex = data.toString('hex');
    const out = this._run(['sign', pinHex, dataHex]);
    const r = parseOutput(out);
    if (r.error) throw new Error(r.error);
    if (!r.signature) throw new Error('No signature returned' + (this._lastStderr ? ' [' + this._lastStderr + ']' : ''));
    return r.signature;
  }
}

module.exports = { BridgeWorker };
