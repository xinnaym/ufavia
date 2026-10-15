const searchForm = document.querySelector('#flight-search');
const departureInput = document.querySelector('#departure');
const returnInput = document.querySelector('#return');
const statusMessage = document.querySelector('#form-status');

const today = new Date();

const localToday = new Date(
  today.getTime() - today.getTimezoneOffset() * 60000
)
  .toISOString()
  .split('T')[0];

departureInput.min = localToday;
returnInput.min = localToday;

departureInput.addEventListener('change', () => {
  returnInput.min = departureInput.value || localToday;

  if (returnInput.value && returnInput.value < departureInput.value) {
    returnInput.value = '';
    statusMessage.textContent =
      'Выберите дату возвращения не раньше даты вылета.';
  } else {
    statusMessage.textContent = '';
  }
});

searchForm.addEventListener('submit', (event) => {
  event.preventDefault();
  statusMessage.textContent = '';

  if (!searchForm.checkValidity()) {
    statusMessage.textContent =
      'Заполните все поля поиска и выберите даты.';

    searchForm.reportValidity();
    return;
  }

  if (returnInput.value < departureInput.value) {
    statusMessage.textContent =
      'Дата возвращения не может быть раньше даты вылета.';

    returnInput.focus();
    return;
  }

  statusMessage.textContent =
    'Параметры приняты. Ищем подходящие билеты…';

  const params = new URLSearchParams({
    from: document.querySelector('#origin').value.trim(),
    to: document.querySelector('#destination').value.trim(),
    date: departureInput.value,
    back: returnInput.value
  });
  window.location.href = 'pages/search.html?' + params.toString();
});

document.querySelectorAll('a[href="#"]').forEach((link) => {
  link.addEventListener('click', (event) => {
    event.preventDefault();
  });
});
