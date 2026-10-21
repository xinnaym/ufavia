/**
 * UFAvia — Выбор билетов и мест (JavaScript)
 * Интерактивная схема салона самолета
 */
document.addEventListener('DOMContentLoaded', function () {
  // Анимации при прокрутке
  const animEls = document.querySelectorAll('.anim');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });
    animEls.forEach(el => io.observe(el));
  } else {
    animEls.forEach(el => el.classList.add('visible'));
  }
});
