const { buildPades } = require('../pades/sign-pdf');
const forge = require('node-forge');

async function executeSign(pdfBuffer, pkcs11Path, pin, certSerial) {
  if (!pkcs11Path || pkcs11Path === 'SIMULATION') {
    const { simulateSign } = require('./simulate');
    const signedPdf = await simulateSign(pdfBuffer, pin);
    return { signedPdf, certInfo: { commonName: 'Ahmet YILMAZ', tckn: '12345678901' } };
  }

  const bridge = getBridge(pkcs11Path);
  const { certDer } = bridge.getCertificate(pin, certSerial);

  const forgeCert = buildForgeCert(certDer);
  const certInfo = extractCertInfo(forgeCert);

  const signedPdf = await buildPades(pdfBuffer, certDer, certInfo.commonName, (hash) => {
    return bridge.signData(pin, hash);
  });

  return { signedPdf, certInfo };
}

function TLV(data, off) {
  const tag = data.charCodeAt(off);
  const lb = data.charCodeAt(off + 1);
  let len, h = 2;
  if (lb & 0x80) { const n = lb & 0x7F; len = 0; for (let i = 0; i < n; i++) len = (len << 8) | data.charCodeAt(off + 2 + i); h += n; }
  else { len = lb; }
  return { tag, len, hdr: h, valOff: off + h };
}

function decodeOid(rawBytes) {
  return forge.asn1.derToOid(rawBytes);
}

function dnToAttrs(forgeSets) {
  const attrs = [];
  for (const set of forgeSets) {
    if (!Array.isArray(set.value)) continue;
    for (const seq of set.value) {
      if (!Array.isArray(seq.value) || seq.value.length < 2) continue;
      const oidBytes = seq.value[0].value;
      const valNode = seq.value[1];
      let val = valNode.value || '';
      if (typeof val !== 'string') continue;
      if (valNode.type === 12) {
        val = Buffer.from(val, 'binary').toString('utf8');
      }
      const oid = decodeOid(oidBytes);
      const names = { '2.5.4.3': 'CN', '2.5.4.5': 'serialNumber', '2.5.4.6': 'C', '2.5.4.7': 'L', '2.5.4.8': 'ST', '2.5.4.10': 'O', '2.5.4.11': 'OU' };
      attrs.push({ oid, name: names[oid] || oid, value: val });
    }
  }
  return attrs;
}

function buildForgeCert(certDer) {
  const s = certDer.toString('binary');
  const certTlv = TLV(s, 0);
  const tbsOff = certTlv.valOff;
  const tbs = TLV(s, tbsOff);
  const tbsStart = tbsOff;
  const tbsEnd = tbsOff + tbs.hdr + tbs.len;
  const tbsAsn1 = forge.asn1.fromDer(s.slice(tbsStart, tbsEnd));

  const v = tbsAsn1.value;

  // serialNumber hex
  const serialInt = v[1];
  const serialDer = forge.asn1.toDer(serialInt).getBytes();
  const serialTlv = TLV(serialDer, 0);
  const serialHex = Buffer.from(serialDer.slice(serialTlv.valOff, serialTlv.valOff + serialTlv.len), 'binary').toString('hex');

  // issuer & subject
  const issuerRaw = dnToAttrs(v[3].value);
  const subjectRaw = dnToAttrs(v[5].value);

  const forgeAttrs = raw => raw.map(a => ({ type: a.oid, value: a.value, shortName: a.name }));

  // sig outside TBS
  const alg = TLV(s, tbsEnd);
  const algOidTlv = TLV(s, alg.valOff);
  const signatureOid = forge.asn1.derToOid(s.slice(algOidTlv.valOff, algOidTlv.valOff + algOidTlv.len));
  const bitStr = TLV(s, alg.valOff + alg.len);
  const sigRaw = s.slice(bitStr.valOff + 1, bitStr.valOff + bitStr.len);

  const nameMap = { CN: 'commonName', serialNumber: 'serialNumber', C: 'countryName', L: 'localityName', ST: 'stateOrProvinceName', O: 'organizationName', OU: 'organizationalUnitName', E: 'emailAddress' };

  return {
    tbsCertificate: tbsAsn1,
    signatureOid,
    signatureParameters: undefined,
    signature: sigRaw,
    serialNumber: serialHex,
    issuer: { attributes: forgeAttrs(issuerRaw) },
    subject: {
      attributes: forgeAttrs(subjectRaw),
      getField(name) {
        const oidName = nameMap[name] || name;
        const oid = forge.pki.oids[oidName];
        if (oid) {
          const found = subjectRaw.find(a => a.oid === oid);
          if (found) return { value: found.value };
        }
        const found = subjectRaw.find(a => a.name === name || a.oid === name);
        return found ? { value: found.value } : null;
      },
    },
  };
}

function extractCertInfo(forgeCert) {
  const cn = forgeCert.subject.getField('CN')?.value || '';
  const sn = forgeCert.subject.getField('serialNumber')?.value || '';
  return { commonName: cn, tckn: sn };
}

function getBridge(p11Path) {
  const { BridgeWorker } = require('../bridge');
  return new BridgeWorker(p11Path);
}

module.exports = { executeSign };
