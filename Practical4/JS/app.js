// StudentHub Practical 4 — beginner-friendly UI interactions
const menuToggle = document.getElementById('menuToggle');
const nav = document.getElementById('primaryNav');
menuToggle?.addEventListener('click', () => {
  const open = nav.classList.toggle('open');
  menuToggle.setAttribute('aria-expanded', String(open));
});

document.querySelectorAll('.faq-toggle').forEach((button) => {
  button.addEventListener('click', () => {
    const answer = button.nextElementSibling;
    const expanded = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!expanded));
    answer.hidden = expanded;
  });
});

const modal = document.getElementById('eventModal');
document.getElementById('openModal')?.addEventListener('click', () => { modal.hidden = false; });
document.getElementById('closeModal')?.addEventListener('click', () => { modal.hidden = true; });
modal?.addEventListener('click', (event) => { if (event.target === modal) modal.hidden = true; });

const slides = ['Learn. Connect. Participate.', 'Discover campus events.', 'Make your campus experience count.'];
let slideIndex = 0;
const slideText = document.getElementById('slideText');
function showSlide() { if (slideText) slideText.textContent = slides[slideIndex]; }
document.getElementById('nextSlide')?.addEventListener('click', () => { slideIndex = (slideIndex + 1) % slides.length; showSlide(); });
document.getElementById('prevSlide')?.addEventListener('click', () => { slideIndex = (slideIndex - 1 + slides.length) % slides.length; showSlide(); });

document.getElementById('themeToggle')?.addEventListener('click', () => {
  document.body.classList.toggle('dark');
});

const notice = document.getElementById('notice');
if (notice) {
  window.setTimeout(() => { notice.hidden = true; }, 7000);
}
