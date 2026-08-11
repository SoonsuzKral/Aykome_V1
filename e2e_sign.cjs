const { spawnSync } = require('child_process');
const fs = require('fs');
const { buildPades } = require('C:/Aykome_V1/aykome-e-imza/src/pades/sign-pdf');

const PIN = '062954';
const P11 = 'C:/Windows/System32/akisp11.dll';
const BRIDGE = 'C:/Aykome_V1/aykome-e-imza/x64-worker/Pkcs11Bridge.exe';
const SRC = 'C:/Aykome_V1/storage/app';
const OUT = 'C:/Aykome_V1/storage/app';

const tipler = ['ruhsat', 'pre_permit', 'cover_letter', 'tahakkuk', 'metraj', 'makbuz', 'taahhutname'];

const pinHex = Buffer.from(PIN, 'utf8').toString('hex');

function run(args) {
  const r = spawnSync(BRIDGE, [P11, ...args], { encoding: 'utf8', maxBuffer: 50 * 1024 * 1024, timeout: 120000 });
  if (r.error) throw new Error('Bridge: ' + r.error.message);
  if (r.status !== 0) throw new Error('Bridge exit ' + r.status + ': ' + r.stderr);
  return r.stdout;
}

function parseOutput(stdout) {
  const res = {};
  for (const line of stdout.trim().split('\n')) {
    if (line.startsWith('CERT_DER ')) res.certDer = Buffer.from(line.substring(9), 'hex');
    else if (line.startsWith('CERT_OK')) res.certOk = true;
    else if (line.startsWith('KEY_TYPE ')) res.keyType = line.substring(9).trim();
    else if (line.startsWith('SIGNATURE ')) res.signature = Buffer.from(line.substring(10), 'hex');
    else if (line.startsWith('ERR ')) res.error = line.substring(4);
  }
  return res;
}

(async () => {
  const certOut = parseOutput(run(['cert', pinHex]));
  if (!certOut.certOk) throw new Error('Sertifika alinamadi: ' + (certOut.error || ''));
  const certDer = certOut.certDer;
  const keyType = certOut.keyType || 'EC';
  console.log('Sertifika OK | keyType =', keyType, '| cert', certDer.length, 'bayt');

  for (const tip of tipler) {
    const pdf = fs.readFileSync(`${SRC}/test_${tip}_5070.pdf`);
    const signedPdf = await buildPades(pdf, certDer, 'E2E-TEST', (hash) => {
      const o = parseOutput(run(['sign', pinHex, hash.toString('hex')]));
      if (o.error) throw new Error(o.error);
      return o.signature;
    }, keyType);
    fs.writeFileSync(`${OUT}/e2e_signed_${tip}.pdf`, signedPdf);
    console.log(`OK ${tip}: ${pdf.length} B -> ${signedPdf.length} B`);
  }
  console.log('TAMAM');
})().catch((e) => { console.error('FATAL:', e.message); process.exit(1); });
