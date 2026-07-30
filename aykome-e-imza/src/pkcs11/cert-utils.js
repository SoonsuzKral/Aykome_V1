const forge = require('node-forge');

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
      if (valNode.type === 12) val = Buffer.from(val, 'binary').toString('utf8');
      const oid = decodeOid(oidBytes);
      const names = { '2.5.4.3': 'CN', '2.5.4.5': 'serialNumber', '2.5.4.6': 'C', '2.5.4.7': 'L', '2.5.4.8': 'ST', '2.5.4.10': 'O', '2.5.4.11': 'OU' };
      attrs.push({ oid, name: names[oid] || oid, value: val });
    }
  }
  return attrs;
}

function parseCertificate(certDer) {
  try {
    const s = certDer.toString('binary');
    const certTlv = TLV(s, 0);
    const tbs = TLV(s, certTlv.valOff);
    const tbsEnd = certTlv.valOff + tbs.hdr + tbs.len;
    const tbsAsn1 = forge.asn1.fromDer(s.slice(certTlv.valOff, tbsEnd));
    const v = tbsAsn1.value;

    // issuer = v[3], subject = v[5]
    const subjectRaw = dnToAttrs(v[5].value);
    const issuerRaw = dnToAttrs(v[3].value);

    const cn = subjectRaw.find(a => a.oid === '2.5.4.3')?.value || 'Bilinmeyen';
    const tckn = subjectRaw.find(a => a.oid === '2.5.4.5')?.value || '';

    const issuerMap = {};
    issuerRaw.forEach(a => { issuerMap[a.name] = a.value; });

    // serial
    const serialInt = v[1];
    const serialDer = forge.asn1.toDer(serialInt).getBytes();
    const serialTlv = TLV(serialDer, 0);
    const serialHex = Buffer.from(serialDer.slice(serialTlv.valOff, serialTlv.valOff + serialTlv.len), 'binary').toString('hex');

    // validity
    const validFrom = v[4].value[0].value;
    const validTo = v[4].value[1].value;

    return {
      subject: Object.fromEntries(subjectRaw.map(a => [a.name, a.value])),
      issuer: issuerMap,
      serialNumber: serialHex,
      serialHex,
      commonName: cn,
      tckn,
      validFrom: new Date(validFrom),
      validTo: new Date(validTo),
      isExpired: new Date() > new Date(validTo) || new Date() < new Date(validFrom),
    };
  } catch (err) {
    return {
      commonName: 'Bilinmeyen',
      tckn: '',
      isExpired: false,
    };
  }
}

function formatValidity(certInfo) {
  const from = certInfo.validFrom ? certInfo.validFrom.toDateString() : '?';
  const to = certInfo.validTo ? certInfo.validTo.toDateString() : '?';
  return `${from} - ${to}`;
}

function isExpired(certInfo) {
  return certInfo.isExpired;
}

module.exports = { parseCertificate, formatValidity, isExpired };
