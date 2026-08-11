const http = require('http');

// ⚠️ KRİTİK: 58210 KULLANILMAZ! Windows bu aralığı Docker Desktop / Hyper-V
// için rezerve eder (netsh interface ipv4 show excludedportrange) — örn.
// 58055-58154, 58155-58254, 58255-58354. Rezerve porta bind() EADDRINUSE
// verir, bu yüzden Electron her restart'ta port kaydırıyordu (58210→58355)
// ve web tarafı bulamıyordu. DEFAULT_PORT, rezerve aralıkların DIŞINDADIR.
// Buna rağmen port doluysa küçük bir aralıkta +1 atlanır; MAX_PORT web'in
// taradığı üst sınırdır (show.blade.php buildEimzaPorts → 58910..58930) —
// sınır aşılırsa hata döndürülür, port asla aralığın dışına çıkmaz.
const DEFAULT_PORT = 58910;
const MAX_PORT = 58930;

let serverInstance = null;
let signRequestHandler = null;

function start(port = DEFAULT_PORT) {
  return new Promise((resolve, reject) => {
    serverInstance = http.createServer((req, res) => {
      res.setHeader('Access-Control-Allow-Origin', '*');
      res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
      res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

      req.setTimeout(60000);
      res.setTimeout(60000);

      if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
      }

      if (req.method === 'POST' && req.url === '/sign') {
        let body = '';
        req.on('data', chunk => body += chunk);
        req.on('end', () => {
          try {
            const data = JSON.parse(body);
            if (!data.transaction_id || !data.server_url) {
              res.writeHead(400, { 'Content-Type': 'application/json' });
              res.end(JSON.stringify({ error: 'Eksik parametreler' }));
              return;
            }
            if (signRequestHandler) {
              signRequestHandler({
                transactionId: data.transaction_id,
                token: data.token || '',
                serverUrl: data.server_url,
              });
            }
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ status: 'received' }));
          } catch (e) {
            res.writeHead(400, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ error: 'Invalid JSON' }));
          }
        });
        return;
      }

      if (req.method === 'GET' && req.url === '/health') {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ status: 'ok', version: '1.0.0' }));
        return;
      }

      res.writeHead(404);
      res.end();
    });

    serverInstance.listen(port, '127.0.0.1', () => {
      console.log(`[E-Imza Server] http://127.0.0.1:${port}`);
      resolve(port);
    });

    serverInstance.on('error', (err) => {
      if (err.code === 'EADDRINUSE' || err.code === 'EACCES') {
        if (port >= MAX_PORT) {
          reject(new Error(`[E-Imza Server] Port ${port} kullanilamiyor ve ust sinir (${MAX_PORT}) asildi.`));
          return;
        }
        console.warn(`[E-Imza Server] Port ${port} kullanilamiyor (${err.code}), ${port + 1} deneniyor...`);
        start(port + 1).then(resolve).catch(reject);
      } else {
        reject(err);
      }
    });
  });
}

function stop() {
  if (serverInstance) {
    serverInstance.close();
    serverInstance = null;
  }
}

function onSignRequest(handler) {
  signRequestHandler = handler;
}

module.exports = { start, stop, onSignRequest };
