const forge = require('node-forge');
const { findByteRange, removeTrailingNewLine, plainAddPlaceholder } = require('node-signpdf');

const OID_ECDSA_SHA384 = '1.2.840.10045.4.3.3';
const OID_SIGNED_DATA = '1.2.840.113549.1.7.2';
const OID_DATA = '1.2.840.113549.1.7.1';
const OID_SHA384 = '2.16.840.1.101.3.4.2.2';

function derLen(n) {
  if (n < 128) return Buffer.from([n]);
  if (n < 256) return Buffer.from([0x81, n]);
  if (n < 65536) return Buffer.from([0x82, n >> 8, n & 0xff]);
  throw new Error('DER length too big');
}

function der(tag, content) {
  return Buffer.concat([Buffer.from([tag]), derLen(content.length), content]);
}

const derSeq = (...parts) => der(0x30, Buffer.concat(parts));
const derSet = (...parts) => der(0x31, Buffer.concat(parts));
const derInt = (b) => der(0x02, b);
const derOct = (b) => der(0x04, b);
const derNull = () => Buffer.from([0x05, 0x00]);

function derOid(oid) {
  return der(0x06, Buffer.from(forge.asn1.oidToDer(oid).getBytes(), 'binary'));
}

function derUtcTime(d) {
  const p = (n) => String(n).padStart(2, '0');
  const s = `${p(d.getUTCFullYear() % 100)}${p(d.getUTCMonth() + 1)}${p(d.getUTCDate())}` +
    `${p(d.getUTCHours())}${p(d.getUTCMinutes())}${p(d.getUTCSeconds())}Z`;
  return der(0x17, Buffer.from(s, 'ascii'));
}

function tlvAt(data, off) {
  const tag = data.charCodeAt(off);
  const lb = data.charCodeAt(off + 1);
  let len;
  let hdr = 2;
  if (lb & 0x80) {
    const n = lb & 0x7f;
    len = 0;
    for (let i = 0; i < n; i++) len = (len << 8) | data.charCodeAt(off + 2 + i);
    hdr += n;
  } else {
    len = lb;
  }
  return { tag, len, hdr, valOff: off + hdr };
}

function certIssuerAndSerial(certDer) {
  const s = certDer.toString('binary');
  let off = 0;
  const cert = tlvAt(s, off); off = cert.valOff;
  const tbs = tlvAt(s, off); off = tbs.valOff;
  const ver = tlvAt(s, off); off += ver.hdr + ver.len;
  const ser = tlvAt(s, off);
  const serialRaw = s.slice(ser.valOff, ser.valOff + ser.len);
  off += ser.hdr + ser.len;
  const alg = tlvAt(s, off); off += alg.hdr + alg.len;
  const iss = tlvAt(s, off);
  const issuerRaw = s.slice(iss.valOff, iss.valOff + iss.len);
  return {
    issuerDer: der(0x30, Buffer.from(issuerRaw, 'binary')),
    serialDer: der(0x02, Buffer.from(serialRaw, 'binary')),
  };
}

function derAttr(oid, ...values) {
  return derSeq(derOid(oid), derSet(...values));
}

function sha384Bytes(data) {
  const md = forge.md.sha384.create();
  md.update(data.toString('binary'));
  return Buffer.from(md.digest().getBytes(), 'binary');
}

function buildCms(contentBytes, certDer, signCallback) {
  const { issuerDer, serialDer } = certIssuerAndSerial(certDer);
  const contentDigest = sha384Bytes(contentBytes);

  const attrsContent = Buffer.concat([
    derAttr(forge.pki.oids.contentType, derOid(OID_DATA)),
    derAttr(forge.pki.oids.signingTime, derUtcTime(new Date())),
    derAttr(forge.pki.oids.messageDigest, derOct(contentDigest)),
  ]);
  const signedAttrs = der(0xA0, attrsContent);

  const attrsDigest = sha384Bytes(der(0x31, attrsContent));
  const signatureDer = signCallback(attrsDigest);

  const signerInfo = derSeq(
    derInt(Buffer.from([1])),
    derSeq(issuerDer, serialDer),
    derSeq(derOid(OID_SHA384), derNull()),
    signedAttrs,
    derSeq(derOid(OID_ECDSA_SHA384)),
    derOct(signatureDer),
  );

  const signedData = derSeq(
    derInt(Buffer.from([1])),
    derSet(derSeq(derOid(OID_SHA384), derNull())),
    derSeq(derOid(OID_DATA)),
    der(0xA0, certDer),
    derSet(signerInfo),
  );

  return derSeq(
    derOid(OID_SIGNED_DATA),
    der(0xA0, signedData),
  );
}

function buildPades(pdfBuffer, certDer, cnName, signCallback) {
  // GÖREV 1 — Görsel imza kaldırıldı. Electron e-imza işlemi PDF'e TEK PİKSEL görsel
  // müdahale yapmaz: belge, DomPDF'ten nasıl geldiyse birebir aynı kalır. PAdES mührü
  // yalnızca görünmez (invisible) kriptografik imza olarak atılır — widget /AP'siz,
  // /Rect'siz üretilir (node-signpdf plainAddPlaceholder). cnName parametresi görsel
  // basılmaz; backend "token sertifika CN ↔ baslatan kullanıcı" güvenlik kilidi için
  // elektrodaki certInfo.commonName aktarım zincirinde korunur.
  let pdf = removeTrailingNewLine(pdfBuffer);

  // Invisible PAdES placeholder: /AP yok, /Rect yok, AcroForm SigFlags=3 dolu.
  pdf = plainAddPlaceholder({
    pdfBuffer: pdf,
    reason: 'Aykome E-Imza',
    location: 'Sanliurfa',
    name: 'Aykome E-Imza',
    contactInfo: 'aykome@local',
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

  const gapStart = placeholderPos;
  const gapSize = placeholderLengthWithBrackets;
  const region2Start = gapStart + gapSize;
  const region2Size = pdf.length - region2Start;

  const byteRange = [0, gapStart, gapSize, region2Size];

  let actualByteRange = `/ByteRange [${byteRange.join(' ')}]`;
  actualByteRange += ' '.repeat(byteRangePlaceholder.length - actualByteRange.length);

  pdf = Buffer.concat([
    pdf.slice(0, byteRangePos),
    Buffer.from(actualByteRange),
    pdf.slice(byteRangeEnd),
  ]);

  const signedContent = Buffer.concat([
    pdf.slice(0, gapStart),
    pdf.slice(region2Start),
  ]);

  const cms = buildCms(signedContent, certDer, signCallback);
  const expectedLen = Math.floor(placeholderLength / 2);

  if (cms.length > expectedLen) {
    throw new Error(`Signature too long: ${cms.length} > ${expectedLen}`);
  }

  let signatureHex = cms.toString('hex');
  signatureHex += '0'.repeat(placeholderLength - signatureHex.length);

  pdf = Buffer.concat([
    pdf.slice(0, gapStart),
    Buffer.from(`<${signatureHex}>`),
    pdf.slice(gapStart + gapSize),
  ]);

  return pdf;
}

module.exports = { buildPades };
