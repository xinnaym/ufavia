<?php
/**
 * UFAvia — Личный кабинет пользователя
 * История бронирований, билеты, контакты, настройки профиля
 * Практическая работа № 1
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/orders.php';

$currentUser = getCurrentUser();

// Обработка сохранения настроек профиля
$profileUpdated = false;
$profileError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!$currentUser) {
        header('Location: profile.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $newPass = !empty($_POST['password']) ? $_POST['password'] : null;

    $res = updateUserProfile($currentUser['id'], $name, $email, $phone, $newPass);
    if ($res['success']) {
        setFlash('success', 'Настройки профиля успешно сохранены');
        $currentUser = getCurrentUser(); // обновляем данные
        $profileUpdated = true;
    } else {
        $profileError = $res['error'] ?? 'Ошибка сохранения профиля';
    }
}

// Заказы текущего пользователя
$orders = $currentUser ? getUserOrders($currentUser['id']) : [];
$activeTab = $_GET['tab'] ?? 'orders';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>UFAvia — Личный кабинет</title>

  <!-- Google Fonts: Montserrat & Open Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  <link rel="stylesheet" href="../assets/css/profile.css">
  <style>
    .badge--completed {
      background: #f1f5f9;
      color: #475569;
    }
    .ticket-card-profile {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 18px;
      margin-bottom: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .ticket-card-profile:hover {
      border-color: var(--blue);
    }
  </style>
</head>

<body class="page-profile">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main>
    <div class="container">
      <?php if (!$currentUser): ?>
        <!-- Экран для неавторизованного пользователя -->
        <div style="max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; padding: 36px; border: 1px solid var(--line); box-shadow: 0 4px 16px rgba(0,0,0,0.05);">
          <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 40px; margin-bottom: 10px;">👤</div>
            <h1 style="font-size: 22px; color: var(--blue); margin: 0 0 8px;">Вход в Личный кабинет</h1>
            <p style="color: #64748b; font-size: 14px;">Для просмотра истории бронирований и сохраненных билетов войдите или зарегистрируйтесь.</p>
          </div>

          <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button type="button" class="button" style="flex: 1;" onclick="openAuthModal('login')">Войти</button>
            <button type="button" class="button button-outline" style="flex: 1;" onclick="openAuthModal('register')">Регистрация</button>
          </div>

          <div style="border-top: 1px solid var(--line); padding-top: 16px; text-align: center;">
            <span style="font-size: 13px; color: #64748b;">Быстрый тестовый вход:</span>
            <div style="display: flex; gap: 8px; justify-content: center; margin-top: 10px;">
              <form method="POST" action="login.php" style="margin: 0;">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="email" value="ivan@example.com">
                <input type="hidden" name="password" value="user123">
                <button type="submit" class="btn-demo">Войти как Иван (Пассажир)</button>
              </form>
              <form method="POST" action="login.php" style="margin: 0;">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="email" value="admin@ufavia.ru">
                <input type="hidden" name="password" value="admin123">
                <button type="submit" class="btn-demo" style="border-color: #f97316; color: #f97316;">Войти как Администратор</button>
              </form>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- Личный кабинет авторизованного пользователя -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
          <div>
            <h1 class="page-title anim" style="margin: 0; font-size: 26px;">Личный кабинет: <?= htmlspecialchars($currentUser['name']) ?></h1>
            <p style="color: #64748b; font-size: 14px; margin-top: 4px;">
              Роль: <strong><?= $currentUser['role'] === 'admin' ? 'Администратор' : 'Пассажир' ?></strong> | Email: <?= htmlspecialchars($currentUser['email']) ?>
            </p>
          </div>
          <?php if (isAdmin()): ?>
            <a href="admin.php" class="button" style="background: #ff6b2c;">
              ⚙ Перейти в панель администратора
            </a>
          <?php endif; ?>
        </div>

        <div class="page-grid">
          <!-- ── Боковая панель вкладок ── -->
          <aside class="sidebar anim">
            <nav class="sidebar-card" aria-label="Навигация по кабинету">
              <a href="profile.php?tab=orders" class="sidebar-link <?= $activeTab === 'orders' ? 'active' : '' ?>">Мои заказы</a>
              <a href="profile.php?tab=tickets" class="sidebar-link <?= $activeTab === 'tickets' ? 'active' : '' ?>">Мои билеты</a>
              <a href="profile.php?tab=contacts" class="sidebar-link <?= $activeTab === 'contacts' ? 'active' : '' ?>">Контакты</a>
              <a href="profile.php?tab=settings" class="sidebar-link <?= $activeTab === 'settings' ? 'active' : '' ?>">Настройки профиля</a>
              <a href="logout.php" class="sidebar-link" style="color: #ef4444;">Выйти из аккаунта</a>
            </nav>
          </aside>

          <!-- ── Основной контент вкладки ── -->
          <div class="content">

            <?php if ($activeTab === 'orders'): ?>
              <!-- Вкладка: Мои заказы -->
              <section class="card anim d1" aria-label="История бронирований">
                <h2 class="card-title">История бронирований</h2>

                <?php if (!empty($orders)): ?>
                  <div class="table-scroll">
                    <table class="bookings-table" aria-label="Список заказов">
                      <thead>
                        <tr>
                          <th class="col-order">№ заказа</th>
                          <th class="col-route">Маршрут</th>
                          <th class="col-date">Дата</th>
                          <th class="col-amount">Сумма</th>
                          <th class="col-status">Статус</th>
                          <th style="text-align: right;">Действие</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($orders as $ord): ?>
                          <?php
                            $badgeClass = 'badge--paid';
                            if ($ord['status'] === 'В обработке') $badgeClass = 'badge--pending';
                            if ($ord['status'] === 'Отменён' || $ord['status'] === 'Отменен') $badgeClass = 'badge--cancelled';
                            if ($ord['status'] === 'Завершен') $badgeClass = 'badge--completed';
                          ?>
                          <tr>
                            <td class="col-order">
                              <strong>#<?= htmlspecialchars($ord['order_number']) ?></strong>
                            </td>
                            <td class="col-route">
                              <?= htmlspecialchars($ord['origin'] ?? 'Уфа') ?> → <?= htmlspecialchars($ord['destination'] ?? 'Москва') ?>
                            </td>
                            <td class="col-date">
                              <?= !empty($ord['created_at']) ? date('d.m.Y', strtotime($ord['created_at'])) : '15.06.2024' ?>
                            </td>
                            <td class="col-amount">
                              <strong><?= number_format($ord['total_amount'], 0, '', ' ') ?> ₽</strong>
                            </td>
                            <td class="col-status">
                              <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($ord['status']) ?></span>
                            </td>
                            <td style="text-align: right;">
                              <a href="checkout.php?order_id=<?= $ord['id'] ?>" class="button button-outline" style="font-size: 12px; height: 32px; padding: 0 10px;">
                                Посадочный талон
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div style="padding: 30px; text-align: center; color: #64748b;">
                    У вас пока нет оформленных заказов.<br>
                    <a href="search.php" class="button" style="margin-top: 14px; display: inline-block;">Найти авиабилет</a>
                  </div>
                <?php endif; ?>
              </section>

            <?php elseif ($activeTab === 'tickets'): ?>
              <!-- Вкладка: Мои билеты -->
              <section class="card anim d1" aria-label="Мои билеты">
                <h2 class="card-title">Актуальные электронные билеты</h2>
                <?php if (!empty($orders)): ?>
                  <?php foreach ($orders as $ord): ?>
                    <div class="ticket-card-profile">
                      <div>
                        <div style="font-size: 17px; font-weight: 700; color: var(--blue);">
                          <?= htmlspecialchars($ord['origin'] ?? 'Уфа') ?> → <?= htmlspecialchars($ord['destination'] ?? 'Москва') ?>
                        </div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                          Заказ <strong>#<?= htmlspecialchars($ord['order_number']) ?></strong> · Пассажир: <?= htmlspecialchars($ord['passenger_name']) ?>
                        </div>
                        <div style="font-size: 13px; color: #475569; margin-top: 4px;">
                          Способ получения: <?= htmlspecialchars($ord['delivery_method']) ?> · Статус: <?= htmlspecialchars($ord['status']) ?>
                        </div>
                      </div>
                      <div>
                        <a href="checkout.php?order_id=<?= $ord['id'] ?>" class="button" style="display: inline-flex; align-items: center; gap: 6px;">
                          <span>🎫 Показать билет с QR</span>
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="color: #64748b;">Нет активных билетов.</p>
                <?php endif; ?>
              </section>

            <?php elseif ($activeTab === 'contacts'): ?>
              <!-- Вкладка: Контакты -->
              <section class="card anim d1" aria-label="Список контактов">
                <h2 class="card-title">Список контактов и пассажиров</h2>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 18px;">
                  Контакты, сохранённые в профиле для быстрого автозаполнения при покупке билетов:
                </p>
                <div style="background: #f8fafc; border-radius: 8px; padding: 18px; border: 1px solid #e2e8f0;">
                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                    <div>
                      <div style="font-size: 12px; color: #64748b;">Основной пассажир:</div>
                      <div style="font-weight: 700; color: var(--ink);"><?= htmlspecialchars($currentUser['name']) ?></div>
                    </div>
                    <div>
                      <div style="font-size: 12px; color: #64748b;">Электронная почта:</div>
                      <div style="font-weight: 600;"><?= htmlspecialchars($currentUser['email']) ?></div>
                    </div>
                    <div>
                      <div style="font-size: 12px; color: #64748b;">Контактный телефон:</div>
                      <div style="font-weight: 600;"><?= htmlspecialchars($currentUser['phone'] ?? '+7 (917) 000-00-00') ?></div>
                    </div>
                  </div>
                </div>
              </section>

            <?php elseif ($activeTab === 'settings'): ?>
              <!-- Вкладка: Настройки профиля -->
              <section class="card anim d2" aria-label="Настройки профиля">
                <h2 class="card-title">Настройки профиля</h2>

                <?php if ($profileUpdated): ?>
                  <div class="flash-alert flash-success">Настройки успешно обновлены!</div>
                <?php endif; ?>
                <?php if (!empty($profileError)): ?>
                  <div class="flash-alert flash-error"><?= htmlspecialchars($profileError) ?></div>
                <?php endif; ?>

                <form class="form-grid" id="profile-form" method="POST" action="profile.php?tab=settings">
                  <input type="hidden" name="action" value="update_profile">

                  <div class="form-field">
                    <label for="f-name">ФИО</label>
                    <input id="f-name" name="name" type="text" value="<?= htmlspecialchars($currentUser['name']) ?>" required autocomplete="name" />
                  </div>

                  <div class="form-field">
                    <label for="f-email">Email</label>
                    <input id="f-email" name="email" type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required autocomplete="email" />
                  </div>

                  <div class="form-field">
                    <label for="f-password">Новый пароль (оставьте пустым, если не меняете)</label>
                    <input id="f-password" name="password" type="password" placeholder="········" autocomplete="new-password" />
                  </div>

                  <div class="form-field">
                    <label for="f-phone">Телефон</label>
                    <input id="f-phone" name="phone" type="tel" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" autocomplete="tel" />
                  </div>

                  <div class="form-actions">
                    <button type="submit" class="btn-save" id="btn-save" style="cursor: pointer;">
                      Сохранить изменения
                    </button>
                  </div>
                </form>
              </section>
            <?php endif; ?>

          </div><!-- /content -->
        </div><!-- /page-grid -->
      <?php endif; ?>
    </div><!-- /container -->
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../assets/js/profile.js"></script>
</body>
</html>
