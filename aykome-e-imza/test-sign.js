const fs = require('fs');
const { executeSign } = require('./src/pkcs11/signer');

function createMinimalPdf(path) {
  const content = `%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj
4 0 obj<</Length 108>>stream
BT
/F1 24 Tf
100 700 Td
(AYKOME E-Imza Test Belgesi) Tj
/F1 12 Tf
100 660 Td
(Bu belge Aykome E-Imza ile imzalanacaktir.) Tj
ET
endstream
endobj
5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj
6 0 obj<</Type/Encoding/Differences[0/.notdef/A/B/C/D/E/F/G/H/I/J/K/L/M/N/O/P/Q/R/S/T/U/V/W/X/Y/Z/a/b/c/d/e/f/g/h/i/j/k/l/m/n/o/p/q/r/s/t/u/v/w/x/y/z/space/parenleft/parenright/comma/hyphen/period/one/two/three/four/five/six/seven/eight/nine]>>endobj
xref
0 7
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000266 00000 n 
0000000427 00000 n 
0000000502 00000 n 
trailer
<</Size 7/Root 1 0 R>>
startxref
590
%%EOF
`;
  fs.writeFileSync(path, content, 'utf8');
}

async function main() {
  const pdfPath = process.argv[2] || '/tmp/test_imza.pdf';
  const outputPath = process.argv[3] || '/tmp/imzali.pdf';

  if (!fs.existsSync(pdfPath)) {
    console.log(`Test PDF oluşturuluyor: ${pdfPath}`);
    createMinimalPdf(pdfPath);
    console.log('Tamam.');
  }

  const pdfBuffer = fs.readFileSync(pdfPath);
  console.log(`Imzalanıyor: ${pdfPath} (${pdfBuffer.length} bytes)`);
  console.log('Token: AKIS (libakisp11.dylib)');

  const result = await executeSign(pdfBuffer, '/usr/local/lib/libakisp11.dylib', '321463', '');

  fs.writeFileSync(outputPath, result.signedPdf);
  console.log(`\n✅ BAŞARILI! İmzalı PDF: ${outputPath}`);
  console.log(`   Boyut: ${result.signedPdf.length} bytes`);
  console.log(`   İmzalayan: ${result.certInfo.commonName}`);
  console.log(`   TCKN: ${result.certInfo.tckn}`);
}

main().catch(e => {
  console.error(`\n❌ HATA: ${e.message}`);
  process.exit(1);
});
