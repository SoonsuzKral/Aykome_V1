const fs = require('fs');
const { execFileSync } = require('child_process');
const { buildPades } = require('C:/Aykome_V1/aykome-e-imza/src/pades/sign-pdf.js');

const OPENSSL = 'C:/Program Files/Git/usr/bin/openssl.exe';
const certDer = fs.readFileSync('ec_cert.der');

function signCallback(digestToSign) {
  fs.writeFileSync('digest_tmp.bin', digestToSign);
  const derHex = execFileSync(OPENSSL, [
    'pkeyutl', '-sign',
    '-inkey', 'ec_key.pem',
    '-pkeyopt', 'digest:sha384',
    '-in', 'digest_tmp.bin',
  ]);
  return derHex;
}

const pdf = fs.readFileSync('storage/app/test_ruhsat_5070.pdf');
const signed = buildPades(pdf, certDer, 'AYKOME EC P-384 Test', signCallback, 'EC');
fs.writeFileSync('storage/app/e2e_signed_ec_test.pdf', signed);
console.log('EC imzali PDF yazildi:', signed.length, 'bayt');
