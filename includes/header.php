<?php
/**
 * UFAvia — Общая шапка сайта
 * Семантическая верстка, адаптивное меню, счетчик корзины и статус пользователя
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

$currentUser = getCurrentUser();
$cartSummary = getCartSummary();
$cartCount = $cartSummary['count'];
$flash = getFlash();

// Определяем базовый путь в зависимости от текущего файла
$isPagesDir = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false);
$basePath = $isPagesDir ? '../' : './';
?>
<!-- ══════════════════════════════════
     HEADER
══════════════════════════════════ -->
<header class="site-header">
  <div class="header-inner">
    <a class="logo" href="<?= $basePath ?>index.php" aria-label="UFAvia — главная">
      <span class="logo-icon" aria-hidden="true">✈</span>
      <span>UFA<span class="logo-accent">via</span></span>
    </a>

    <nav class="main-nav" aria-label="Основная навигация">
      <a href="<?= $basePath ?>index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Главная</a>
      <a href="<?= $basePath ?>pages/search.php" class="<?= basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : '' ?>">Авиабилеты</a>
      <a href="<?= $basePath ?>index.php#destinations">Направления</a>
      <a href="<?= $basePath ?>index.php#about">О компании</a>
      <a href="<?= $basePath ?>index.php#contacts">Контакты</a>
      <?php if (isAdmin()): ?>
        <a href="<?= $basePath ?>pages/admin.php" class="admin-link-nav <?= basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : '' ?>" style="color: #ff6b2c; font-weight: 700;">Админ-панель</a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <a class="button button-outline cart-btn-with-badge" href="<?= $basePath ?>pages/cart.php" id="cart-button">
        Корзина
        <?php if ($cartCount > 0): ?>
          <span class="cart-badge" id="cart-badge-count"><?= $cartCount ?></span>
        <?php endif; ?>
      </a>

      <?php if ($currentUser): ?>
        <a class="button button-user-name" href="<?= $basePath ?>pages/profile.php">
          <span><?= htmlspecialchars($currentUser['name']) ?></span>
        </a>
        <a class="button button-outline button-logout" href="<?= $basePath ?>pages/logout.php" title="Выйти из аккаунта">
          Выйти
        </a>
      <?php else: ?>
        <button class="button" type="button" onclick="openAuthModal('login')">Войти</button>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($flash): ?>
  <div class="container flash-container" style="margin-top: 16px;">
    <div class="flash-alert flash-<?= htmlspecialchars($flash['type']) ?>" role="alert">
      <?= htmlspecialchars($flash['message']) ?>
      <button type="button" class="flash-close" onclick="this.parentElement.remove()">×</button>
    </div>
  </div>
<?php endif; ?>
