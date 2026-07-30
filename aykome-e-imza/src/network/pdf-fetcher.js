const axios = require('axios');

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
    validateStatus: (status) => status < 500,
  });

  if (response.status === 403) {
    throw new Error('PDF indirme yetkisi yok. İşlem süresi dolmuş olabilir.');
  }

  if (response.status !== 200) {
    throw new Error(`PDF indirme hatası: HTTP ${response.status}`);
  }

  return Buffer.from(response.data);
}

module.exports = { downloadPdf };
