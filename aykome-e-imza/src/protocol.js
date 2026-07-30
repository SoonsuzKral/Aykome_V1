const url = require('url');

const PROTOCOL_PATTERN = /^aykome:\/\/sign\?/;

function parseSignUrl(signUrl) {
  const parsed = url.parse(signUrl, true);
  return {
    transactionId: parsed.query.tid,
    token: parsed.query.token,
    serverUrl: parsed.query.server,
  };
}

function handleSignUrl(signUrl, store, createPinWindow) {
  if (!PROTOCOL_PATTERN.test(signUrl)) {
    console.warn('Bilinmeyen aykome protokolü:', signUrl);
    return;
  }

  const { transactionId, token, serverUrl } = parseSignUrl(signUrl);

  if (!transactionId || !serverUrl) {
    console.error('Eksik parametreler:', { transactionId, serverUrl });
    return;
  }

  // Windows IPv6 fix: localhost -> 127.0.0.1
  const safeServerUrl = serverUrl.replace('//localhost', '//127.0.0.1');

  if (safeServerUrl) {
    store.set('server_url', safeServerUrl);
  }

  createPinWindow({ transactionId, token }, safeServerUrl);
}

module.exports = { handleSignUrl, parseSignUrl };
