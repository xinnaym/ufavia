/**
 * UFAvia — Корзина билетов (JavaScript)
 * Анимации и визуальные эффекты
 */
document.addEventListener('DOMContentLoaded', function () {
  // Анимации при появлении блоков
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
