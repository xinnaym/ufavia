/**
 * UFAvia — Главная страница (JavaScript)
 * Валидация формы поиска билетов перед отправкой на сервер
 */
document.addEventListener('DOMContentLoaded', function () {
  const searchForm = document.querySelector('#flight-search');
  if (!searchForm) return;

  const departureInput = document.querySelector('#departure');
  const returnInput = document.querySelector('#return');
  const statusMessage = document.querySelector('#form-status');

  const today = new Date();
  const localToday = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
    .toISOString()
    .split('T')[0];

  if (departureInput) departureInput.min = localToday;
  if (returnInput) returnInput.min = localToday;

  if (departureInput && returnInput) {
    departureInput.addEventListener('change', () => {
      returnInput.min = departureInput.value || localToday;
      if (returnInput.value && returnInput.value < departureInput.value) {
        returnInput.value = '';
        if (statusMessage) statusMessage.textContent = 'Выберите дату возвращения не раньше даты вылета.';
      } else {
        if (statusMessage) statusMessage.textContent = '';
      }
    });
  }

  searchForm.addEventListener('submit', (event) => {
    if (statusMessage) statusMessage.textContent = '';

    if (!searchForm.checkValidity()) {
      event.preventDefault();
      if (statusMessage) statusMessage.textContent = 'Пожалуйста, заполните все поля поиска.';
      searchForm.reportValidity();
      return;
    }

    if (departureInput && returnInput && returnInput.value && returnInput.value < departureInput.value) {
      event.preventDefault();
      if (statusMessage) statusMessage.textContent = 'Дата возвращения не может быть раньше даты вылета.';
      returnInput.focus();
      return;
    }

    // Форма отправляется нативно методом GET на pages/search.php
  });
});
