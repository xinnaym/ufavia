<?php
/**
 * UFAvia — Подтверждение заказа и посадочный талон с QR-кодом
 * Практическая работа № 1
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/orders.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = $orderId > 0 ? getOrderById($orderId) : null;

// Если order_id не передан или не найден, возьмем последний заказ
if (!$order) {
    $db = getDb();
    $lastId = $db->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1")->fetchColumn();
    if ($lastId) {
        $order = getOrderById((int)$lastId);
    }
}

if (!$order) {
    header('Location: ../index.php');
    exit;
}

$firstItem = !empty($order['items']) ? $order['items'][0] : null;
$qrText = "UFAVIA-TICKET:" . $order['order_number'] . "|" . ($firstItem['flight_number'] ?? 'UF100') . "|" . $order['passenger_name'] . "|SEAT:" . ($firstItem['seat_number'] ?? '2B');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UFAvia — Заказ оформлен: #<?= htmlspecialchars($order['order_number']) ?></title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/checkout.css">
  <style>
    @media print {
      .site-header, .site-footer, .action-buttons-wrap, .btn-print-hide {
        display: none !important;
      }
      body {
        background: #fff;
      }
      .confirm-wrap {
        box-shadow: none;
        border: none;
        padding: 0;
      }
    }
    .qr-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #fff;
      padding: 16px;
      border: 1px dashed #cbd5e1;
      border-radius: 8px;
      margin-top: 18px;
    }
    .barcode-svg {
      width: 100%;
      max-width: 280px;
      height: 60px;
    }
  </style>
</head>

<body class="page-checkout">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main>
    <div class="container" style="display: flex; justify-content: center;">
      <div class="confirm-wrap" style="margin: 0 auto;">

        <!-- Иконка успешного заказа -->
        <div class="success-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>

        <!-- Заголовок -->
        <h1 class="confirm-title">Заказ успешно оплачен и оформлен!</h1>
        <p class="confirm-order-num">
          Номер заказа: <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
        </p>

        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 8px; padding: 12px 18px; margin: 16px 0 24px; text-align: center; font-size: 14px;">
          ✓ Оплата успешно подтверждена. Электронный билет направлен на <strong><?= htmlspecialchars($order['passenger_email']) ?></strong> и сохранен в вашем Личном кабинете.
        </div>

        <!-- ══════════════════════════════════
             ЭЛЕКТРОННЫЙ БИЛЕТ (BOARDING PASS)
        ══════════════════════════════════ -->
        <div class="ticket-block" aria-label="Электронный билет">
          <div class="ticket-heading" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Электронный билет / Boarding Pass</span>
            <span style="font-size: 12px; font-weight: 400; opacity: 0.9;">UFAvia E-Ticket</span>
          </div>

          <div class="ticket-rows">
            <div class="ticket-row">
              <span class="ticket-label">Пассажир:</span>
              <span class="ticket-value"><strong><?= htmlspecialchars($order['passenger_name']) ?></strong></span>
            </div>

            <?php if (!empty($order['passenger_passport'])): ?>
              <div class="ticket-row">
                <span class="ticket-label">Документ:</span>
                <span class="ticket-value"><?= htmlspecialchars($order['passenger_passport']) ?></span>
              </div>
            <?php endif; ?>

            <?php if ($firstItem): ?>
              <div class="ticket-row">
                <span class="ticket-label">Рейс:</span>
                <span class="ticket-value"><strong><?= htmlspecialchars($firstItem['flight_number']) ?></strong> (<?= htmlspecialchars($firstItem['airline']) ?>)</span>
              </div>
              <div class="ticket-row">
                <span class="ticket-label">Маршрут:</span>
                <span class="ticket-value"><?= htmlspecialchars($firstItem['origin']) ?> (<?= htmlspecialchars($firstItem['origin_code']) ?>) → <?= htmlspecialchars($firstItem['destination']) ?> (<?= htmlspecialchars($firstItem['destination_code']) ?>)</span>
              </div>
              <div class="ticket-row">
                <span class="ticket-label">Дата и вылет:</span>
                <span class="ticket-value"><?= date('d.m.Y', strtotime($firstItem['departure_date'])) ?> в <?= htmlspecialchars($firstItem['departure_time']) ?></span>
              </div>
              <div class="ticket-row">
                <span class="ticket-label">Прибытие:</span>
                <span class="ticket-value"><?= htmlspecialchars($firstItem['arrival_time']) ?></span>
              </div>
              <div class="ticket-row">
                <span class="ticket-label">Место в салоне:</span>
                <span class="ticket-value" style="color: var(--orange); font-weight: 700; font-size: 17px;"><?= htmlspecialchars($firstItem['seat_number']) ?></span>
              </div>
              <div class="ticket-row">
                <span class="ticket-label">Класс:</span>
                <span class="ticket-value"><?= $firstItem['cabin'] === 'business' ? 'Бизнес-класс' : 'Эконом-класс' ?></span>
              </div>
            <?php endif; ?>

            <div class="ticket-row">
              <span class="ticket-label">Способ получения:</span>
              <span class="ticket-value"><?= htmlspecialchars($order['delivery_method']) ?></span>
            </div>
            <div class="ticket-row">
              <span class="ticket-label">Оплата:</span>
              <span class="ticket-value"><?= htmlspecialchars($order['payment_method']) ?> (Оплачено)</span>
            </div>
            <div class="ticket-row">
              <span class="ticket-label">Сумма заказа:</span>
              <span class="ticket-value" style="font-weight: 800; color: var(--ink); font-size: 16px;"><?= number_format($order['total_amount'], 0, '', ' ') ?> ₽</span>
            </div>
          </div>

          <!-- Черный квадрат вместо QR-кода -->
          <div class="qr-box">
            <div style="width: 120px; height: 120px; background-color: #000000; margin: 0 auto; border-radius: 2px;" aria-label="Электронный код"></div>
            <div style="font-size: 11px; color: #64748b; margin-top: 8px; text-align: center; letter-spacing: 0.5px;">
              ЭЛЕКТРОННЫЙ КОД БИЛЕТА (UFA/SVO)<br>
              <strong><?= htmlspecialchars($order['order_number']) ?></strong>
            </div>
          </div>
        </div>

        <!-- Кнопки действий -->
        <div class="action-buttons-wrap" style="display: flex; gap: 14px; justify-content: center; margin-top: 28px; flex-wrap: wrap;">
          <a href="profile.php" class="button button-outline">
            Перейти в Личный кабинет
          </a>
          <a href="../index.php" class="button button-outline">
            На главную
          </a>
        </div>

      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
