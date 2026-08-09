const http = require('http');

const DEFAULT_PORT = 57898;

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
      if (err.code === 'EADDRINUSE') {
        console.warn(`[E-Imza Server] Port ${port} dolu, ${port + 1} deneniyor...`);
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
