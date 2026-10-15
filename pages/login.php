<?php
/**
 * UFAvia — Авторизация и регистрация пользователей
 * Поддержка PHP 7.2+
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = loginUser($email, $password);
        if ($res['success']) {
            setFlash('success', 'Добро пожаловать, ' . $res['user']['name'] . '!');
            if ($res['user']['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: profile.php');
            }
            exit;
        } else {
            $error = $res['error'];
        }
    } elseif ($action === 'register') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = registerUser($name, $email, $phone, $password);
        if ($res['success']) {
            setFlash('success', 'Регистрация прошла успешно! Добро пожаловать.');
            header('Location: profile.php');
            exit;
        } else {
            $error = $res['error'];
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UFAvia — Вход и Регистрация</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/base.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <main style="padding: 40px 0;">
    <div class="container" style="max-width: 480px;">
      <div style="background: #fff; border-radius: 12px; padding: 32px; border: 1px solid var(--line); box-shadow: 0 4px 16px rgba(0,0,0,0.06);">
        <h1 style="font-size: 24px; color: var(--blue); text-align: center; margin-top: 0; margin-bottom: 20px;">
          Авторизация UFAvia
        </h1>

        <?php if (!empty($error)): ?>
          <div class="flash-alert flash-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="auth-tabs" style="justify-content: center; margin-bottom: 24px;">
          <button type="button" class="auth-tab active" id="tab-login" onclick="showForm('login')">Вход</button>
          <button type="button" class="auth-tab" id="tab-reg" onclick="showForm('reg')">Регистрация</button>
        </div>

        <form id="login-form-main" method="POST" action="login.php">
          <input type="hidden" name="action" value="login">
          <div class="modal-field">
            <label for="f-email">Email или логин</label>
            <input type="email" id="f-email" name="email" required placeholder="ivan@example.com" value="ivan@example.com">
          </div>
          <div class="modal-field">
            <label for="f-pass">Пароль</label>
            <input type="password" id="f-pass" name="password" required placeholder="••••••••" value="user123">
          </div>

          <div style="background: #f8fafc; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 16px;">
            <strong>Тестовые данные:</strong><br>
            Администратор: <a href="javascript:void(0)" onclick="quick('admin@ufavia.ru', 'admin123')">admin@ufavia.ru / admin123</a><br>
            Пользователь: <a href="javascript:void(0)" onclick="quick('ivan@example.com', 'user123')">ivan@example.com / user123</a>
          </div>

          <button type="submit" class="button" style="width: 100%;">Войти</button>
        </form>

        <form id="reg-form-main" method="POST" action="login.php" style="display: none;">
          <input type="hidden" name="action" value="register">
          <div class="modal-field">
            <label for="reg-name">ФИО *</label>
            <input type="text" id="reg-name" name="name" required placeholder="Иванов Иван">
          </div>
          <div class="modal-field">
            <label for="reg-email">Email *</label>
            <input type="email" id="reg-email" name="email" required placeholder="name@example.com">
          </div>
          <div class="modal-field">
            <label for="reg-phone">Телефон</label>
            <input type="tel" id="reg-phone" name="phone" placeholder="+7 (999) 000-00-00">
          </div>
          <div class="modal-field">
            <label for="reg-pass">Пароль *</label>
            <input type="password" id="reg-pass" name="password" required minlength="4" placeholder="Минимум 4 символа">
          </div>
          <button type="submit" class="button" style="width: 100%; margin-top: 8px;">Зарегистрироваться</button>
        </form>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <script>
    function showForm(type) {
      const l = document.getElementById('login-form-main');
      const r = document.getElementById('reg-form-main');
      const tl = document.getElementById('tab-login');
      const tr = document.getElementById('tab-reg');
      if (type === 'login') {
        l.style.display = 'block';
        r.style.display = 'none';
        tl.classList.add('active');
        tr.classList.remove('active');
      } else {
        l.style.display = 'none';
        r.style.display = 'block';
        tl.classList.remove('active');
        tr.classList.add('active');
      }
    }
    function quick(e, p) {
      document.getElementById('f-email').value = e;
      document.getElementById('f-pass').value = p;
    }
  </script>
</body>
</html>
