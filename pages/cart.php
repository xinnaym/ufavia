<?php
/**
 * UFAvia — Корзина билетов
 * Практическая работа № 1
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/flights.php';

// Обработка удаления билета из корзины
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove' && !empty($_POST['cart_id'])) {
        removeFromCart((int)$_POST['cart_id']);
        setFlash('success', 'Билет удален из корзины');
        header('Location: cart.php');
        exit;
    } elseif ($_POST['action'] === 'clear') {
        clearCart();
        setFlash('success', 'Корзина очищена');
        header('Location: cart.php');
        exit;
    }
}

$summary = getCartSummary();
$items = $summary['items'];
$currentUser = getCurrentUser();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UFAvia — Корзина билетов (<?= $summary['count'] ?>)</title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>

<body class="page-cart">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main>
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 24px;">
        <h1 class="page-title anim" style="margin: 0;">Корзина билетов</h1>
        <?php if (!empty($items)): ?>
          <form method="POST" action="cart.php" onsubmit="return confirm('Вы уверены, что хотите очистить корзину?');">
            <input type="hidden" name="action" value="clear">
            <button type="submit" style="background: none; border: none; color: #ef4444; font-size: 13px; cursor: pointer; text-decoration: underline;">
              Очистить корзину
            </button>
          </form>
        <?php endif; ?>
      </div>

      <div class="cart-grid">
        <!-- ── Левая колонка: Список билетов ── -->
        <div class="cart-items" id="cart-list">
          <?php if (!empty($items)): ?>
            <?php foreach ($items as $idx => $it): ?>
              <div class="ticket-card anim d<?= min($idx + 1, 3) ?>" data-price="<?= $it['item_price'] ?>">
                <div class="ticket-info">
                  <div class="ticket-route">
                    <?= htmlspecialchars($it['origin']) ?> → <?= htmlspecialchars($it['destination']) ?> · <?= htmlspecialchars($it['airline']) ?> (<?= htmlspecialchars($it['flight_number']) ?>)
                  </div>
                  <div class="ticket-details">
                    <?= date('d.m.Y', strtotime($it['departure_date'])) ?> · <?= htmlspecialchars($it['departure_time']) ?> – <?= htmlspecialchars($it['arrival_time']) ?> · Место <?= htmlspecialchars($it['seat_number']) ?> · <?= $it['cabin'] === 'business' ? 'Бизнес' : 'Эконом' ?>
                  </div>
                </div>

                <div class="ticket-action">
                  <div class="ticket-price"><?= number_format($it['item_price'], 0, '', ' ') ?> ₽</div>
                  <form method="POST" action="cart.php" style="margin: 0;">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="cart_id" value="<?= $it['cart_id'] ?>">
                    <button type="submit" class="btn-remove" onclick="return confirm('Удалить этот билет?');">
                      Удалить
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="background: #fff; padding: 48px; border-radius: 12px; text-align: center; border: 1px solid var(--line);">
              <div style="font-size: 48px; margin-bottom: 16px;">🛒</div>
              <h2 style="font-size: 20px; color: var(--ink); margin-bottom: 10px;">Ваша корзина пуста</h2>
              <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">
                Найдите интересующие вас рейсы и добавьте их в корзину для оформления билетов.
              </p>
              <a href="search.php" class="button" style="display: inline-block;">Найти авиабилеты</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- ── Правая колонка: Итог и Оформление ── -->
        <aside class="cart-summary anim d2">
          <div class="summary-card">
            <h2 class="summary-title">Итого к оплате</h2>

            <div class="summary-rows">
              <div class="summary-row">
                <span class="summary-label" id="tickets-label">Билеты (<?= $summary['count'] ?> шт.):</span>
                <span class="summary-val" id="tickets-val"><?= number_format($summary['subtotal'], 0, '', ' ') ?> ₽</span>
              </div>
              <div class="summary-row">
                <span class="summary-label">Сервисный сбор:</span>
                <span class="summary-val" id="fee-val"><?= number_format($summary['service_fee'], 0, '', ' ') ?> ₽</span>
              </div>
            </div>

            <div class="summary-total">
              <span class="summary-total-label">Итого:</span>
              <span class="summary-total-val" id="total-val"><?= number_format($summary['total'], 0, '', ' ') ?> ₽</span>
            </div>

            <?php if (!empty($items)): ?>
              <a href="order.php" class="btn-checkout" id="btn-checkout" style="display: block; text-align: center; text-decoration: none; box-sizing: border-box;">
                Перейти к оформлению
              </a>
            <?php else: ?>
              <button class="btn-checkout" id="btn-checkout" disabled style="opacity: 0.5; cursor: not-allowed; width: 100%;">
                Перейти к оформлению
              </button>
            <?php endif; ?>

            <div style="margin-top: 14px; font-size: 12px; color: #64748b; text-align: center;">
              Оплата банковской картой или СБП без дополнительной комиссии
            </div>
          </div>
        </aside>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../assets/js/cart.js"></script>
</body>
</html>
