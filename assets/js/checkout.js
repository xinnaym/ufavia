/**
 * UFAvia — Подтверждение заказа и посадочный талон
 */
document.addEventListener('DOMContentLoaded', function () {
  const pdfBtn = document.getElementById('btn-pdf');
  if (pdfBtn) {
    pdfBtn.addEventListener('click', function () {
      window.print();
    });
  }
});
