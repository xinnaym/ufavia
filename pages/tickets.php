<?php
/**
 * UFAvia — Страница выбора и бронирования авиабилетов
 * Детальная информация о рейсе, интерактивная схема салона, расчет цены, добавление в корзину
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flights.php';
require_once __DIR__ . '/../includes/cart.php';

$flightId = isset($_GET['flight_id']) ? (int)$_GET['flight_id'] : 1;
$flight = getFlightById($flightId);

if (!$flight) {
    // Если рейс не найден, берем первый доступный
    $all = getFlights();
    $flight = !empty($all) ? $all[0] : null;
}

if (!$flight) {
    header('Location: search.php');
    exit;
}

// Обработка добавления в корзину
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $seat = trim($_POST['seat_number'] ?? '2B');
    $price = (int)($_POST['total_price'] ?? $flight['price']);
    addToCart($flight['id'], $seat, $price);
    header('Location: cart.php');
    exit;
}

$basePrice = $flight['price'];
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
        <a href="search.php?origin=<?= urlencode($flight['origin']) ?>&destination=<?= urlencode($flight['destination']) ?>" style="color: var(--blue); font-size: 14px; text-decoration: underline;">
          ← Назад к списку рейсов
        </a>
      </div>

      <div class="content-grid">
        <!-- ── Левая колонка: Детали рейса и Схема мест ── -->
        <div class="left-col">
          <!-- Информация о рейсе -->
          <section class="card anim" aria-label="Информация о рейсе">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 16px;">
              <h2 class="card-title" style="margin: 0;">Информация о рейсе</h2>
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
          <section class="card anim d1" aria-label="Выбор места в самолёте">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
              <h2 class="card-title" style="margin: 0;">Выбор места в самолёте</h2>
              <span id="selected-seat-badge" style="font-size: 14px; font-weight: 700; color: var(--orange); background: #fff7ed; padding: 4px 10px; border-radius: 4px; border: 1px solid #ffedd5;">
                Выбрано место: 2B
              </span>
            </div>

            <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
              Выберите любое свободное место на интерактивной схеме лайнера Boeing 737-800.
            </p>

            <div class="seat-map-bg" role="group" aria-label="Схема салона самолёта">
              <!-- Ряд 1 -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="1A" data-extra="500" aria-label="Место 1A, свободно">1A</button>
                <button type="button" class="seat seat--free" data-seat="1B" data-extra="500" aria-label="Место 1B, свободно">1B</button>
                <button type="button" class="seat seat--occupied" data-seat="1C" aria-label="Место 1C, занято" disabled>1C</button>
                <button type="button" class="seat seat--occupied" data-seat="1D" aria-label="Место 1D, занято" disabled>1D</button>
                <button type="button" class="seat seat--free" data-seat="1E" data-extra="500" aria-label="Место 1E, свободно">1E</button>
                <button type="button" class="seat seat--free" data-seat="1F" data-extra="500" aria-label="Место 1F, свободно">1F</button>
              </div>
              <!-- Ряд 2 -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="2A" data-extra="300" aria-label="Место 2A, свободно">2A</button>
                <button type="button" class="seat seat--selected" data-seat="2B" data-extra="300" aria-label="Место 2B, выбрано" aria-pressed="true">2B</button>
                <button type="button" class="seat seat--free" data-seat="2C" data-extra="300" aria-label="Место 2C, свободно">2C</button>
                <button type="button" class="seat seat--free" data-seat="2D" data-extra="300" aria-label="Место 2D, свободно">2D</button>
                <button type="button" class="seat seat--occupied" data-seat="2E" aria-label="Место 2E, занято" disabled>2E</button>
                <button type="button" class="seat seat--free" data-seat="2F" data-extra="300" aria-label="Место 2F, свободно">2F</button>
              </div>
              <!-- Ряд 3 -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="3A" data-extra="300" aria-label="Место 3A, свободно">3A</button>
                <button type="button" class="seat seat--free" data-seat="3B" data-extra="300" aria-label="Место 3B, свободно">3B</button>
                <button type="button" class="seat seat--free" data-seat="3C" data-extra="300" aria-label="Место 3C, свободно">3C</button>
                <button type="button" class="seat seat--occupied" data-seat="3D" aria-label="Место 3D, занято" disabled>3D</button>
                <button type="button" class="seat seat--free" data-seat="3E" data-extra="300" aria-label="Место 3E, свободно">3E</button>
                <button type="button" class="seat seat--free" data-seat="3F" data-extra="300" aria-label="Место 3F, свободно">3F</button>
              </div>
              <!-- Ряд 4 -->
              <div class="seat-row">
                <button type="button" class="seat seat--free" data-seat="4A" data-extra="0" aria-label="Место 4A, свободно">4A</button>
                <button type="button" class="seat seat--free" data-seat="4B" data-extra="0" aria-label="Место 4B, свободно">4B</button>
                <button type="button" class="seat seat--free" data-seat="4C" data-extra="0" aria-label="Место 4C, свободно">4C</button>
                <button type="button" class="seat seat--free" data-seat="4D" data-extra="0" aria-label="Место 4D, свободно">4D</button>
                <button type="button" class="seat seat--free" data-seat="4E" data-extra="0" aria-label="Место 4E, свободно">4E</button>
                <button type="button" class="seat seat--occupied" data-seat="4F" aria-label="Место 4F, занято" disabled>4F</button>
              </div>

              <!-- Легенда обозначений -->
              <div class="legend" aria-label="Условные обозначения">
                <div class="legend-item">
                  <div class="legend-dot legend-dot--free" aria-hidden="true"></div>
                  Свободно
                </div>
                <div class="legend-item">
                  <div class="legend-dot legend-dot--selected" aria-hidden="true"></div>
                  Выбрано
                </div>
                <div class="legend-item">
                  <div class="legend-dot legend-dot--occupied" aria-hidden="true"></div>
                  Занято
                </div>
              </div>
            </div>
          </section>

          <!-- Договор оферты -->
          <section class="card anim d2" style="font-size: 13px; color: #64748b; line-height: 1.6;">
            <h3 style="font-size: 15px; color: var(--ink); margin-bottom: 8px;">Договор публичной оферты о купле-продаже авиабилетов</h3>
            <p>
              Нажимая кнопку «Купить авиабилет», вы подтверждаете согласие с условиями публичной оферты перевозчика <?= htmlspecialchars($flight['airline']) ?>, правилами применения тарифа, политикой обработки персональных данных и условиями возврата.
            </p>
          </section>
        </div>

        <!-- ── Правая колонка: Калькулятор заказа ── -->
        <aside class="right-col">
          <section class="card anim d2" aria-label="Ваш заказ">
            <h2 class="card-title">Ваш заказ</h2>

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

            <div class="order-total">
              <span class="order-total-label">Итого:</span>
              <span class="order-total-value" id="price-total"><?= number_format($basePrice + 300 + $serviceFee, 0, '', ' ') ?> ₽</span>
            </div>

            <!-- Форма отправки в корзину -->
            <form method="POST" action="tickets.php?flight_id=<?= $flight['id'] ?>">
              <input type="hidden" name="action" value="add_to_cart">
              <input type="hidden" name="seat_number" id="form-seat-number" value="2B">
              <input type="hidden" name="total_price" id="form-total-price" value="<?= $basePrice + 300 ?>">
              <button type="submit" class="btn-buy" id="btn-buy" style="cursor: pointer; width: 100%;">
                Купить авиабилет
              </button>
            </form>

            <div style="margin-top: 14px; text-align: center; font-size: 12px; color: #64748b;">
              Билет будет добавлен в корзину для подтверждения и оплаты
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
    let selectedSeat = '2B';
    let seatFee = 300;

    const seatButtons = document.querySelectorAll('.seat--free, .seat--selected');
    const badge = document.getElementById('selected-seat-badge');
    const lblSeatCode = document.getElementById('lbl-seat-code');
    const priceSeatEl = document.getElementById('price-seat');
    const priceTotalEl = document.getElementById('price-total');
    const formSeat = document.getElementById('form-seat-number');
    const formPrice = document.getElementById('form-total-price');

    seatButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        // Снимаем выбор со всех
        document.querySelectorAll('.seat--selected').forEach(s => {
          s.classList.replace('seat--selected', 'seat--free');
          s.setAttribute('aria-pressed', 'false');
        });

        // Выбираем нажатое
        btn.classList.replace('seat--free', 'seat--selected');
        btn.setAttribute('aria-pressed', 'true');
        
        selectedSeat = btn.dataset.seat;
        seatFee = parseInt(btn.dataset.extra || 0, 10);

        badge.textContent = 'Выбрано место: ' + selectedSeat;
        lblSeatCode.textContent = selectedSeat;
        priceSeatEl.textContent = seatFee.toLocaleString('ru-RU') + ' ₽';

        const totalWithSeat = basePrice + seatFee;
        const grandTotal = totalWithSeat + serviceFee;
        priceTotalEl.textContent = grandTotal.toLocaleString('ru-RU') + ' ₽';

        formSeat.value = selectedSeat;
        formPrice.value = totalWithSeat;
      });
    });
  </script>
</body>
</html>
