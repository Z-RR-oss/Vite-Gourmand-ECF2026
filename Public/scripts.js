const burger = document.querySelector('.burger-menu');
const navLinks = document.querySelector('.navbar-links');

burger.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('menu-open');

    burger.textContent = isOpen ? '✕' : '☰';
});