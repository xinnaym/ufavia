/* ── Scroll-in animations ── */
var animEls = document.querySelectorAll('.anim');
var io = new IntersectionObserver(function(entries) {
  entries.forEach(function(e) {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      io.unobserve(e.target);
    }
  });
}, { threshold: 0.08 });
animEls.forEach(function(el) { io.observe(el); });


/* ── Extras pricing logic ── */
var BASE_PRICE = 6150;
var totalEl    = document.getElementById('total-price');
var checkboxes = document.querySelectorAll('[data-extra]');

function recalc() {
  var extras = 0;
  checkboxes.forEach(function(cb) {
    if (cb.checked) extras += parseInt(cb.dataset.extra, 10);
  });
  var total = BASE_PRICE + extras;
  animateValue(totalEl, total);
}

function animateValue(el, target) {
  var current = parseInt(el.textContent.replace(/[^0-9]/g, '') || '0', 10);
  if (current === target) return;
  var duration = 350;
  var start = performance.now();

  function tick(now) {
    var p = Math.min((now - start) / duration, 1);
    var eased = 1 - Math.pow(1 - p, 3);
    var val = Math.round(current + (target - current) * eased);
    el.textContent = val.toLocaleString('ru-RU') + ' ₽';
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

checkboxes.forEach(function(cb) {
  cb.addEventListener('change', recalc);
});


/* ── Form submit ── */
var form = document.getElementById('booking-form');
var btn  = document.getElementById('btn-confirm');

form.addEventListener('submit', function(e) {
  e.preventDefault();

  var required = ['f-surname', 'f-name', 'f-dob', 'f-passport', 'f-email', 'f-phone'];
  for (var i = 0; i < required.length; i++) {
    var field = document.getElementById(required[i]);
    if (!field.value.trim() || !field.checkValidity()) {
      field.focus();
      field.style.borderColor = '#dc2626';
      field.addEventListener('input', function () { this.style.borderColor = ''; }, { once: true });
      return;
    }
  }

  btn.textContent = 'Оформляем…';
  btn.disabled = true;

  setTimeout(function() {
    btn.textContent = 'Заказ подтверждён ✓';
    btn.style.background = '#059669';
    setTimeout(function () { window.location.href = 'checkout.html'; }, 600);
  }, 1200);
});
  
