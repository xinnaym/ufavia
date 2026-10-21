/**
 * UFAvia — Личный кабинет (JavaScript)
 * Анимации и визуальные эффекты
 */
document.addEventListener('DOMContentLoaded', function () {
  const animEls = document.querySelectorAll('.anim');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.08 });
    animEls.forEach(el => io.observe(el));
  } else {
    animEls.forEach(el => el.classList.add('visible'));
  }
});
