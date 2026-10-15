/* ── Scroll-in animations ── */
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


/* ── Cart Logic ── */
const feePerTicket = 150;

function updateSummary() {
  const cards = document.querySelectorAll('.ticket-card:not(.removing)');
  const count = cards.length;
  
  let sum = 0;
  cards.forEach(c => sum += parseInt(c.dataset.price, 10));

  const totalFee = count * feePerTicket;
  const finalTotal = sum + totalFee;

  const ticketsLabel = document.getElementById('tickets-label');
  const ticketsVal   = document.getElementById('tickets-val');
  const feeVal       = document.getElementById('fee-val');
  const totalVal     = document.getElementById('total-val');
  const checkoutBtn  = document.getElementById('btn-checkout');

  if (count === 0) {
    ticketsLabel.textContent = 'Билеты (0 шт.):';
    ticketsVal.textContent   = '0 ₽';
    feeVal.textContent       = '0 ₽';
    totalVal.textContent     = '0 ₽';
    checkoutBtn.disabled     = true;
  } else {
    ticketsLabel.textContent = `Билеты (${count} шт.):`;
    
    // Simple animation for numbers
    animateValue(ticketsVal, sum);
    animateValue(feeVal, totalFee);
    animateValue(totalVal, finalTotal);
  }
}

function animateValue(el, target) {
  const current = parseInt(el.textContent.replace(/[^0-9]/g, '') || 0, 10);
  if (current === target) return;
  
  const duration = 400;
  const start = performance.now();
  
  function tick(now) {
    const p = Math.min((now - start) / duration, 1);
    const val = Math.round(current + (target - current) * p);
    el.textContent = val.toLocaleString('ru-RU') + ' ₽';
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

// Remove buttons
document.querySelectorAll('.btn-remove').forEach(btn => {
  btn.addEventListener('click', e => {
    const card = e.target.closest('.ticket-card');
    card.classList.add('removing');
    
    // Update math immediately
    updateSummary();
    
    // Remove from DOM after transition
    setTimeout(() => {
      card.remove();
    }, 400);
  });
});

// Checkout button
const checkoutBtn = document.getElementById('btn-checkout');
checkoutBtn.addEventListener('click', () => {
  checkoutBtn.textContent = 'Оформляем...';
  checkoutBtn.disabled = true;
  setTimeout(() => {
    checkoutBtn.textContent = 'Оформлено ✓';
    checkoutBtn.style.background = '#059669';
    setTimeout(() => { window.location.href = 'order.html'; }, 500);
  }, 800);
});

  
