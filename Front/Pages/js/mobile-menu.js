var navToggle = document.getElementById('nav-toggle');
var mobileMenu = document.getElementById('mobile-menu');
navToggle.addEventListener('click', function () {
var isOpen = mobileMenu.classList.toggle('is-open');
navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});
