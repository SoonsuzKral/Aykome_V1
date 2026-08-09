const { SignPdf, plainAddPlaceholder } = require('node-signpdf');
const forge = require('node-forge');

async function simulateSign(pdfBuffer, pin) {
  const { p12Buffer, passphrase } = generateSimulatedP12();
  const pdfWithPlaceholder = plainAddPlaceholder({
    pdfBuffer,
    reason: 'Aykome E-Imza',
    location: 'Sanliurfa',
    name: 'Ahmet YILMAZ',
  });
  const signer = new SignPdf();
  const signedPdf = signer.sign(pdfWithPlaceholder, p12Buffer, { passphrase });
  return signedPdf;
}

function generateSimulatedP12() {
  const keys = forge.pki.rsa.generateKeyPair(2048);
  const cert = forge.pki.createCertificate();
  cert.publicKey = keys.publicKey;
  cert.serialNumber = 'SIM' + Math.floor(Date.now() / 1000).toString(16);
  cert.validity.notBefore = new Date();
  cert.validity.notAfter = new Date();
  cert.validity.notAfter.setFullYear(cert.validity.notBefore.getFullYear() + 10);
  cert.setSubject([
    { name: 'commonName', value: 'Ahmet YILMAZ' },
    { shortName: 'CN', value: 'Ahmet YILMAZ' },
  ]);
  cert.setIssuer([
    { name: 'commonName', value: 'Ahmet YILMAZ' },
    { shortName: 'CN', value: 'Ahmet YILMAZ' },
  ]);
  cert.sign(keys.privateKey, forge.md.sha256.create());
  const passphrase = 'sim123';
  const p12Asn1 = forge.pkcs12.toPkcs12Asn1(keys.privateKey, [cert], passphrase);
  const p12Buffer = Buffer.from(forge.asn1.toDer(p12Asn1).getBytes(), 'binary');
  return { p12Buffer, passphrase };
}

module.exports = { simulateSign };
