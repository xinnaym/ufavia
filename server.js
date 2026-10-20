const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;
const HOST = '0.0.0.0';

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static assets
app.use('/assets', express.static(path.join(__dirname, 'assets')));
app.use('/public', express.static(path.join(__dirname, 'public')));
app.use(express.static(path.join(__dirname)));

// Route aliases
const routes = {
  '/': 'index.html',
  '/index.html': 'index.html',
  '/index.php': 'index.html',
  '/search': 'pages/search.html',
  '/search.html': 'pages/search.html',
  '/search.php': 'pages/search.html',
  '/pages/search.html': 'pages/search.html',
  '/pages/search.php': 'pages/search.html',
  '/tickets': 'pages/tickets.html',
  '/tickets.html': 'pages/tickets.html',
  '/tickets.php': 'pages/tickets.html',
  '/pages/tickets.html': 'pages/tickets.html',
  '/pages/tickets.php': 'pages/tickets.html',
  '/cart': 'pages/cart.html',
  '/cart.html': 'pages/cart.html',
  '/cart.php': 'pages/cart.html',
  '/pages/cart.html': 'pages/cart.html',
  '/pages/cart.php': 'pages/cart.html',
  '/order': 'pages/order.html',
  '/order.html': 'pages/order.html',
  '/order.php': 'pages/order.html',
  '/pages/order.html': 'pages/order.html',
  '/pages/order.php': 'pages/order.html',
  '/checkout': 'pages/checkout.html',
  '/checkout.html': 'pages/checkout.html',
  '/checkout.php': 'pages/checkout.html',
  '/pages/checkout.html': 'pages/checkout.html',
  '/pages/checkout.php': 'pages/checkout.html',
  '/profile': 'pages/profile.html',
  '/profile.html': 'pages/profile.html',
  '/profile.php': 'pages/profile.html',
  '/pages/profile.html': 'pages/profile.html',
  '/pages/profile.php': 'pages/profile.html',
  '/admin': 'pages/admin.html',
  '/admin.html': 'pages/admin.html',
  '/admin.php': 'pages/admin.html',
  '/pages/admin.html': 'pages/admin.html',
  '/pages/admin.php': 'pages/admin.html',
};

// Map each route
for (const [routePath, filePath] of Object.entries(routes)) {
  app.get(routePath, (req, res) => {
    const fullPath = path.join(__dirname, filePath);
    if (fs.existsSync(fullPath)) {
      res.sendFile(fullPath);
    } else {
      res.sendFile(path.join(__dirname, 'index.html'));
    }
  });
}

// Fallback to index.html
app.use((req, res) => {
  res.sendFile(path.join(__dirname, 'index.html'));
});

app.listen(PORT, HOST, () => {
  console.log(`UFAvia server running at http://${HOST}:${PORT}`);
});
