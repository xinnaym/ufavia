const express = require('express');
const http = require('http');
const path = require('path');
const { spawn } = require('child_process');

const app = express();
const PORT = process.env.PORT || 3000;
const HOST = '0.0.0.0';
const PHP_PORT = 8080;
const PHP_HOST = '127.0.0.1';

// Serve static assets directly for high performance
app.use('/assets', express.static(path.join(__dirname, 'assets')));
app.use('/public', express.static(path.join(__dirname, 'public')));

let phpProcess = null;

function startPhpBackend() {
  if (phpProcess) {
    try {
      phpProcess.kill('SIGKILL');
    } catch (e) {}
  }

  phpProcess = spawn('php', ['-S', `${PHP_HOST}:${PHP_PORT}`, 'router.php'], {
    cwd: __dirname,
    stdio: ['ignore', 'inherit', 'inherit']
  });

  phpProcess.on('error', (err) => {
    console.error('PHP process error:', err.message);
  });

  phpProcess.on('exit', (code, signal) => {
    console.log(`PHP process exited with code ${code}, signal ${signal}. Restarting in 1s...`);
    setTimeout(startPhpBackend, 1000);
  });
}

startPhpBackend();

process.on('SIGINT', () => {
  if (phpProcess) phpProcess.kill();
  process.exit();
});

process.on('SIGTERM', () => {
  if (phpProcess) phpProcess.kill();
  process.exit();
});

// Proxy all other requests to PHP built-in server
app.use((req, res) => {
  const forward = (retries = 5) => {
    const options = {
      hostname: PHP_HOST,
      port: PHP_PORT,
      path: req.originalUrl || req.url,
      method: req.method,
      headers: {
        ...req.headers,
        host: req.headers.host || `${HOST}:${PORT}`,
        'x-forwarded-for': req.socket.remoteAddress,
        'x-forwarded-proto': 'http'
      }
    };

    const proxyReq = http.request(options, (proxyRes) => {
      res.writeHead(proxyRes.statusCode, proxyRes.headers);
      proxyRes.pipe(res);
    });

    proxyReq.on('error', (err) => {
      if (retries > 0) {
        setTimeout(() => forward(retries - 1), 200);
      } else {
        res.status(502).send('Ошибка соединения с бэкендом: ' + err.message);
      }
    });

    req.pipe(proxyReq);
  };

  forward();
});

app.listen(PORT, HOST, () => {
  console.log(`UFAvia server running at http://${HOST}:${PORT}`);
});
