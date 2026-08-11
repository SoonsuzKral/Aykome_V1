const fs = require('fs');
const pdf = fs.readFileSync(process.argv[2]);
const pdfStr = pdf.toString('latin1');
const bIdx = pdfStr.indexOf('/ByteRange');
const m = /\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/.exec(pdfStr.slice(bIdx));
if (!m) { console.error('ByteRange bulunamadi'); process.exit(1); }
const br = m.slice(1).map(Number);
const cmsStart = pdfStr.indexOf('/Contents <', bIdx);
const hexStart = pdfStr.indexOf('<', cmsStart) + 1;
const hexEnd = pdfStr.indexOf('>', hexStart);
const cms = Buffer.from(pdfStr.slice(hexStart, hexEnd), 'hex');
const seg1 = pdf.slice(br[0], br[0] + br[1]);
const seg2 = pdf.slice(br[2], br[2] + br[3]);
const content = Buffer.concat([seg1, seg2]);
fs.writeFileSync(process.argv[3] || 'cms_out.der', cms);
fs.writeFileSync(process.argv[4] || 'content_out.bin', content);
console.log('ByteRange:', JSON.stringify(br), '| CMS:', cms.length, 'bayt | content:', content.length, 'bayt');
