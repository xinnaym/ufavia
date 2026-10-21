/**
 * UFAvia — Поиск авиабилетов (JavaScript)
 */
document.addEventListener('DOMContentLoaded', function () {
  // Обработчики интерфейса фильтрации
  const filterInputs = document.querySelectorAll('#filters input[type="radio"], #filters select');
  filterInputs.forEach(input => {
    input.addEventListener('change', function () {
      const form = this.closest('form');
      if (form) form.submit();
    });
  });
});
