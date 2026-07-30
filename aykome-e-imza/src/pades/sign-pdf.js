const forge = require('node-forge');
const { plainAddPlaceholder, findByteRange, DEFAULT_BYTE_RANGE_PLACEHOLDER, removeTrailingNewLine } = require('node-signpdf');
const { PDFDocument, StandardFonts, rgb } = require('pdf-lib');

async function buildPades(pdfBuffer, forgeCert, signCallback) {
  let pdf = removeTrailingNewLine(pdfBuffer);
  pdf = plainAddPlaceholder({
    pdfBuffer: pdf,
    reason: 'Aykome E-Imza',
    location: 'Sanliurfa',
    name: 'Imzalayan',
  });

  const { byteRangePlaceholder } = findByteRange(pdf);
  if (!byteRangePlaceholder) {
    throw new Error('Could not find byte range placeholder');
  }

  const byteRangePos = pdf.indexOf(byteRangePlaceholder);
  const byteRangeEnd = byteRangePos + byteRangePlaceholder.length;
  const contentsTagPos = pdf.indexOf('/Contents ', byteRangeEnd);
  const placeholderPos = pdf.indexOf('<', contentsTagPos);
  const placeholderEnd = pdf.indexOf('>', placeholderPos);
  const placeholderLengthWithBrackets = placeholderEnd + 1 - placeholderPos;
  const placeholderLength = placeholderLengthWithBrackets - 2;

  const byteRange = [0, 0, 0, 0];
  byteRange[1] = placeholderPos;
  byteRange[2] = byteRange[1] + placeholderLengthWithBrackets;
  byteRange[3] = pdf.length - byteRange[2];

  let actualByteRange = `/ByteRange [${byteRange.join(' ')}]`;
  actualByteRange += ' '.repeat(byteRangePlaceholder.length - actualByteRange.length);

  pdf = Buffer.concat([
    pdf.slice(0, byteRangePos),
    Buffer.from(actualByteRange),
    pdf.slice(byteRangeEnd),
  ]);

  pdf = Buffer.concat([
    pdf.slice(0, byteRange[1]),
    pdf.slice(byteRange[2], byteRange[2] + byteRange[3]),
  ]);

  const p7 = forge.pkcs7.createSignedData();
  p7.content = forge.util.createBuffer(pdf.toString('binary'));
  p7.addCertificate(forgeCert);

  const fakeKey = {
    sign: function (md) {
      const hash = md.digest().getBytes();
      const sig = signCallback(Buffer.from(hash, 'binary'));
      return sig.toString('binary');
    },
  };

  p7.addSigner({
    key: fakeKey,
    certificate: forgeCert,
    digestAlgorithm: forge.pki.oids.sha384,
    authenticatedAttributes: [
      { type: forge.pki.oids.contentType, value: forge.pki.oids.data },
      { type: forge.pki.oids.signingTime, value: new Date() },
      { type: forge.pki.oids.messageDigest },
    ],
  });

  p7.sign({ detached: true });

  const raw = forge.asn1.toDer(p7.toAsn1()).getBytes();
  const expectedLen = Math.floor(placeholderLength / 2);

  if (raw.length > expectedLen) {
    throw new Error(`Signature too long: ${raw.length} > ${expectedLen}`);
  }

  let signatureHex = Buffer.from(raw, 'binary').toString('hex');
  signatureHex += '0'.repeat(placeholderLength - signatureHex.length);

  pdf = Buffer.concat([
    pdf.slice(0, byteRange[1]),
    Buffer.from(`<${signatureHex}>`),
    pdf.slice(byteRange[1]),
  ]);

  const doc = await PDFDocument.load(pdf);
  const pages = doc.getPages();
  const firstPage = pages[0];
  const helvetica = await doc.embedFont(StandardFonts.Helvetica);
  const helveticaBold = await doc.embedFont(StandardFonts.HelveticaBold);

  const cnName = (forgeCert.subject.getField('CN')?.value || 'Imzalayan')
    .replace(/İ/g, 'I').replace(/ı/g, 'i').replace(/ğ/g, 'g').replace(/Ğ/g, 'G')
    .replace(/ü/g, 'u').replace(/Ü/g, 'U').replace(/ş/g, 's').replace(/Ş/g, 'S')
    .replace(/ö/g, 'o').replace(/Ö/g, 'O').replace(/ç/g, 'c').replace(/Ç/g, 'C');
  const now = new Date();
  const dateStr = now.toLocaleDateString('tr-TR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });

  firstPage.drawText('Bu belge Aykome E-Imza ile imzalanmistir', {
    x: 50, y: 50, size: 9, font: helveticaBold, color: rgb(0.2, 0.2, 0.2),
  });
  firstPage.drawText(`${cnName} - ${dateStr}`, {
    x: 50, y: 38, size: 8, font: helvetica, color: rgb(0.4, 0.4, 0.4),
  });

  return await doc.save();
}

module.exports = { buildPades };
