#!/usr/bin/env node
// test_cms_crypto.js — PAdES PDF'ten CMS çıkarır ve KRİPTOGRAFİK olarak doğrular.
// Kullanım: node test_cms_crypto.js <imzali.pdf>
//  1) messageDigest attr == ByteRange içeriğinin özeti mi?
//  2) signature, embedded sertifikanın public key'i ile doğrulanıyor mu (RSA/ECDSA)?
//  3) Algoritma OID'leri + sertifika bilgileri raporlanır (asn1parse benzeri).
const fs = require('fs');
const crypto = require('crypto');

const pdfPath = process.argv[2];
if (!pdfPath) { console.error('Kullanim: node test_cms_crypto.js <imzali.pdf>'); process.exit(1); }

// ---------- DER yardimcilari ----------
function parseDer(buf, start) {
  const tag = buf[start];
  let lb = buf[start + 1];
  let len = 0, hdr = 2;
  if (lb & 0x80) { const n = lb & 0x7f; for (let i = 0; i < n; i++) len = (len << 8) | buf[start + 2 + i]; hdr += n; }
  else len = lb;
  return { tag, len, hdr, start, end: start + hdr + len, content: buf.subarray(start + hdr, start + hdr + len) };
}

function children(buf, s, e) {
  const out = [];
  let p = s;
  while (p < e) { const t = parseDer(buf, p); out.push(t); p = t.end; }
  return out;
}

function oidToStr(buf) {
  let r = '', v = 0, first = true;
  for (const b of buf) {
    v = (v << 7) | (b & 0x7f);
    if (!(b & 0x80)) {
      if (first) { r = Math.floor(v / 40) + '.' + (v % 40); first = false; }
      else r += '.' + v;
      v = 0;
    }
  }
  return r;
}

const OID_NAMES = {
  '1.2.840.113549.1.7.2': 'signedData',
  '1.2.840.113549.1.7.1': 'data',
  '1.2.840.113549.1.1.1': 'rsaEncryption',
  '1.2.840.113549.1.1.5': 'sha1WithRSA',
  '1.2.840.113549.1.1.11': 'sha256WithRSAEncryption',
  '1.2.840.113549.1.1.12': 'sha384WithRSAEncryption',
  '1.2.840.113549.1.1.13': 'sha512WithRSAEncryption',
  '1.2.840.113549.1.9.3': 'contentType',
  '1.2.840.113549.1.9.4': 'messageDigest',
  '1.2.840.113549.1.9.5': 'signingTime',
  '2.16.840.1.101.3.4.2.1': 'sha256',
  '2.16.840.1.101.3.4.2.2': 'sha384',
  '1.2.840.10045.4.3.3': 'ecdsa-with-SHA384',
  '1.2.840.10045.4.3.2': 'ecdsa-with-SHA256',
  '1.2.840.10045.2.1': 'id-ecPublicKey',
  '1.3.132.0.34': 'secp384r1 (P-384)',
  '1.2.840.10045.3.1.7': 'prime256v1 (P-256)',
};

function oidName(oid) { return OID_NAMES[oid] || oid; }

function algIdInfo(buf, a) {
  const kids = children(buf, a.start + a.hdr, a.end);
  const oid = kids[0] ? oidToStr(buf.subarray(kids[0].start + kids[0].hdr, kids[0].end)) : '?';
  const hasNull = kids[1] && kids[1].tag === 0x05;
  return { oid, hasNull, name: oidName(oid) };
}

function algIdInfoAt(buf, pos) {
  const a = parseDer(buf, pos);
  return { ...algIdInfo(buf, a), next: a.end };
}

// ---------- PDF'ten CMS + ByteRange ----------
function extractSig(pdfBuf) {
  const s = pdfBuf.toString('latin1');
  const br = /\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/.exec(s);
  if (!br) throw new Error('ByteRange bulunamadi');
  const seg = [1, 2, 3, 4].map(i => parseInt(br[i], 10));
  const content = Buffer.concat([
    pdfBuf.subarray(seg[0], seg[0] + seg[1]),
    pdfBuf.subarray(seg[2], seg[2] + seg[3]),
  ]);
  // Imza /Contents'i ByteRange'ten SONRA aranir (ilk /Contents sayfa akisi olabilir).
  const ci = s.indexOf('/Contents ', br.index);
  if (ci < 0) throw new Error('Imza /Contents bulunamadi');
  const lt = s.indexOf('<', ci);
  const gt = s.indexOf('>', lt);
  const hex = s.slice(lt + 1, gt).replace(/\s+/g, '');
  const cms = Buffer.from(hex, 'hex');
  return { content, cms, byteRange: seg };
}

// ---------- CMS SignedData analizi ----------
function analyzeCms(cms) {
  const root = parseDer(cms, 0);
  if (root.tag !== 0x30) throw new Error('CMS SEQUENCE degil, tag=' + root.tag.toString(16));
  const ciKids = children(cms, root.start + root.hdr, root.end);
  const ctOid = oidToStr(cms.subarray(ciKids[0].start + ciKids[0].hdr, ciKids[0].end));
  if (ctOid !== '1.2.840.113549.1.7.2') throw new Error('signedData degil: ' + ctOid);
  const a0 = ciKids[1];
  const sd = parseDer(cms, a0.start + a0.hdr);
  const sdKids = children(cms, sd.start + sd.hdr, sd.end);
  const version = sdKids[0];
  const digAlgs = sdKids[1];
  const eci = sdKids[2];
  let certs = null, siSet = null;
  for (const k of sdKids.slice(3)) {
    if (k.tag === 0xA0) certs = k;
    else if (k.tag === 0x31) siSet = k;
  }
  const certDer = certs ? Buffer.from(cms.subarray(certs.start + certs.hdr, certs.end)) : null;

  // eContent varsa:
  let eContent = null;
  {
    const eciKids = children(cms, eci.start + eci.hdr, eci.end);
    const eciType = eciKids[0] ? oidToStr(cms.subarray(eciKids[0].start + eciKids[0].hdr, eciKids[0].end)) : null;
    if (eciKids[1] && eciKids[1].tag === 0xA0) {
      const ec = parseDer(cms, eciKids[1].start + eciKids[1].hdr);
      eContent = Buffer.from(cms.subarray(ec.start + ec.hdr, ec.end));
    }
    eci.oid = eciType;
  }

  const si = parseDer(cms, siSet.start + siSet.hdr);
  const siKids = children(cms, si.start + si.hdr, si.end);
  const siVersion = siKids[0];
  const sid = siKids[1];
  const digAlg = siKids[2];
  const dA = algIdInfo(cms, digAlg);

  let signedAttrsBytes = null, sigAlg = null, signature = null, signedAttrsA0 = null;
  for (const k of siKids.slice(3)) {
    if (k.tag === 0xA0 && !signedAttrsBytes) {
      // [0] IMPLICIT SET OF — RFC 5652 §5.4: imza, SET OF (0x31) DER kodlamasi
      // üzerine atilir (A0 etiketi hash girdisine DAHIL EDILMEZ)
      const lenBytes = k.len < 128 ? Buffer.from([k.len]) : (k.len < 256 ? Buffer.from([0x81, k.len]) : Buffer.from([0x82, (k.len >> 8) & 0xff, k.len & 0xff]));
      signedAttrsBytes = Buffer.concat([Buffer.from([0x31]), lenBytes, Buffer.from(cms.subarray(k.start + k.hdr, k.end))]);
      signedAttrsA0 = Buffer.from(cms.subarray(k.start, k.end));
    } else if (k.tag === 0x30 && !sigAlg) {
      sigAlg = algIdInfo(cms, k);
    } else if (k.tag === 0x03 || k.tag === 0x04) {
      // 0x03 = BIT STRING (dogru, 1. bayt unused-bits sayaci) | 0x04 = OCTET STRING
      signature = Buffer.from(cms.subarray(k.start + k.hdr + (k.tag === 0x03 ? 1 : 0), k.end));
    }
  }

  // signedAttrs icindeki attribute'lari cozumle
  const attrs = [];
  if (signedAttrsBytes) {
    const saRoot = parseDer(signedAttrsBytes, 0);
    for (const attr of children(signedAttrsBytes, saRoot.start + saRoot.hdr, saRoot.end)) {
      const kids = children(signedAttrsBytes, attr.start + attr.hdr, attr.end);
      const oid = oidToStr(signedAttrsBytes.subarray(kids[0].start + kids[0].hdr, kids[0].end));
      const valSet = kids[1];
      if (valSet) {
        const vals = children(signedAttrsBytes, valSet.start + valSet.hdr, valSet.end);
        // Attribute value SET icindeki ilk deger; SEQUENCE ise OCTET STRING'i bul
        let val = vals[0];
        if (val && val.tag === 0x30) {
          const inner = children(signedAttrsBytes, val.start + val.hdr, val.end);
          const oct = inner.find(k => k.tag === 0x04);
          if (oct) val = oct;
        }
        attrs.push({ oid, name: oidName(oid), value: Buffer.from(signedAttrsBytes.subarray(val.start + val.hdr, val.end)) });
      }
    }
  }

  return { ctOid, version, digAlgsCount: children(cms, digAlgs.start + digAlgs.hdr, digAlgs.end).length, eci, eContent, certDer, certs, siVersion, sid, digAlg: dA, signedAttrsBytes, attrs, sigAlg, signature, signedAttrsA0 };
}

// ---------- ana akis ----------
function main() {
  const pdfBuf = fs.readFileSync(pdfPath);
  const { content, cms, byteRange } = extractSig(pdfBuf);
  console.log('PDF: ' + pdfPath);
  console.log('ByteRange: [' + byteRange.join(' ') + '] | CMS: ' + cms.length + ' bayt\n');

  const info = analyzeCms(cms);
  console.log('contentType: ' + oidName(info.ctOid));
  console.log('signedData version: ' + info.version.content[0]);
  console.log('digestAlgorithms sayisi: ' + info.digAlgsCount);
  console.log('encapContentInfo eContent: ' + (info.eContent ? info.eContent.length + ' bayt' : 'YOK (detached)'));
  console.log('sertifika gomulu: ' + (info.certDer ? 'evet (' + info.certDer.length + ' bayt)' : 'HAYIR!'));
  console.log('signerInfo version: ' + info.siVersion.content[0]);
  console.log('signerInfo digestAlgorithm: ' + info.digAlg.name + (info.digAlg.hasNull ? ' [params=NULL]' : ' [params=ABSENT]'));
  console.log('signerInfo signatureAlgorithm: ' + (info.sigAlg ? info.sigAlg.name : '?'));
  console.log('signedAttrs: ' + info.attrs.map(a => a.name).join(', '));
  console.log('signature: ' + info.signature.length + ' bayt | ilk bayt 0x' + info.signature[0].toString(16) + '\n');

  const problems = [];

  // 1) messageDigest kontrolu
  const mdAttr = info.attrs.find(a => a.name === 'messageDigest');
  if (!mdAttr) { problems.push('messageDigest attr YOK'); }
  else {
    const digestName = info.digAlg.oid === '2.16.840.1.101.3.4.2.1' ? 'sha256' : 'sha384';
    const calc = crypto.createHash(digestName).update(content).digest();
    if (calc.equals(mdAttr.value)) console.log('[1] messageDigest == ByteRange ozeti: OK (' + digestName + ')');
    else { console.log('[1] messageDigest UYUSMUYOR! hesaplanan ' + calc.toString('hex').slice(0, 24) + '... vs attr ' + mdAttr.value.toString('hex').slice(0, 24) + '...'); problems.push('messageDigest uyusmazligi'); }
  }

  // 2) signature dogrulama — embedded sertifika public key'i ile
  if (!info.certDer) { problems.push('sertifika yok — imza dogrulanamadi'); }
  else {
    let cert;
    try { cert = new crypto.X509Certificate(info.certDer); }
    catch (e) { problems.push('X509 parse hatasi: ' + e.message); }
    if (cert) {
      const key = cert.publicKey;
      const kt = key.asymmetricKeyType;
      let curve = '';
      if (key.asymmetricKeyDetails && key.asymmetricKeyDetails.namedCurve) curve = ' (' + key.asymmetricKeyDetails.namedCurve + ')';
      console.log('sertifika: CN=' + (cert.subject.split('\n').find(l => l.startsWith('CN=')) || '?').replace('CN=', ''));
      console.log('sertifika publicKey: ' + kt + curve);

      let ok = false, verifyErr = null;
      try {
        if (kt === 'ec') {
          ok = crypto.verify('sha384', info.signedAttrsBytes, { key, dsaEncoding: 'der' }, info.signature);
        } else if (kt === 'rsa') {
          const d = info.digAlg.oid === '2.16.840.1.101.3.4.2.1' ? 'sha256' : 'sha384';
          ok = crypto.verify(d, info.signedAttrsBytes, key, info.signature);
        } else {
          problems.push('Bilinmeyen anahtar tipi: ' + kt);
        }
      } catch (e) { verifyErr = e.message; }

      if (ok) console.log('[2] imza, gomulu sertifika ile DOGRULANDI (signedAttrs uzerinden)');
      else {
        console.log('[2] IMZA DOGRULANAMADI! ' + (verifyErr || 'kriptografik uyusmazlik'));
        problems.push('imza dogrulama hatasi: ' + (verifyErr || 'signedAttrs uyusmazligi'));
      }
    }
  }

  // 3) DER siki kurallari: signedAttrs SET OF siralamasi (DER canonical)
  //    DER canonical = attribute'larin TAM DER encoding'lerinin byte-lexicographic
  //    siralamasi (OID sirasi DEGIL!). Kamu SM/E-Tugra SDK'lari da ayni duzeni uretir.
  {
    const a0raw = info.signedAttrsA0;
    let canonOk = true, prev = null;
    if (a0raw) {
      let off = 1 + (a0raw[1] & 0x80 ? (a0raw[1] & 0x7f) : 0) + 1;
      while (off < a0raw.length) {
        const tag = a0raw[off];
        let lb = a0raw[off + 1], len = lb, hdr = 2;
        if (lb & 0x80) {
          const n = lb & 0x7f;
          len = 0;
          for (let i = 0; i < n; i++) len = (len << 8) | a0raw[off + 2 + i];
          hdr += n;
        }
        const bytes = a0raw.subarray(off, off + hdr + len);
        if (prev && Buffer.compare(prev, bytes) >= 0) canonOk = false;
        prev = bytes;
        off += hdr + len;
      }
    }
    const order = info.attrs.map(a => oidName(a.oid));
    console.log('\n[3] DER SET OF sirasi: ' + order.join(', ') + (canonOk ? ' — DER canonical OK' : ' — DER canonical DEGIL (byte siralama)'));
    if (!canonOk) problems.push('SET OF siralamasi DER canonical degil');
  }

  // 4) sertifika public key ile signerInfo signatureAlgorithm uyumu
  if (info.certDer) {
    try {
      const cert = new crypto.X509Certificate(info.certDer);
      const kt = cert.publicKey.asymmetricKeyType;
      const sigAlgOid = info.sigAlg ? info.sigAlg.oid : '';
      const okPair = (kt === 'rsa' && (sigAlgOid === '1.2.840.113549.1.1.1' || sigAlgOid === '1.2.840.113549.1.1.11')) ||
                     (kt === 'ec' && sigAlgOid === '1.2.840.10045.4.3.3');
      console.log('[4] anahtar tipi ' + kt + ' vs signatureAlgorithm ' + (info.sigAlg ? info.sigAlg.name : '?') + ' — ' + (okPair ? 'uyumlu' : 'UYUMSUZ!'));
      if (!okPair) problems.push('anahtar tipi/signatureAlgorithm uyumsuz');
    } catch (e) { /* yukarida raporlandi */ }
  }

  // 5) ESS SigningCertificateV2 (ETSI EN 319 102-1 AdES-BES) — mevcut mu, certHash dogru mu?
  {
    const ess = info.attrs.find(a => a.oid === '1.2.840.113549.1.9.16.2.47');
    let ok = false, detay = '';
    if (ess && info.certDer) {
      const expected = crypto.createHash('sha256').update(info.certDer).digest();
      ok = expected.equals(ess.value);
      detay = ok ? ' (certHash == sha256(sertifika) OK)' : ' (certHash UYUMSUZ!)';
    }
    console.log('[5] ESS SigningCertificateV2 ozniteligi: ' + (ess ? (ok ? 'MEVCUT ve dogru' + detay : 'MEVCUT ' + detay) : 'YOK! (AdES-BES eksigi)'));
    if (!ok) problems.push('signingCertificateV2 eksik/yanlis');
  }

  console.log('\nSONUC: ' + (problems.length === 0 ? 'TUM KRIPTOGRAFIK KONTROLLER GECTI' : problems.length + ' SORUN: ' + problems.join(' | ')));
  process.exit(problems.length === 0 ? 0 : 1);
}

main();
