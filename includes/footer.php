<?php
/**
 * UFAvia — Общий подвал сайта
 * Подвал, модальное окно авторизации/регистрации и правила
 */

$isPagesDir = (strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false);
$basePath = $isPagesDir ? '../' : './';
?>
<!-- ══════════════════════════════════
     FOOTER
══════════════════════════════════ -->
<footer class="site-footer" id="about">
  <div class="container footer-grid">
    <div class="footer-brand">
      <h2>UFAvia</h2>
      <p>Сервис поиска и покупки<br>авиабилетов по России и миру.</p>
      <div style="margin-top: 12px; font-size: 13px; color: #94a3b8;">
        г. Стерлитамак, 2026
      </div>
    </div>

    <div class="footer-column">
      <h3>Компания</h3>
      <a href="<?= $basePath ?>index.php#about">О нас</a>
      <a href="<?= $basePath ?>index.php#destinations">Направления</a>
      <a href="<?= $basePath ?>index.php#contacts">Контакты</a>
      <?php if (isAdmin()): ?>
        <a href="<?= $basePath ?>pages/admin.php" style="color: #ff6b2c;">Панель управления</a>
      <?php endif; ?>
    </div>

    <div class="footer-column">
      <h3>Помощь</h3>
      <a href="javascript:void(0)" onclick="openRulesModal()">Правила бронирования</a>
      <a href="javascript:void(0)" onclick="openRulesModal()">Возврат средств</a>
      <a href="<?= $basePath ?>pages/profile.php">Личный кабинет</a>
    </div>

    <address class="footer-column" id="contacts">
      <h3>Контакты</h3>
      <a href="tel:+73470000000">+7 (347) 000-00-00</a>
      <a href="mailto:info@ufavia.ru">info@ufavia.ru</a>
      <span>г. Стерлитамак</span>
      <span style="font-size: 13px; color: #94a3b8; margin-top: 4px;">Служба поддержки 24/7</span>
    </address>
  </div>

  <div class="container copyright">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
      <span>© 2026 UFAvia. Все права защищены.</span>
    </div>
  </div>
</footer>

<!-- ══════════════════════════════════
     МОДАЛЬНОЕ ОКНО АВТОРИЗАЦИИ / РЕГИСТРАЦИИ
══════════════════════════════════ -->
<div id="auth-modal" class="auth-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeAuthModal()">
  <div class="auth-modal-box">
    <button type="button" class="auth-modal-close" onclick="closeAuthModal()" aria-label="Закрыть">&times;</button>
    
    <div class="auth-tabs">
      <button type="button" id="tab-btn-login" class="auth-tab active" onclick="switchAuthTab('login')">Вход</button>
      <button type="button" id="tab-btn-register" class="auth-tab" onclick="switchAuthTab('register')">Регистрация</button>
    </div>

    <div id="auth-error-msg" class="auth-alert error" style="display: none;"></div>
    <div id="auth-success-msg" class="auth-alert success" style="display: none;"></div>

    <!-- Форма входа -->
    <form id="form-login" method="POST" action="<?= $basePath ?>pages/login.php" onsubmit="handleAuthSubmit(event, 'login')">
      <input type="hidden" name="action" value="login">
      <div class="modal-field">
        <label for="modal-login-email">Email или Логин</label>
        <input type="email" id="modal-login-email" name="email" required placeholder="name@example.com" value="ivan@example.com">
      </div>
      <div class="modal-field">
        <label for="modal-login-password">Пароль</label>
        <input type="password" id="modal-login-password" name="password" required placeholder="Введите пароль" value="user123">
      </div>

      <div class="quick-demo-logins" style="margin-bottom: 16px; padding: 10px; background: #f8fafc; border-radius: 6px; font-size: 13px;">
        <span style="font-weight: 600; color: #475569;">Быстрый тестовый вход:</span>
        <div style="display: flex; gap: 8px; margin-top: 6px;">
          <button type="button" class="btn-demo" onclick="fillDemo('admin@ufavia.ru', 'admin123')">Администратор</button>
          <button type="button" class="btn-demo" onclick="fillDemo('ivan@example.com', 'user123')">Пользователь</button>
        </div>
      </div>

      <button type="submit" class="button" style="width: 100%;">Войти</button>
    </form>

    <!-- Форма регистрации -->
    <form id="form-register" method="POST" action="<?= $basePath ?>pages/login.php" style="display: none;" onsubmit="handleAuthSubmit(event, 'register')">
      <input type="hidden" name="action" value="register">
      <div class="modal-field">
        <label for="modal-reg-name">ФИО</label>
        <input type="text" id="modal-reg-name" name="name" required placeholder="Иванов Иван Иванович">
      </div>
      <div class="modal-field">
        <label for="modal-reg-email">Email</label>
        <input type="email" id="modal-reg-email" name="email" required placeholder="name@example.com">
      </div>
      <div class="modal-field">
        <label for="modal-reg-phone">Телефон</label>
        <input type="tel" id="modal-reg-phone" name="phone" placeholder="+7 (999) 000-00-00">
      </div>
      <div class="modal-field">
        <label for="modal-reg-password">Пароль</label>
        <input type="password" id="modal-reg-password" name="password" required minlength="4" placeholder="Не менее 4 символов">
      </div>
      <button type="submit" class="button" style="width: 100%; margin-top: 8px;">Зарегистрироваться</button>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════
     МОДАЛЬНОЕ ОКНО ПРАВИЛ И ВОЗВРАТА
══════════════════════════════════ -->
<div id="rules-modal" class="auth-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeRulesModal()">
  <div class="auth-modal-box" style="max-width: 560px;">
    <button type="button" class="auth-modal-close" onclick="closeRulesModal()" aria-label="Закрыть">&times;</button>
    <h2 style="font-size: 20px; color: var(--blue); margin-bottom: 14px;">Правила бронирования и возврата авиабилетов</h2>
    <div style="font-size: 14px; line-height: 1.6; color: #4b5563; max-height: 400px; overflow-y: auto; padding-right: 8px;">
      <p style="margin-bottom: 12px;"><strong>1. Бронирование и оформление:</strong><br>
      Бронирование билетов осуществляется на сайте UFAvia в режиме онлайн. После подтверждения заказа и успешной оплаты электронный билет и посадочный талон формируются автоматически с уникальным QR-кодом.</p>
      
      <p style="margin-bottom: 12px;"><strong>2. Электронный билет:</strong><br>
      Электронный билет (маршрутная квитанция) направляется на указанный адрес электронной почты и сохраняется в Личном кабинете пассажира. При прохождении регистрации в аэропорту достаточно предъявить паспорт и электронный билет на смартфоне или в распечатанном виде.</p>
      
      <p style="margin-bottom: 12px;"><strong>3. Условия возврата и обмена:</strong><br>
      Добровольный возврат средств возможен не позднее чем за 24 часа до времени вылета рейса с удержанием установленного авиакомпанией сервисного сбора (150 ₽). При отмене или переносе рейса по вине авиакомпании возврат осуществляется в полном объёме 100% стоимости.</p>

      <p style="margin-bottom: 12px;"><strong>4. Багаж и ручная кладь:</strong><br>
      В базовый тариф включена ручная кладь до 10 кг габаритами 55×40×20 см. Дополнительный багаж до 23 кг можно оформить во время покупки билета или в аэропорту.</p>
    </div>
    <div style="margin-top: 18px; text-align: right;">
      <button type="button" class="button" onclick="closeRulesModal()">Понятно</button>
    </div>
  </div>
</div>

<script>
// Глобальные функции для работы с модальными окнами
function openAuthModal(tab = 'login') {
  const modal = document.getElementById('auth-modal');
  if (modal) {
    modal.style.display = 'flex';
    switchAuthTab(tab);
  }
}

function closeAuthModal() {
  const modal = document.getElementById('auth-modal');
  if (modal) modal.style.display = 'none';
  const err = document.getElementById('auth-error-msg');
  if (err) err.style.display = 'none';
}

function switchAuthTab(tab) {
  const loginForm = document.getElementById('form-login');
  const regForm = document.getElementById('form-register');
  const btnLogin = document.getElementById('tab-btn-login');
  const btnReg = document.getElementById('tab-btn-register');
  const err = document.getElementById('auth-error-msg');
  if (err) err.style.display = 'none';

  if (tab === 'login') {
    loginForm.style.display = 'block';
    regForm.style.display = 'none';
    btnLogin.classList.add('active');
    btnReg.classList.remove('active');
  } else {
    loginForm.style.display = 'none';
    regForm.style.display = 'block';
    btnLogin.classList.remove('active');
    btnReg.classList.add('active');
  }
}

function fillDemo(email, pass) {
  document.getElementById('modal-login-email').value = email;
  document.getElementById('modal-login-password').value = pass;
}

function openRulesModal() {
  const modal = document.getElementById('rules-modal');
  if (modal) modal.style.display = 'flex';
}

function closeRulesModal() {
  const modal = document.getElementById('rules-modal');
  if (modal) modal.style.display = 'none';
}

function handleAuthSubmit(e, type) {
  // Стандартная отправка формы с валидацией
  return true;
}
</script>
