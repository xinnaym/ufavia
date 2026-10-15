/* ── Animate paid amount counter on load ── */
var paidEl = document.getElementById('paid-amount');
var target = 6150;
var duration = 900;
var start = null;

function animateAmount(ts) {
  if (!start) start = ts;
  var p = Math.min((ts - start) / duration, 1);
  var eased = 1 - Math.pow(1 - p, 3);
  var val = Math.round(eased * target);
  paidEl.textContent = val.toLocaleString('ru-RU') + '\u00a0₽';
  if (p < 1) requestAnimationFrame(animateAmount);
}

/* Start counter after a short delay so it plays after the card appears */
setTimeout(function() {
  requestAnimationFrame(animateAmount);
}, 600);


/* ── PDF download button ── */
var pdfBtn = document.getElementById('btn-pdf');
pdfBtn.addEventListener('click', function() {
  pdfBtn.textContent = 'Скачиваем…';
  pdfBtn.disabled = true;

  setTimeout(function() {
    /* Simulate download complete */
    pdfBtn.textContent = '✓ Загружено';
    pdfBtn.style.background = '#059669';

    setTimeout(function() {
      pdfBtn.textContent = 'Скачать билет (PDF)';
      pdfBtn.style.background = '';
      pdfBtn.disabled = false;
    }, 2000);
  }, 1400);
});
  
