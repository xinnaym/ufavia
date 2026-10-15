/*profile.html*/
/* ── Scroll-in animations ── */
const animEls = document.querySelectorAll('.anim');
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      io.unobserve(e.target);
    }
  });
}, { threshold: 0.08 });
animEls.forEach(el => io.observe(el));


/* ── Sidebar active link ── */
document.querySelectorAll('.sidebar-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
  });
});


/* ── Profile form submit ── */
const form    = document.getElementById('profile-form');
const saveBtn = document.getElementById('btn-save');

form.addEventListener('submit', e => {
  e.preventDefault();

  saveBtn.textContent = 'Сохраняем…';
  saveBtn.disabled    = true;
  saveBtn.style.opacity = '.75';

  setTimeout(() => {
    saveBtn.textContent  = '✓ Сохранено';
    saveBtn.style.opacity = '1';
    saveBtn.style.background = '#059669';

    setTimeout(() => {
      saveBtn.textContent  = 'Сохранить изменения';
      saveBtn.disabled     = false;
      saveBtn.style.background = '';
    }, 2000);
  }, 1200);
});


/* ── Table row hover highlight (already via CSS, but add pointer) ── */
document.querySelectorAll('.bookings-table tbody tr').forEach(row => {
  row.style.cursor = 'pointer';
});
