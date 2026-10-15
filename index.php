<?php
/**
 * UFAvia — Главная страница
 * Практическая работа № 1
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flights.php';

$popular = getPopularDestinations();
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="UFAvia — поиск и покупка авиабилетов по России и миру из Уфы без комиссии.">
  <title>UFAvia — авиабилеты по всему миру из Уфы</title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/home.css">
</head>

<body class="page-home">
  <?php include __DIR__ . '/includes/header.php'; ?>

  <main>
    <!-- ══════════════════════════════════
         ГЕРОЙ: ИНТЕРАКТИВНЫЙ ПОИСК
    ══════════════════════════════════ -->
    <section class="hero" aria-labelledby="hero-title">
      <div class="container hero-inner">
        <div class="hero-copy">
          <h1 id="hero-title">
            Найдите дешёвые авиабилеты<br class="desktop-break">
            по всему миру
          </h1>
          <p>
            Сравнивайте цены на билеты от сотен авиакомпаний и
            бронируйте без комиссии напрямую.
          </p>
        </div>

        <!-- Форма интерактивного поиска рейсов -->
        <form class="flight-search" id="flight-search" action="pages/search.php" method="GET">
          <div class="search-field">
            <label for="origin">Откуда</label>
            <input id="origin" name="origin" type="text" value="Уфа" placeholder="Город или аэропорт" required>
          </div>

          <div class="search-field">
            <label for="destination">Куда</label>
            <input id="destination" name="destination" type="text" value="Москва" placeholder="Куда летите" required>
          </div>

          <div class="search-field date-field">
            <label for="departure">Туда</label>
            <input id="departure" name="departure" type="date" value="<?= $today ?>" required>
          </div>

          <div class="search-field date-field">
            <label for="return">Обратно</label>
            <input id="return" name="return" type="date" value="<?= $tomorrow ?>">
          </div>

          <div class="search-field" style="max-width: 130px;">
            <label for="cabin">Класс</label>
            <select id="cabin" name="cabin" style="height: 44px; width: 100%; border: 1px solid var(--line); border-radius: 4px; padding: 0 8px; background: #fff; font-size: 14px;">
              <option value="any">Любой</option>
              <option value="economy" selected>Эконом</option>
              <option value="business">Бизнес</option>
            </select>
          </div>

          <button class="search-button" type="submit">Найти билеты</button>

          <p class="form-status" id="form-status" role="status" aria-live="polite"></p>
        </form>
      </div>
    </section>

    <!-- ══════════════════════════════════
         ПОПУЛЯРНЫЕ НАПРАВЛЕНИЯ
    ══════════════════════════════════ -->
    <section class="destinations" id="destinations" aria-labelledby="destinations-title">
      <div class="container">
        <div class="section-heading">
          <div>
            <h2 id="destinations-title">Популярные направления</h2>
            <p>Дешёвые билеты из Уфы (актуальные тарифы из базы данных)</p>
          </div>

          <a class="outline-link" href="pages/search.php">Все направления</a>
        </div>

        <div class="destination-grid">
          <?php if (!empty($popular)): ?>
            <?php foreach ($popular as $dest): ?>
              <article class="destination-card" onclick="window.location.href='pages/search.php?origin=Уфа&destination=<?= urlencode($dest['destination']) ?>'" style="cursor: pointer;">
                <div class="destination-space" aria-hidden="true"></div>
                <div class="destination-copy">
                  <h3><?= htmlspecialchars($dest['destination']) ?></h3>
                  <p><strong>от <?= number_format($dest['min_price'], 0, '', ' ') ?> ₽</strong> <span>в одну сторону</span></p>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <article class="destination-card">
              <div class="destination-space" aria-hidden="true"></div>
              <div class="destination-copy">
                <h3>Москва</h3>
                <p><strong>от 3 750 ₽</strong> <span>в одну сторону</span></p>
              </div>
            </article>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════
         ОТЗЫВЫ ПАССАЖИРОВ
    ══════════════════════════════════ -->
    <section class="testimonials" aria-labelledby="testimonials-title">
      <div class="container">
        <div class="section-heading testimonial-heading">
          <div>
            <h2 id="testimonials-title">Отзывы наших пассажиров</h2>
            <p>Более 50 000 довольных клиентов по всей России</p>
          </div>
        </div>

        <div class="testimonial-grid">
          <article class="testimonial-card">
            <div class="reviewer">
              <span class="avatar" aria-hidden="true">А</span>
              <div>
                <h3>Анна Иванова</h3>
                <div class="stars" aria-label="5 из 5 звёзд">★★★★★</div>
              </div>
            </div>
            <blockquote>
              «Удобный сайт, купила билеты из Уфы в Москву за 5 минут. Цены действительно
              ниже, чем у других сервисов. Электронный посадочный пришел сразу!»
            </blockquote>
          </article>

          <article class="testimonial-card">
            <div class="reviewer">
              <span class="avatar" aria-hidden="true">Д</span>
              <div>
                <h3>Дмитрий Петров</h3>
                <div class="stars" aria-label="5 из 5 звёзд">★★★★★</div>
              </div>
            </div>
            <blockquote>
              «Пользуюсь UFAvia уже больше года для командировок. Всегда нахожу выгодные предложения
              и удобный выбор мест в салоне. Рекомендую!»
            </blockquote>
          </article>

          <article class="testimonial-card">
            <div class="reviewer">
              <span class="avatar" aria-hidden="true">М</span>
              <div>
                <h3>Мария Смирнова</h3>
                <div class="stars" aria-label="5 из 5 звёзд">★★★★★</div>
              </div>
            </div>
            <blockquote>
              «Летали с семьей в Сочи на каникулы. Очень порадовала прозрачная стоимость
              без скрытых комиссий и моментальное оформление заказа.»
            </blockquote>
          </article>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════
         О КОМПАНИИ
    ══════════════════════════════════ -->
    <section class="about-section" id="about-info" style="padding: 60px 0; background: #fff; border-top: 1px solid var(--line);">
      <div class="container" style="max-width: 900px; text-align: center;">
        <h2 style="font-size: 26px; color: var(--blue); margin-bottom: 16px;">О компании UFAvia</h2>
        <p style="font-size: 16px; color: #4b5563; line-height: 1.7; margin-bottom: 24px;">
          UFAvia — региональный и федеральный сервис поиска и бронирования авиабилетов, базирующийся в Республике Башкортостан.
          Мы сотрудничаем с ведущими авиакомпаниями («Аэрофлот», «S7 Airlines», «Победа», «Utair», «Россия», «Nordwind»), предоставляя пассажирам удобный поиск, сравнение цен, выбор мест в самолёте и моментальную выдачу электронных билетов.
        </p>
        <div style="display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin-top: 30px;">
          <div>
            <div style="font-size: 32px; font-weight: 800; color: var(--orange);">500+</div>
            <div style="font-size: 14px; color: #64748b; margin-top: 4px;">Маршрутов и рейсов</div>
          </div>
          <div>
            <div style="font-size: 32px; font-weight: 800; color: var(--blue);">0 ₽</div>
            <div style="font-size: 14px; color: #64748b; margin-top: 4px;">Скрытых комиссий</div>
          </div>
          <div>
            <div style="font-size: 32px; font-weight: 800; color: var(--orange);">24/7</div>
            <div style="font-size: 14px; color: #64748b; margin-top: 4px;">Служба поддержки</div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/footer.php'; ?>
  <script src="assets/js/home.js"></script>
</body>
</html>
