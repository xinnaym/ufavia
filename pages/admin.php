<?php
/**
 * UFAvia — Панель управления администратора
 * Модуль 3: Управление товарами (рейсами), заказами и пользователями
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/flights.php';
require_once __DIR__ . '/../includes/orders.php';

$currentUser = getCurrentUser();

// Проверка прав администратора
if (!isAdmin()) {
    // Если пользователь не администратор, покажем страницу входа с подсказкой
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
      <meta charset="UTF-8">
      <title>UFAvia — Доступ запрещён</title>
      <link rel="stylesheet" href="../assets/css/base.css">
    </head>
    <body style="background: #f8fafc; display: grid; place-items: center; min-height: 100vh;">
      <div style="background: #fff; border-radius: 12px; padding: 36px; max-width: 440px; text-align: center; border: 1px solid var(--line); box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
        <div style="font-size: 40px; margin-bottom: 12px;">🔒</div>
        <h1 style="font-size: 20px; color: #b91c1c; margin-bottom: 10px;">Доступ только для администраторов</h1>
        <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
          Для входа в панель управления необходимо авторизоваться под учетной записью с ролью <strong>admin</strong>.
        </p>
        <form method="POST" action="login.php">
          <input type="hidden" name="action" value="login">
          <input type="hidden" name="email" value="admin@ufavia.ru">
          <input type="hidden" name="password" value="admin123">
          <button type="submit" class="button" style="width: 100%; margin-bottom: 10px;">Войти как Администратор (admin@ufavia.ru)</button>
        </form>
        <a href="../index.php" style="font-size: 13px; color: var(--blue);">Вернуться на главную страницу</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$db = getDb();

// Обработка действий администратора
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Добавление рейса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_flight') {
    $flightData = [
        'flight_number' => $_POST['flight_number'] ?? '',
        'airline' => $_POST['airline'] ?? '',
        'airline_code' => $_POST['airline_code'] ?? 'UF',
        'origin' => $_POST['origin'] ?? 'Уфа',
        'origin_code' => $_POST['origin_code'] ?? 'UFA',
        'destination' => $_POST['destination'] ?? '',
        'destination_code' => $_POST['destination_code'] ?? 'XXX',
        'departure_date' => $_POST['departure_date'] ?? date('Y-m-d'),
        'departure_time' => $_POST['departure_time'] ?? '12:00',
        'arrival_time' => $_POST['arrival_time'] ?? '14:00',
        'duration' => (int)($_POST['duration'] ?? 120),
        'price' => (int)($_POST['price'] ?? 5000),
        'seats_total' => (int)($_POST['seats_total'] ?? 180),
        'seats_available' => (int)($_POST['seats_available'] ?? 40),
        'cabin' => $_POST['cabin'] ?? 'economy',
        'stops' => (int)($_POST['stops'] ?? 0)
    ];

    if (addFlight($flightData)) {
        setFlash('success', 'Новый рейс ' . htmlspecialchars($flightData['flight_number']) . ' успешно добавлен!');
    } else {
        setFlash('error', 'Не удалось добавить рейс');
    }
    header('Location: admin.php?tab=flights');
    exit;
}

// 2. Редактирование рейса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_flight') {
    $flightId = (int)$_POST['flight_id'];
    $flightData = [
        'flight_number' => $_POST['flight_number'] ?? '',
        'airline' => $_POST['airline'] ?? '',
        'airline_code' => $_POST['airline_code'] ?? 'UF',
        'origin' => $_POST['origin'] ?? 'Уфа',
        'origin_code' => $_POST['origin_code'] ?? 'UFA',
        'destination' => $_POST['destination'] ?? '',
        'destination_code' => $_POST['destination_code'] ?? 'XXX',
        'departure_date' => $_POST['departure_date'] ?? date('Y-m-d'),
        'departure_time' => $_POST['departure_time'] ?? '12:00',
        'arrival_time' => $_POST['arrival_time'] ?? '14:00',
        'duration' => (int)($_POST['duration'] ?? 120),
        'price' => (int)($_POST['price'] ?? 5000),
        'seats_total' => (int)($_POST['seats_total'] ?? 180),
        'seats_available' => (int)($_POST['seats_available'] ?? 40),
        'cabin' => $_POST['cabin'] ?? 'economy',
        'stops' => (int)($_POST['stops'] ?? 0)
    ];

    if (updateFlight($flightId, $flightData)) {
        setFlash('success', 'Рейс #' . $flightId . ' успешно обновлён');
    } else {
        setFlash('error', 'Ошибка при обновлении рейса');
    }
    header('Location: admin.php?tab=flights');
    exit;
}

// 3. Удаление рейса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_flight') {
    $flightId = (int)$_POST['flight_id'];
    if (deleteFlight($flightId)) {
        setFlash('success', 'Рейс #' . $flightId . ' успешно удален из базы данных');
    } else {
        setFlash('error', 'Ошибка при удалении рейса');
    }
    header('Location: admin.php?tab=flights');
    exit;
}

// 4. Обновление статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_order_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status'] ?? 'Оплачен');
    if (updateOrderStatus($orderId, $newStatus)) {
        setFlash('success', 'Статус заказа обновлен на «' . htmlspecialchars($newStatus) . '»');
    }
    header('Location: admin.php?tab=orders');
    exit;
}

// 5. Переключение роли пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle_user_role') {
    $targetUserId = (int)$_POST['user_id'];
    $targetRole = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
    $stmt = $db->prepare("UPDATE users SET role = :role WHERE id = :id");
    $stmt->execute([':role' => $targetRole, ':id' => $targetUserId]);
    setFlash('success', 'Роль пользователя изменена на ' . $targetRole);
    header('Location: admin.php?tab=users');
    exit;
}

// Сбор статистики для Dashboard
$totalFlights = (int)$db->query("SELECT COUNT(*) FROM flights")->fetchColumn();
$totalOrders  = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (int)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Отменён'")->fetchColumn();
$totalUsers   = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

$activeTab = $_GET['tab'] ?? 'flights';
$flightsList = getFlights(['sort' => 'price']);
$ordersList = getAllOrders();

// Пользователи
$usersList = $db->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UFAvia — Панель управления администратора</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
  
  <style>
    .admin-stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 18px 20px;
    }
    .stat-val {
      font-size: 26px;
      font-weight: 800;
      color: var(--blue);
      margin-top: 4px;
    }
    .stat-lbl {
      font-size: 13px;
      color: #64748b;
      font-weight: 600;
    }
    .admin-nav {
      display: flex;
      gap: 12px;
      border-bottom: 2px solid #e2e8f0;
      margin-bottom: 24px;
    }
    .admin-nav-item {
      padding: 10px 18px;
      font-weight: 600;
      font-size: 15px;
      color: #64748b;
      text-decoration: none;
      position: relative;
    }
    .admin-nav-item.active {
      color: var(--blue);
    }
    .admin-nav-item.active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 2px;
      background: var(--blue);
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    .admin-table th, .admin-table td {
      padding: 12px 14px;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
    }
    .admin-table th {
      background: #f8fafc;
      font-weight: 600;
      color: #475569;
    }
    .admin-table tr:hover {
      background: #f8fafc;
    }
    .table-container {
      background: #fff;
      border-radius: 8px;
      border: 1px solid var(--line);
      overflow-x: auto;
    }
    .form-box {
      background: #fff;
      border-radius: 8px;
      border: 1px solid var(--line);
      padding: 24px;
      margin-bottom: 24px;
    }
  </style>
</head>
<body style="background: #f1f5f9;">
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main style="padding: 32px 0;">
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <div>
          <h1 style="font-size: 26px; color: var(--ink); margin: 0;">Панель управления администратора</h1>
          <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Управление товарами (рейсами), заказами клиентов и пользователями</p>
        </div>
        <div style="display: flex; gap: 10px;">
          <a href="search.php" class="button button-outline" target="_blank">Открыть сайт</a>
          <a href="logout.php" class="button button-outline" style="color: #ef4444;">Выйти</a>
        </div>
      </div>

      <!-- KPI Статистика -->
      <div class="admin-stats-grid">
        <div class="stat-card">
          <div class="stat-lbl">Рейсов в базе данных:</div>
          <div class="stat-val"><?= $totalFlights ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-lbl">Оформлено заказов:</div>
          <div class="stat-val"><?= $totalOrders ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-lbl">Общая сумма продаж:</div>
          <div class="stat-val" style="color: #16a34a;"><?= number_format($totalRevenue ?: 0, 0, '', ' ') ?> ₽</div>
        </div>
        <div class="stat-card">
          <div class="stat-lbl">Пользователей:</div>
          <div class="stat-val"><?= $totalUsers ?></div>
        </div>
      </div>

      <!-- Вкладки панели -->
      <nav class="admin-nav">
        <a href="admin.php?tab=flights" class="admin-nav-item <?= $activeTab === 'flights' ? 'active' : '' ?>">✈ Товары / Рейсы (<?= $totalFlights ?>)</a>
        <a href="admin.php?tab=add_flight" class="admin-nav-item <?= $activeTab === 'add_flight' ? 'active' : '' ?>">➕ Добавить новый рейс</a>
        <a href="admin.php?tab=orders" class="admin-nav-item <?= $activeTab === 'orders' ? 'active' : '' ?>">📦 Заказы (<?= $totalOrders ?>)</a>
        <a href="admin.php?tab=users" class="admin-nav-item <?= $activeTab === 'users' ? 'active' : '' ?>">👥 Пользователи (<?= $totalUsers ?>)</a>
      </nav>

      <?php if ($activeTab === 'flights'): ?>
        <!-- ── Список рейсов (товаров) с возможностью удаления ── -->
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Рейс</th>
                <th>Авиакомпания</th>
                <th>Маршрут</th>
                <th>Дата / Время</th>
                <th>Мест свободно</th>
                <th>Класс</th>
                <th>Цена</th>
                <th style="text-align: right;">Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($flightsList as $fl): ?>
                <tr>
                  <td><strong>#<?= $fl['id'] ?></strong></td>
                  <td><span style="font-weight: 700; color: var(--blue);"><?= htmlspecialchars($fl['flight_number']) ?></span></td>
                  <td><?= htmlspecialchars($fl['airline']) ?></td>
                  <td><strong><?= htmlspecialchars($fl['origin']) ?></strong> (<?= htmlspecialchars($fl['origin_code']) ?>) → <strong><?= htmlspecialchars($fl['destination']) ?></strong> (<?= htmlspecialchars($fl['destination_code']) ?>)</td>
                  <td><?= date('d.m.Y', strtotime($fl['departure_date'])) ?>, <?= htmlspecialchars($fl['departure_time']) ?></td>
                  <td>
                    <span style="color: <?= $fl['seats_available'] < 15 ? '#ea580c' : '#16a34a' ?>; font-weight: 700;">
                      <?= $fl['seats_available'] ?>
                    </span> / <?= $fl['seats_total'] ?>
                  </td>
                  <td><?= $fl['cabin'] === 'business' ? 'Бизнес' : 'Эконом' ?></td>
                  <td><strong><?= number_format($fl['price'], 0, '', ' ') ?> ₽</strong></td>
                  <td style="text-align: right;">
                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                      <a href="tickets.php?flight_id=<?= $fl['id'] ?>" class="button button-outline" style="font-size: 12px; height: 30px; padding: 0 8px;" target="_blank">Просмотр</a>
                      <form method="POST" action="admin.php" style="margin: 0;" onsubmit="return confirm('Удалить рейс #<?= $fl['id'] ?> (<?= htmlspecialchars($fl['flight_number']) ?>)?');">
                        <input type="hidden" name="action" value="delete_flight">
                        <input type="hidden" name="flight_id" value="<?= $fl['id'] ?>">
                        <button type="submit" class="button" style="font-size: 12px; height: 30px; padding: 0 8px; background: #ef4444; border-color: #ef4444;">
                          Удалить
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($activeTab === 'add_flight'): ?>
        <!-- ── Форма добавления рейса (товара) ── -->
        <div class="form-box">
          <h2 style="font-size: 18px; color: var(--blue); margin-top: 0; margin-bottom: 20px;">
            Добавить новый рейс в базу данных
          </h2>

          <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="add_flight">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
              <div class="modal-field">
                <label>Номер рейса (например, SU-1240) *</label>
                <input type="text" name="flight_number" required placeholder="SU-1240" value="SU-<?= mt_rand(1000, 9999) ?>">
              </div>

              <div class="modal-field">
                <label>Авиакомпания *</label>
                <input type="text" name="airline" required placeholder="Аэрофлот" value="Аэрофлот">
              </div>

              <div class="modal-field">
                <label>Код авиакомпании (IATA) *</label>
                <input type="text" name="airline_code" required placeholder="SU" value="SU" maxlength="3">
              </div>

              <div class="modal-field">
                <label>Город вылета *</label>
                <input type="text" name="origin" required placeholder="Уфа" value="Уфа">
              </div>

              <div class="modal-field">
                <label>Код аэропорта вылета *</label>
                <input type="text" name="origin_code" required placeholder="UFA" value="UFA" maxlength="4">
              </div>

              <div class="modal-field">
                <label>Город назначения *</label>
                <input type="text" name="destination" required placeholder="Калининград" value="Калининград">
              </div>

              <div class="modal-field">
                <label>Код аэропорта назначения *</label>
                <input type="text" name="destination_code" required placeholder="KGD" value="KGD" maxlength="4">
              </div>

              <div class="modal-field">
                <label>Дата вылета *</label>
                <input type="date" name="departure_date" required value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
              </div>

              <div class="modal-field">
                <label>Время вылета *</label>
                <input type="time" name="departure_time" required value="09:15">
              </div>

              <div class="modal-field">
                <label>Время прибытия *</label>
                <input type="time" name="arrival_time" required value="11:30">
              </div>

              <div class="modal-field">
                <label>Длительность полета (минут) *</label>
                <input type="number" name="duration" required value="195">
              </div>

              <div class="modal-field">
                <label>Стоимость билета (рублей) *</label>
                <input type="number" name="price" required value="6800">
              </div>

              <div class="modal-field">
                <label>Всего мест *</label>
                <input type="number" name="seats_total" required value="180">
              </div>

              <div class="modal-field">
                <label>Доступно для бронирования мест *</label>
                <input type="number" name="seats_available" required value="36">
              </div>

              <div class="modal-field">
                <label>Класс обслуживания</label>
                <select name="cabin" style="height: 42px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff;">
                  <option value="economy" selected>Эконом</option>
                  <option value="business">Бизнес</option>
                </select>
              </div>

              <div class="modal-field">
                <label>Количество пересадок</label>
                <select name="stops" style="height: 42px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 0 10px; background: #fff;">
                  <option value="0" selected>0 (Прямой рейс)</option>
                  <option value="1">1 пересадка</option>
                </select>
              </div>
            </div>

            <div style="margin-top: 24px;">
              <button type="submit" class="button" style="min-width: 200px;">
                Сохранить рейс в базе данных
              </button>
            </div>
          </form>
        </div>

      <?php elseif ($activeTab === 'orders'): ?>
        <!-- ── Список заказов клиентов ── -->
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>№ Заказа</th>
                <th>Пассажир</th>
                <th>Контакты</th>
                <th>Рейс</th>
                <th>Сумма</th>
                <th>Оплата / Доставка</th>
                <th>Статус</th>
                <th>Сменить статус</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ordersList as $ord): ?>
                <tr>
                  <td><strong>#<?= htmlspecialchars($ord['order_number']) ?></strong></td>
                  <td><strong><?= htmlspecialchars($ord['passenger_name']) ?></strong></td>
                  <td style="font-size: 13px;">
                    <?= htmlspecialchars($ord['passenger_email']) ?><br>
                    <span style="color: #64748b;"><?= htmlspecialchars($ord['passenger_phone']) ?></span>
                  </td>
                  <td>
                    <?= htmlspecialchars($ord['origin'] ?? 'Уфа') ?> → <?= htmlspecialchars($ord['destination'] ?? 'Москва') ?><br>
                    <span style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($ord['airline'] ?? '') ?> (<?= htmlspecialchars($ord['flight_number'] ?? '') ?>)</span>
                  </td>
                  <td><strong><?= number_format($ord['total_amount'], 0, '', ' ') ?> ₽</strong></td>
                  <td style="font-size: 12px;">
                    <?= htmlspecialchars($ord['payment_method']) ?><br>
                    <span style="color: #64748b;"><?= htmlspecialchars($ord['delivery_method']) ?></span>
                  </td>
                  <td>
                    <?php
                      $bClass = 'badge--paid';
                      if ($ord['status'] === 'В обработке') $bClass = 'badge--pending';
                      if ($ord['status'] === 'Отменён' || $ord['status'] === 'Отменен') $bClass = 'badge--cancelled';
                      if ($ord['status'] === 'Завершен') $bClass = 'badge--completed';
                    ?>
                    <span class="badge <?= $bClass ?>"><?= htmlspecialchars($ord['status']) ?></span>
                  </td>
                  <td>
                    <form method="POST" action="admin.php" style="margin: 0; display: flex; gap: 4px;">
                      <input type="hidden" name="action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                      <select name="status" onchange="this.form.submit()" style="height: 30px; font-size: 12px; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff;">
                        <option value="Оплачен" <?= $ord['status'] === 'Оплачен' ? 'selected' : '' ?>>Оплачен</option>
                        <option value="В обработке" <?= $ord['status'] === 'В обработке' ? 'selected' : '' ?>>В обработке</option>
                        <option value="Завершен" <?= $ord['status'] === 'Завершен' ? 'selected' : '' ?>>Завершен</option>
                        <option value="Отменён" <?= $ord['status'] === 'Отменён' ? 'selected' : '' ?>>Отменён</option>
                      </select>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php elseif ($activeTab === 'users'): ?>
        <!-- ── Список пользователей ── -->
        <div class="table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usersList as $u): ?>
                <tr>
                  <td>#<?= $u['id'] ?></td>
                  <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                  <td>
                    <span style="font-weight: 700; color: <?= $u['role'] === 'admin' ? '#ea580c' : '#0867c9' ?>;">
                      <?= $u['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?>
                    </span>
                  </td>
                  <td><?= !empty($u['created_at']) ? date('d.m.Y H:i', strtotime($u['created_at'])) : '—' ?></td>
                  <td>
                    <?php if ($u['id'] !== $currentUser['id']): ?>
                      <form method="POST" action="admin.php" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_user_role">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="new_role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                        <button type="submit" class="btn-demo" style="font-size: 12px;">
                          <?= $u['role'] === 'admin' ? 'Сделать пользователем' : 'Сделать админом' ?>
                        </button>
                      </form>
                    <?php else: ?>
                      <span style="color: #64748b; font-size: 12px;">(Текущий)</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
