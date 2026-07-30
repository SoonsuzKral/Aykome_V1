const axios = require('axios');
const FormData = require('form-data');

async function uploadSignedPdf(transaction, signedPdfBuffer, certInfo, store) {
  const { transactionId, serverUrl } = transaction;
  const apiKey = store.get('api_key');

  if (!apiKey) {
    throw new Error('API anahtarı bulunamadı. Lütfen kurulum sihirbazını çalıştırın.');
  }

  // Windows: localhost -> 127.0.0.1 (IPv6 cozumu engelle)
  const safeServerUrl = serverUrl.replace('//localhost', '//127.0.0.1');

  const form = new FormData();
  form.append('transaction_id', transactionId);
  form.append('file', Buffer.from(signedPdfBuffer), {
    filename: 'imzali.pdf',
    contentType: 'application/pdf',
  });
  form.append('imzalayan[ad]', (certInfo?.commonName || '').split(' ')[0] || '');
  form.append('imzalayan[soyad]', (certInfo?.commonName || '').split(' ').slice(1).join(' ') || '');
  form.append('imzalayan[tckn]', certInfo?.tckn || '');
  form.append('imzalayan[sertifika_turu]', 'Kamu SM');

  const response = await axios.post(`${safeServerUrl}/api/e-imza/tamamla`, form, {
    headers: {
      ...form.getHeaders(),
      'X-EImza-Api-Key': apiKey,
    },
    validateStatus: (status) => status < 500,
  });

  if (response.status !== 200) {
    throw new Error(`İmzalı PDF yüklenirken hata: ${response.data?.message || 'Bilinmeyen hata'}`);
  }

  return response.data;
}

module.exports = { uploadSignedPdf };