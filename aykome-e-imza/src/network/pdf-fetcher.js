const axios = require('axios');
const { PDFDocument } = require('pdf-lib');

async function downloadPdf(transaction, store) {
  const { transactionId, token, serverUrl } = transaction;

  if (!transactionId || !serverUrl) {
    throw new Error('Eksik işlem bilgisi: transaction_id veya server_url bulunamadı');
  }

  // Windows: localhost -> 127.0.0.1 (IPv6 cozumu engelle)
  const safeServerUrl = serverUrl.replace('//localhost', '//127.0.0.1');
  const pdfUrl = `${safeServerUrl}/api/e-imza/pdf/${transactionId}?token=${token}`;

  const response = await axios.get(pdfUrl, {
    responseType: 'arraybuffer',
    timeout: 90000,
    validateStatus: (status) => status < 500,
  });

  if (response.status === 403) {
    throw new Error('PDF indirme yetkisi yok. İşlem süresi dolmuş olabilir.');
  }

  if (response.status !== 200) {
    throw new Error(`PDF indirme hatası: HTTP ${response.status}`);
  }

  let pdfBuffer = Buffer.from(response.data);

  // node-signpdf yalnızca klasik xref tablosunu destekler; kaynak PDF xref stream
  // (PDF 1.5+) içeriyorsa parse edemez. Güvenli dönüşüm: pdf-lib ile yeniden yaz.
  if (pdfBuffer.includes('startxref') && !pdfBuffer.slice(pdfBuffer.lastIndexOf('startxref')).includes('trailer')) {
    try {
      const doc = await PDFDocument.load(pdfBuffer, { updateMetadata: false });
      const bytes = await doc.save({ useObjectStreams: false });
      pdfBuffer = Buffer.from(bytes);
    } catch (e) {
      // Dönüşüm başarısızsa orijinali kullan; buildPades hata verirse zaten görünür.
    }
  }

  return pdfBuffer;
}

module.exports = { downloadPdf };
