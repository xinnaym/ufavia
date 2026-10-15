<?php
/**
 * UFAvia — Оформление заказа
 * Практическая работа № 1
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/orders.php';

$summary = getCartSummary();
$items = $summary['items'];

// Если корзина пуста, перенаправляем на страницу поиска
if (empty($items)) {
    setFlash('error', 'В корзине нет билетов для оформления');
    header('Location: cart.php');
    exit;
}

$currentUser = getCurrentUser();

// Обработка отправки формы оформления
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passengerData = [
        'user_id' => $currentUser ? $currentUser['id'] : null,
        'surname' => trim($_POST['surname'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'patronymic' => trim($_POST['patronymic'] ?? ''),
        'dob' => trim($_POST['dob'] ?? ''),
        'passport' => trim($_POST['passport'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'insurance' => !empty($_POST['insurance']),
        'transfer' => !empty($_POST['transfer']),
        'meal' => !empty($_POST['meal']),
        'sms' => !empty($_POST['sms']),
        'payment_method' => trim($_POST['payment_method'] ?? 'Банковская карта'),
        'delivery_method' => trim($_POST['delivery_method'] ?? 'Электронный билет')
    ];

    $res = createOrder($passengerData, $items);
    if ($res['success']) {
        header('Location: checkout.php?order_id=' . $res['order_id']);
        exit;
    } else {
        $error = $res['error'] ?? 'Произошла ошибка при сохранении заказа';
    }
}

// Заполняем имя по умолчанию
$defaultName = $currentUser ? $currentUser['name'] : 'Иван Иванов';
$nameParts = explode(' ', $defaultName, 3);
$defaultSurname = $nameParts[0] ?? 'Иванов';
$defaultFirstName = $nameParts[1] ?? 'Иван';
$defaultEmail = $currentUser ? $currentUser['email'] : 'ivan@example.com';
$defaultPhone = $currentUser ? $currentUser['phone'] : '+7 (917) 000-00-00';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UFAvia — Оформление заказа</title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/order.css">
  <style>
    .service-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border: 1px solid var(--line);
      border-radius: 8px;
      margin-bottom: 10px;
      background: #fff;
      cursor: pointer;
      transition: border-color 0.15s, background 0.15s;
    }
    .service-item:hover {
      border-color: var(--blue);
      background: #f8fafc;
    }
    .service-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .service-price {
      font-weight: 700;
      color: var(--ink);
    }
    .delivery-options {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 10px;
    }
    .delivery-card {
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 14px;
      cursor: pointer;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      background: #fff;
    }
    .delivery-card input {
      margin-top: 3px;
    }
  </style>
</head>

<body class="page-order">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main>
    <div class="container">
      <?php if (!empty($error)): ?>
        <div class="flash-alert flash-error" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form id="booking-form" method="POST" action="order.php">
        <div class="page-grid">

          <!-- ── Левая колонка ── -->
          <div class="left-col">

            <!-- 1. Подтверждение информации о заказе -->
            <section class="card anim" aria-label="Состав заказа">
              <h2 class="card-title">Состав заказа</h2>
              <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($items as $it): ?>
                  <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div>
                      <div style="font-weight: 700; color: var(--blue); font-size: 15px;">
                        <?= htmlspecialchars($it['origin']) ?> → <?= htmlspecialchars($it['destination']) ?> (<?= htmlspecialchars($it['flight_number']) ?>)
                      </div>
                      <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
                        <?= htmlspecialchars($it['airline']) ?> · <?= date('d.m.Y', strtotime($it['departure_date'])) ?> в <?= htmlspecialchars($it['departure_time']) ?> · Место <?= htmlspecialchars($it['seat_number']) ?>
                      </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: var(--ink);">
                      <?= number_format($it['item_price'], 0, '', ' ') ?> ₽
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <!-- 2. Данные пассажира -->
            <section class="card anim d1" aria-label="Данные пассажира">
              <h2 class="card-title">Личные данные пассажира</h2>
              <div class="form-row">
                <div class="field">
                  <label for="f-surname">Фамилия *</label>
                  <input id="f-surname" name="surname" type="text" value="<?= htmlspecialchars($defaultSurname) ?>" required autocomplete="family-name" />
                </div>
                <div class="field">
                  <label for="f-name">Имя *</label>
                  <input id="f-name" name="name" type="text" value="<?= htmlspecialchars($defaultFirstName) ?>" required autocomplete="given-name" />
                </div>
                <div class="field">
                  <label for="f-patronymic">Отчество</label>
                  <input id="f-patronymic" name="patronymic" type="text" value="Иванович" autocomplete="additional-name" />
                </div>
                <div class="field">
                  <label for="f-dob">Дата рождения *</label>
                  <input id="f-dob" name="dob" type="date" value="1995-04-12" required autocomplete="bday" />
                </div>
                <div class="field">
                  <label for="f-passport">Паспорт (серия и номер) *</label>
                  <input id="f-passport" name="passport" type="text" value="8014 123456" required placeholder="0000 000000" />
                </div>
                <div class="field">
                  <label for="f-gender">Пол</label>
                  <select id="f-gender" name="gender" style="height: 42px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff;">
                    <option value="M" selected>Мужской</option>
                    <option value="F">Женский</option>
                  </select>
                </div>
              </div>
            </section>

            <!-- 3. Контактная информация -->
            <section class="card anim d1" aria-label="Контактная информация">
              <h2 class="card-title">Контактная информация</h2>
              <div class="form-row">
                <div class="field">
                  <label for="f-email">Email для получения билетов *</label>
                  <input id="f-email" name="email" type="email" value="<?= htmlspecialchars($defaultEmail) ?>" required autocomplete="email" />
                </div>
                <div class="field">
                  <label for="f-phone">Контактный телефон *</label>
                  <input id="f-phone" name="phone" type="tel" value="<?= htmlspecialchars($defaultPhone) ?>" required autocomplete="tel" />
                </div>
              </div>
            </section>

            <!-- 4. Дополнительные услуги (Страховка, Трансфер, Питание, SMS) -->
            <section class="card anim d2" aria-label="Дополнительные услуги">
              <h2 class="card-title">Дополнительные услуги</h2>
              <p style="font-size: 13px; color: #64748b; margin-bottom: 14px;">
                Сделайте ваше путешествие комфортным и защищённым:
              </p>

              <label class="service-item">
                <div class="service-info">
                  <input type="checkbox" name="insurance" id="srv-insurance" value="1" onchange="calcTotal()">
                  <div>
                    <div style="font-weight: 600;">Медицинская страховка и страхование багажа</div>
                    <div style="font-size: 12px; color: #64748b;">Покрытие до 2 000 000 ₽ при задержке рейса или утере багажа</div>
                  </div>
                </div>
                <div class="service-price">+450 ₽</div>
              </label>

              <label class="service-item">
                <div class="service-info">
                  <input type="checkbox" name="transfer" id="srv-transfer" value="1" onchange="calcTotal()">
                  <div>
                    <div style="font-weight: 600;">Индивидуальный трансфер в аэропорт</div>
                    <div style="font-size: 12px; color: #64748b;">Комфорт-класс, подача машины прямо к вашему дому вовремя</div>
                  </div>
                </div>
                <div class="service-price">+1 200 ₽</div>
              </label>

              <label class="service-item">
                <div class="service-info">
                  <input type="checkbox" name="meal" id="srv-meal" value="1" onchange="calcTotal()">
                  <div>
                    <div style="font-weight: 600;">Горячее бортовое питание (на выбор)</div>
                    <div style="font-size: 12px; color: #64748b;">Мясное, рыбное или вегетарианское меню от шеф-повара</div>
                  </div>
                </div>
                <div class="service-price">+350 ₽</div>
              </label>

              <label class="service-item">
                <div class="service-info">
                  <input type="checkbox" name="sms" id="srv-sms" value="1" onchange="calcTotal()">
                  <div>
                    <div style="font-weight: 600;">SMS-уведомления об изменении статуса рейса</div>
                    <div style="font-size: 12px; color: #64748b;">Моментальные оповещения о начале посадки, выходе и багаже</div>
                  </div>
                </div>
                <div class="service-price">+99 ₽</div>
              </label>
            </section>

            <!-- 5. Способ доставки билетов -->
            <section class="card anim d2" aria-label="Способ получения билетов">
              <h2 class="card-title">Способ получения билетов</h2>
              <div class="delivery-options">
                <label class="delivery-card">
                  <input type="radio" name="delivery_method" value="Электронный билет" checked>
                  <div>
                    <strong style="display: block; font-size: 14px;">Электронный билет (Рекомендуется)</strong>
                    <span style="font-size: 12px; color: #64748b;">Мгновенная отправка на email, в Личный кабинет и загрузка PDF с QR-кодом</span>
                  </div>
                </label>
                <label class="delivery-card">
                  <input type="radio" name="delivery_method" value="Бумажный билет в кассе">
                  <div>
                    <strong style="display: block; font-size: 14px;">Бумажный билет</strong>
                    <span style="font-size: 12px; color: #64748b;">Получение в кассе аэропорта перед вылетом по паспорту</span>
                  </div>
                </label>
              </div>
            </section>

            <!-- 6. Способ оплаты -->
            <section class="card anim d2" aria-label="Способ оплаты">
              <h2 class="card-title">Способ оплаты</h2>
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 10px;">
                <label class="delivery-card">
                  <input type="radio" name="payment_method" value="Банковская карта" checked>
                  <div>
                    <strong style="font-size: 14px;">Банковская карта</strong>
                    <span style="font-size: 11px; color: #64748b; display: block;">МИР, Visa, Mastercard</span>
                  </div>
                </label>
                <label class="delivery-card">
                  <input type="radio" name="payment_method" value="СБП (Система быстрых платежей)">
                  <div>
                    <strong style="font-size: 14px;">СБП</strong>
                    <span style="font-size: 11px; color: #64748b; display: block;">Оплата по QR-коду 0%</span>
                  </div>
                </label>
                <label class="delivery-card">
                  <input type="radio" name="payment_method" value="Mir Pay">
                  <div>
                    <strong style="font-size: 14px;">Mir Pay</strong>
                    <span style="font-size: 11px; color: #64748b; display: block;">В одно касание</span>
                  </div>
                </label>
                <label class="delivery-card">
                  <input type="radio" name="payment_method" value="ЮMoney">
                  <div>
                    <strong style="font-size: 14px;">ЮMoney</strong>
                    <span style="font-size: 11px; color: #64748b; display: block;">Электронный кошелек</span>
                  </div>
                </label>
              </div>
            </section>

          </div><!-- /left-col -->

          <!-- ── Правая колонка: Сводка заказа ── -->
          <aside class="right-col">
            <section class="card anim d2" aria-label="Сводка стоимости">
              <h2 class="card-title">Итог заказа</h2>

              <div class="order-rows">
                <div class="order-row">
                  <span class="order-label">Билеты (<?= count($items) ?> шт.):</span>
                  <span class="order-value"><?= number_format($summary['subtotal'], 0, '', ' ') ?> ₽</span>
                </div>
                <div class="order-row">
                  <span class="order-label">Сервисный сбор:</span>
                  <span class="order-value"><?= number_format($summary['service_fee'], 0, '', ' ') ?> ₽</span>
                </div>
                <div class="order-row" id="row-insurance" style="display: none;">
                  <span class="order-label">Страховка:</span>
                  <span class="order-value">450 ₽</span>
                </div>
                <div class="order-row" id="row-transfer" style="display: none;">
                  <span class="order-label">Трансфер:</span>
                  <span class="order-value">1 200 ₽</span>
                </div>
                <div class="order-row" id="row-meal" style="display: none;">
                  <span class="order-label">Питание:</span>
                  <span class="order-value">350 ₽</span>
                </div>
                <div class="order-row" id="row-sms" style="display: none;">
                  <span class="order-label">SMS-оповещения:</span>
                  <span class="order-value">99 ₽</span>
                </div>
              </div>

              <div class="order-total">
                <span class="order-total-label">К оплате:</span>
                <span class="order-total-value" id="grand-total-val"><?= number_format($summary['total'], 0, '', ' ') ?> ₽</span>
              </div>

              <button type="submit" class="btn-buy" style="width: 100%; border: none; cursor: pointer; margin-top: 10px;">
                Подтвердить и оплатить
              </button>

              <div style="font-size: 12px; color: #64748b; margin-top: 14px; text-align: center; line-height: 1.4;">
                Нажимая «Подтвердить и оплатить», вы подтверждаете согласие с правилами бронирования и обработки данных
              </div>
            </section>
          </aside>

        </div><!-- /page-grid -->
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
    const baseTotal = <?= $summary['total'] ?>;
    
    function calcTotal() {
      let extra = 0;
      
      const ins = document.getElementById('srv-insurance').checked;
      document.getElementById('row-insurance').style.display = ins ? 'flex' : 'none';
      if (ins) extra += 450;

      const tr = document.getElementById('srv-transfer').checked;
      document.getElementById('row-transfer').style.display = tr ? 'flex' : 'none';
      if (tr) extra += 1200;

      const meal = document.getElementById('srv-meal').checked;
      document.getElementById('row-meal').style.display = meal ? 'flex' : 'none';
      if (meal) extra += 350;

      const sms = document.getElementById('srv-sms').checked;
      document.getElementById('row-sms').style.display = sms ? 'flex' : 'none';
      if (sms) extra += 99;

      const sum = baseTotal + extra;
      document.getElementById('grand-total-val').textContent = sum.toLocaleString('ru-RU') + ' ₽';
    }
  </script>
</body>
</html>
