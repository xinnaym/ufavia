/*tickets*/

/* ─── Scroll-in animations ─── */
const animEls = document.querySelectorAll('.anim');
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      io.unobserve(e.target);
    }
  });
}, { threshold: 0.1 });
animEls.forEach(el => io.observe(el));


/* ─── Seat selection ─── */
const seats = document.querySelectorAll('.seat--free, .seat--selected');

seats.forEach(btn => {
  btn.addEventListener('click', () => {
    const isSelected = btn.classList.contains('seat--selected');

    // deselect all
    document.querySelectorAll('.seat--selected').forEach(s => {
      s.classList.replace('seat--selected', 'seat--free');
      s.setAttribute('aria-pressed', 'false');
      s.setAttribute('aria-label',
        s.getAttribute('aria-label').replace('выбрано', 'свободно'));
    });

    // select clicked (unless it was already selected → toggle off)
    if (!isSelected) {
      btn.classList.replace('seat--free', 'seat--selected');
      btn.setAttribute('aria-pressed', 'true');
      btn.setAttribute('aria-label',
        btn.getAttribute('aria-label').replace('свободно', 'выбрано'));
    }
  });
});


/* ─── Animated price counters ─── */
function animateCount(el, target, suffix, duration) {
  const start = performance.now();
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
    const val = Math.round(eased * target);
    el.textContent = val.toLocaleString('ru-RU') + suffix;
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

animateCount(document.getElementById('price-ticket'),  5400, '\u00a0₽', 900);
animateCount(document.getElementById('price-seat'),     300, '\u00a0₽', 700);
animateCount(document.getElementById('price-service'),  150, '\u00a0₽', 600);
animateCount(document.getElementById('price-total'),   5850, '\u00a0₽', 950);


/* ─── Buy button feedback ─── */
const buyBtn = document.getElementById('btn-buy');
buyBtn.addEventListener('click', () => {
  buyBtn.textContent = 'Оформляем…';
  buyBtn.disabled = true;
  buyBtn.style.opacity = '.8';
  setTimeout(() => {
    window.location.href = 'order.html';
  }, 900);
});
