<?php
/**
 * UFAvia — Страница выбора и бронирования авиабилетов
 * Детальная информация о рейсе, интерактивная схема салона, расчет цены, добавление в корзину
 * Практическая работа № 1
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flights.php';
require_once __DIR__ . '/../includes/cart.php';

$flightId = isset($_REQUEST['flight_id']) ? (int)$_REQUEST['flight_id'] : 1;
$flight = getFlightById($flightId);

if (!$flight) {
    $all = getFlights();
    $flight = !empty($all) ? $all[0] : null;
}

// Если в базе вообще нет рейсов, создаем дефолтный рейс, чтобы страница всегда работала
if (!$flight) {
    $flight = [
        'id' => 1,
        'flight_number' => 'SU-1234',
        'airline' => 'Аэрофлот',
        'airline_code' => 'SU',
        'origin' => 'Уфа',
        'origin_code' => 'UFA',
        'destination' => 'Москва',
        'destination_code' => 'SVO',
        'departure_date' => date('Y-m-d'),
        'departure_time' => '08:20',
        'arrival_time' => '09:35',
        'duration' => 135,
        'price' => 5400,
        'seats_total' => 180,
        'seats_available' => 38,
        'cabin' => 'economy',
        'stops' => 0
    ];
}

// Обработка добавления билета в корзину (работает и через POST, и через GET)
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'add_to_cart') {
    $seat = trim($_REQUEST['seat_number'] ?? '2B');
    $price = (int)($_REQUEST['total_price'] ?? ($flight['price'] + 300));
    addToCart((int)$flight['id'], $seat, $price);
    header('Location: cart.php');
    exit;
}

$basePrice = (int)$flight['price'];
$serviceFee = 150;
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UFAvia — Выбор места: <?= htmlspecialchars($flight['origin']) ?> → <?= htmlspecialchars($flight['destination']) ?></title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/tickets.css">
</head>

<body class="page-tickets">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main>
    <div class="container">
      <div style="margin-bottom: 20px;">
        <a href="search.php" style="color: var(--blue); font-size: 14px; text-decoration: underline;">
          ← Назад к списку всех рейсов
        </a>
      </div>

      <div class="content-grid">
        <!-- ── Левая колонка: Детали рейса и Схема мест ── -->
        <div class="left-col">
          <!-- Информация о рейсе -->
          <section class="card" aria-label="Информация о рейсе">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px;">
              <h2 class="card-title" style="margin: 0; font-size: 22px;">Информация о рейсе</h2>
              <span style="font-weight: 700; color: var(--blue); background: #e0f2fe; padding: 4px 10px; border-radius: 4px; font-size: 13px;">
                <?= htmlspecialchars($flight['flight_number']) ?>
              </span>
            </div>

            <div class="flight-table" role="list">
              <div class="flight-row" role="listitem">
                <span class="flight-label">Авиакомпания:</span>
                <span class="flight-value"><strong><?= htmlspecialchars($flight['airline']) ?></strong></span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Маршрут:</span>
                <span class="flight-value"><?= htmlspecialchars($flight['origin']) ?> (<?= htmlspecialchars($flight['origin_code']) ?>) → <?= htmlspecialchars($flight['destination']) ?> (<?= htmlspecialchars($flight['destination_code']) ?>)</span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Дата вылета:</span>
                <span class="flight-value"><?= date('d.m.Y', strtotime($flight['departure_date'])) ?></span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Время вылета:</span>
                <span class="flight-value"><?= htmlspecialchars($flight['departure_time']) ?> (местное время)</span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Время прилёта:</span>
                <span class="flight-value"><?= htmlspecialchars($flight['arrival_time']) ?></span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Длительность:</span>
                <span class="flight-value"><?= floor($flight['duration'] / 60) ?> ч <?= ($flight['duration'] % 60) ?> мин</span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Класс обслуживания:</span>
                <span class="flight-value"><?= $flight['cabin'] === 'business' ? 'Бизнес-класс' : 'Эконом-класс' ?></span>
              </div>
              <div class="flight-row" role="listitem">
                <span class="flight-label">Свободных мест:</span>
                <span class="flight-value" style="color: #16a34a; font-weight: 700;"><?= $flight['seats_available'] ?> из <?= $flight['seats_total'] ?></span>
              </div>
            </div>
          </section>

          <!-- Выбор места в самолёте -->
          <section class="card" aria-label="Выбор места в самолёте">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
              <h2 class="card-title" style="margin: 0; font-size: 22px;">Выбор места в салоне</h2>
              <span id="selected-seat-badge" style="font-size: 14px; font-weight: 700; color: var(--orange); background: #fff7ed; padding: 4px 12px; border-radius: 4px; border: 1px solid #ffedd5;">
                Выбрано место: 2B
              </span>
            </div>

            <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
              Нажмите на любое свободное место на интерактивной схеме лайнера (Ряды 1–4):
            </p>

            <div class="seat-map-bg" role="group" aria-label="Схема салона самолёта">
              <!-- Ряд 1 (Комфорт +500 ₽) -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="1A" data-extra="500" onclick="selectSeat('1A', 500)">1A</button>
                <button type="button" class="seat seat--free" data-seat="1B" data-extra="500" onclick="selectSeat('1B', 500)">1B</button>
                <button type="button" class="seat seat--occupied" data-seat="1C" disabled>1C</button>
                <button type="button" class="seat seat--occupied" data-seat="1D" disabled>1D</button>
                <button type="button" class="seat seat--free" data-seat="1E" data-extra="500" onclick="selectSeat('1E', 500)">1E</button>
                <button type="button" class="seat seat--free" data-seat="1F" data-extra="500" onclick="selectSeat('1F', 500)">1F</button>
              </div>
              <!-- Ряд 2 (Стандарт +300 ₽) -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="2A" data-extra="300" onclick="selectSeat('2A', 300)">2A</button>
                <button type="button" class="seat seat--selected" data-seat="2B" data-extra="300" onclick="selectSeat('2B', 300)">2B</button>
                <button type="button" class="seat seat--free" data-seat="2C" data-extra="300" onclick="selectSeat('2C', 300)">2C</button>
                <button type="button" class="seat seat--free" data-seat="2D" data-extra="300" onclick="selectSeat('2D', 300)">2D</button>
                <button type="button" class="seat seat--occupied" data-seat="2E" disabled>2E</button>
                <button type="button" class="seat seat--free" data-seat="2F" data-extra="300" onclick="selectSeat('2F', 300)">2F</button>
              </div>
              <!-- Ряд 3 (Стандарт +300 ₽) -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="3A" data-extra="300" onclick="selectSeat('3A', 300)">3A</button>
                <button type="button" class="seat seat--free" data-seat="3B" data-extra="300" onclick="selectSeat('3B', 300)">3B</button>
                <button type="button" class="seat seat--free" data-seat="3C" data-extra="300" onclick="selectSeat('3C', 300)">3C</button>
                <button type="button" class="seat seat--occupied" data-seat="3D" disabled>3D</button>
                <button type="button" class="seat seat--free" data-seat="3E" data-extra="300" onclick="selectSeat('3E', 300)">3E</button>
                <button type="button" class="seat seat--free" data-seat="3F" data-extra="300" onclick="selectSeat('3F', 300)">3F</button>
              </div>
              <!-- Ряд 4 (Базовые места 0 ₽) -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="4A" data-extra="0" onclick="selectSeat('4A', 0)">4A</button>
                <button type="button" class="seat seat--free" data-seat="4B" data-extra="0" onclick="selectSeat('4B', 0)">4B</button>
                <button type="button" class="seat seat--free" data-seat="4C" data-extra="0" onclick="selectSeat('4C', 0)">4C</button>
                <button type="button" class="seat seat--free" data-seat="4D" data-extra="0" onclick="selectSeat('4D', 0)">4D</button>
                <button type="button" class="seat seat--free" data-seat="4E" data-extra="0" onclick="selectSeat('4E', 0)">4E</button>
                <button type="button" class="seat seat--occupied" data-seat="4F" disabled>4F</button>
              </div>

              <!-- Легенда -->
              <div class="legend" style="display: flex; gap: 20px; justify-content: center; margin-top: 20px; font-size: 13px;">
                <div class="legend-item" style="display: flex; align-items: center; gap: 6px;">
                  <span class="seat seat--free" style="width: 22px; height: 22px; font-size: 10px;"></span>
                  Свободно
                </div>
                <div class="legend-item" style="display: flex; align-items: center; gap: 6px;">
                  <span class="seat seat--selected" style="width: 22px; height: 22px; font-size: 10px;"></span>
                  Выбрано
                </div>
                <div class="legend-item" style="display: flex; align-items: center; gap: 6px;">
                  <span class="seat seat--occupied" style="width: 22px; height: 22px; font-size: 10px;"></span>
                  Занято
                </div>
              </div>
            </div>
          </section>

          <!-- Договор оферты -->
          <section class="card" style="font-size: 13px; color: #64748b; line-height: 1.6;">
            <h3 style="font-size: 15px; color: var(--ink); margin-bottom: 8px;">Договор публичной оферты о купле-продаже авиабилетов</h3>
            <p>
              Нажимая кнопку «Купить авиабилет», вы подтверждаете согласие с условиями публичной оферты перевозчика <?= htmlspecialchars($flight['airline']) ?>, правилами применения тарифа, политикой обработки персональных данных и условиями возврата билета.
            </p>
          </section>
        </div>

        <!-- ── Правая колонка: Калькулятор заказа ── -->
        <aside class="right-col">
          <section class="card" aria-label="Ваш заказ">
            <h2 class="card-title" style="font-size: 20px; margin-bottom: 16px;">Ваш заказ</h2>

            <div class="order-rows">
              <div class="order-row">
                <span class="order-label">Билет (<?= htmlspecialchars($flight['airline_code']) ?>):</span>
                <span class="order-value" id="price-ticket"><?= number_format($basePrice, 0, '', ' ') ?> ₽</span>
              </div>
              <div class="order-row">
                <span class="order-label">Выбор места (<span id="lbl-seat-code">2B</span>):</span>
                <span class="order-value" id="price-seat">300 ₽</span>
              </div>
              <div class="order-row">
                <span class="order-label">Сервисный сбор:</span>
                <span class="order-value" id="price-service"><?= number_format($serviceFee, 0, '', ' ') ?> ₽</span>
              </div>
            </div>

            <div class="order-total" style="border-top: 1px solid var(--line); padding-top: 14px; margin-top: 14px; display: flex; justify-content: space-between; font-size: 18px; font-weight: 700;">
              <span>Итого:</span>
              <span id="price-total" style="color: var(--orange);"><?= number_format($basePrice + 300 + $serviceFee, 0, '', ' ') ?> ₽</span>
            </div>

            <!-- Форма отправки в корзину -->
            <form method="POST" action="tickets.php?flight_id=<?= $flight['id'] ?>" style="margin-top: 20px;">
              <input type="hidden" name="action" value="add_to_cart">
              <input type="hidden" name="flight_id" value="<?= $flight['id'] ?>">
              <input type="hidden" name="seat_number" id="form-seat-number" value="2B">
              <input type="hidden" name="total_price" id="form-total-price" value="<?= $basePrice + 300 ?>">
              <button type="submit" class="button button-orange" id="btn-buy" style="cursor: pointer; width: 100%; height: 48px; font-size: 16px; font-weight: 700; border-radius: 8px;">
                Купить авиабилет
              </button>
            </form>

            <div style="margin-top: 14px; text-align: center; font-size: 12px; color: #64748b;">
              Билет будет добавлен в корзину для оформления договора
            </div>
          </section>
        </aside>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
    const basePrice = <?= $basePrice ?>;
    const serviceFee = <?= $serviceFee ?>;

    function selectSeat(seatCode, extra) {
      const extraPrice = parseInt(extra, 10) || 0;

      // Снимаем выбор со всех кресел
      document.querySelectorAll('.seat').forEach(btn => {
        if (btn.classList.contains('seat--occupied')) return;
        if (btn.getAttribute('data-seat') === seatCode) {
          btn.classList.remove('seat--free');
          btn.classList.add('seat--selected');
        } else {
          btn.classList.remove('seat--selected');
          btn.classList.add('seat--free');
        }
      });

      const badge = document.getElementById('selected-seat-badge');
      const lblSeat = document.getElementById('lbl-seat-code');
      const priceSeatEl = document.getElementById('price-seat');
      const priceTotalEl = document.getElementById('price-total');
      const formSeat = document.getElementById('form-seat-number');
      const formPrice = document.getElementById('form-total-price');

      if (badge) badge.textContent = 'Выбрано место: ' + seatCode;
      if (lblSeat) lblSeat.textContent = seatCode;
      if (priceSeatEl) priceSeatEl.textContent = extraPrice.toLocaleString('ru-RU') + ' ₽';

      const totalWithSeat = basePrice + extraPrice;
      const grandTotal = totalWithSeat + serviceFee;
      if (priceTotalEl) priceTotalEl.textContent = grandTotal.toLocaleString('ru-RU') + ' ₽';

      if (formSeat) formSeat.value = seatCode;
      if (formPrice) formPrice.value = totalWithSeat;
    }
  </script>
</body>
</html>
