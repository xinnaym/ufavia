<?php
/**
 * UFAvia — Результаты поиска авиабилетов
 * Фильтрация, сортировка, количество свободных мест, правила бронирования
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flights.php';
require_once __DIR__ . '/../includes/cart.php';

$origin = $_GET['origin'] ?? 'Уфа';
$destination = $_GET['destination'] ?? 'Москва';
$departureDate = $_GET['departure'] ?? '';
$cabin = $_GET['cabin'] ?? 'any';
$stops = $_GET['stops'] ?? 'any';
$priceRange = $_GET['price'] ?? '';
$sort = $_GET['sort'] ?? 'price';

$filters = [
    'origin' => $origin,
    'destination' => $destination,
    'cabin' => $cabin,
    'stops' => $stops,
    'price_range' => $priceRange,
    'sort' => $sort
];

// Если искали по дате, но ничего не нашлось, покажем все рейсы на это направление
$flights = getFlights($filters);
if (empty($flights) && (!empty($origin) || !empty($destination))) {
    // Мягкий поиск
    $flights = getFlights(['origin' => $origin, 'sort' => $sort]);
}
if (empty($flights)) {
    // Все рейсы
    $flights = getFlights(['sort' => $sort]);
}

$cartItems = getCartItems();
$inCartFlightIds = array_column($cartItems, 'id');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>UFAvia — <?= htmlspecialchars($origin) ?> → <?= htmlspecialchars($destination) ?></title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/search.css">
  <style>
    .seats-badge {
      display: inline-block;
      padding: 3px 8px;
      background: #eff6ff;
      color: #1d4ed8;
      border: 1px solid #bfdbfe;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      margin-top: 6px;
    }
    .seats-badge.low {
      background: #fff7ed;
      color: #c2410c;
      border-color: #ffedd5;
    }
  </style>
</head>

<body class="page-search">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main class="layout">
    <!-- ══════════════════════════════════
         БОКОВАЯ ПАНЕЛЬ ФИЛЬТРОВ
    ══════════════════════════════════ -->
    <form class="filters" id="filters" method="GET" action="search.php" aria-label="Фильтры рейсов">
      <h2>Фильтры</h2>

      <input type="hidden" name="origin" value="<?= htmlspecialchars($origin) ?>">
      <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">

      <!-- Сортировка -->
      <div class="filter-group" role="group" aria-labelledby="sort-title">
        <p class="filter-title" id="sort-title">Сортировка</p>
        <label><input type="radio" name="sort" value="price" <?= $sort === 'price' ? 'checked' : '' ?> onchange="this.form.submit()"> По цене</label>
        <label><input type="radio" name="sort" value="departure" <?= $sort === 'departure' ? 'checked' : '' ?> onchange="this.form.submit()"> По времени вылета</label>
        <label><input type="radio" name="sort" value="duration" <?= $sort === 'duration' ? 'checked' : '' ?> onchange="this.form.submit()"> По продолжительности</label>
      </div>

      <!-- Цена -->
      <div class="filter-group" role="group" aria-labelledby="price-title">
        <p class="filter-title" id="price-title">Цена, ₽</p>
        <label><input type="radio" name="price" value="" <?= empty($priceRange) ? 'checked' : '' ?> onchange="this.form.submit()"> Все цены</label>
        <label><input type="radio" name="price" value="low" <?= $priceRange === 'low' ? 'checked' : '' ?> onchange="this.form.submit()"> до 5 000</label>
        <label><input type="radio" name="price" value="medium" <?= $priceRange === 'medium' ? 'checked' : '' ?> onchange="this.form.submit()"> 5 000 – 10 000</label>
        <label><input type="radio" name="price" value="high" <?= $priceRange === 'high' ? 'checked' : '' ?> onchange="this.form.submit()"> от 10 000</label>
      </div>

      <!-- Пересадки -->
      <div class="filter-group" role="group" aria-labelledby="stops-title">
        <p class="filter-title" id="stops-title">Пересадки</p>
        <label><input type="radio" name="stops" value="0" <?= $stops === '0' ? 'checked' : '' ?> onchange="this.form.submit()"> Без пересадок</label>
        <label><input type="radio" name="stops" value="1" <?= $stops === '1' ? 'checked' : '' ?> onchange="this.form.submit()"> 1 пересадка</label>
        <label><input type="radio" name="stops" value="any" <?= $stops === 'any' ? 'checked' : '' ?> onchange="this.form.submit()"> Любые</label>
      </div>

      <!-- Класс обслуживания -->
      <div class="filter-group" role="group" aria-labelledby="cabin-title">
        <p class="filter-title" id="cabin-title">Класс обслуживания</p>
        <label><input type="radio" name="cabin" value="any" <?= $cabin === 'any' ? 'checked' : '' ?> onchange="this.form.submit()"> Любой класс</label>
        <label><input type="radio" name="cabin" value="economy" <?= $cabin === 'economy' ? 'checked' : '' ?> onchange="this.form.submit()"> Эконом</label>
        <label><input type="radio" name="cabin" value="business" <?= $cabin === 'business' ? 'checked' : '' ?> onchange="this.form.submit()"> Бизнес</label>
      </div>

      <button class="button apply" type="submit" style="width: 100%;">Применить</button>
      <a href="search.php" class="outline-link" style="display: block; text-align: center; margin-top: 10px; font-size: 13px;">Сбросить фильтры</a>
    </form>

    <!-- ══════════════════════════════════
         СПИСОК РЕЙСОВ
    ══════════════════════════════════ -->
    <section class="results" id="results" aria-labelledby="results-title">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
        <div>
          <h1 id="results-title" style="margin: 0;"><?= htmlspecialchars($origin) ?> → <?= htmlspecialchars($destination) ?></h1>
          <p class="results-subtitle" id="result-count" style="margin-top: 4px;">
            Найдено рейсов: <strong><?= count($flights) ?></strong>
          </p>
        </div>
        <div style="font-size: 13px; color: #64748b;">
          Цены указаны с учётом такс и сборов
        </div>
      </div>

      <div class="flight-list" id="flight-list" aria-live="polite">
        <?php if (!empty($flights)): ?>
          <?php foreach ($flights as $f): ?>
            <?php 
              $isInCart = in_array($f['id'], $inCartFlightIds);
              $hours = floor($f['duration'] / 60);
              $mins = $f['duration'] % 60;
              $durationStr = ($hours > 0 ? $hours . ' ч ' : '') . ($mins > 0 ? $mins . ' мин' : '');
            ?>
            <article class="flight" aria-label="<?= htmlspecialchars($f['airline']) ?>, вылет <?= htmlspecialchars($f['departure_time']) ?>">
              <div class="airline">
                <span class="airline-code"><?= htmlspecialchars($f['airline_code']) ?></span>
                <span style="font-weight: 600;"><?= htmlspecialchars($f['airline']) ?></span>
                <span style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($f['flight_number']) ?></span>
                <span class="seats-badge <?= $f['seats_available'] < 15 ? 'low' : '' ?>">
                  Осталось мест: <?= $f['seats_available'] ?>
                </span>
              </div>

              <div class="route">
                <div class="airport">
                  <time class="time"><?= htmlspecialchars($f['departure_time']) ?></time>
                  <span class="city"><?= htmlspecialchars($f['origin']) ?> (<?= htmlspecialchars($f['origin_code']) ?>)</span>
                </div>
                <div class="route-line" aria-hidden="true">
                  <span style="font-size: 11px; color: #64748b;"><?= $durationStr ?></span>
                  <span>✈</span>
                  <span style="font-size: 11px; color: <?= $f['stops'] > 0 ? '#ea580c' : '#16a34a' ?>;">
                    <?= $f['stops'] == 0 ? 'прямой' : ($f['stops'] . ' пересадка') ?>
                  </span>
                </div>
                <div class="airport">
                  <time class="time"><?= htmlspecialchars($f['arrival_time']) ?></time>
                  <span class="city"><?= htmlspecialchars($f['destination']) ?> (<?= htmlspecialchars($f['destination_code']) ?>)</span>
                </div>
              </div>

              <div class="fare">
                <span class="price"><?= number_format($f['price'], 0, '', ' ') ?> ₽</span>
                <span style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
                  <?= $f['cabin'] === 'business' ? 'Бизнес-класс' : 'Эконом-класс' ?>
                </span>
                <a href="tickets.php?flight_id=<?= $f['id'] ?>" class="button button-orange" style="text-align: center; text-decoration: none;">
                  Выбрать место
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="background: #fff; padding: 40px; border-radius: 8px; text-align: center;">
            <p style="font-size: 16px; color: #64748b; margin-bottom: 16px;">Рейсов по выбранным фильтрам не найдено.</p>
            <a href="search.php" class="button">Показать все рейсы</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- ══════════════════════════════════
           ПРАВИЛА БРОНИРОВАНИЯ И ВОЗВРАТА
      ══════════════════════════════════ -->
      <section class="rules" id="rules-title" aria-labelledby="rules-heading">
        <h2 id="rules-heading" style="color: var(--blue); margin-top: 0;">Правила бронирования и возврата авиабилетов</h2>
        <p style="margin-bottom: 10px;">
          Бронирование авиабилетов на сайте UFAvia осуществляется моментально онлайн.
          После завершения оплаты электронный билет и посадочный талон направляются на email и доступны в Личном кабинете.
        </p>
        <p style="margin-bottom: 10px;">
          <strong>Условия возврата средств:</strong> возврат билета возможен не позднее чем за 24 часа до времени вылета рейса через Личный кабинет или службу поддержки с удержанием минимального сервисного сбора. При вынужденном возврате (отмена рейса) возвращается 100% стоимости.
        </p>
        <p>
          Багаж до 23 кг и ручная кладь до 10 кг включены в тарифы большинства авиакомпаний.
        </p>
      </section>
    </section>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
