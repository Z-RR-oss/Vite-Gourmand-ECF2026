const burger = document.querySelector('.burger-menu');
const navLinks = document.querySelector('.navbar-links');
const searchInput = document.querySelector('#search-menu');
const menuContainer = document.querySelector('#menus');


burger.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('menu-open');

    burger.textContent = isOpen ? '✕' : '☰';
});


searchInput.addEventListener('input', () => {

    const url = `api-menu.php?search=${searchInput.value}`;

    fetch(url)
        .then(response => response.json())
        .then(menus => {

            menuContainer.innerHTML = '';
            if (menus.length === 0) {
    menuContainer.innerHTML = '<p>Aucun menu trouvé.</p>';
    return;
}

            menus.forEach(menu => {
                menuContainer.innerHTML += `
                    <div>
                        <h1>${menu.titre}</h1>
                        <h2>${menu.prix} €</h2>
                        <p>${menu.description}</p>
                        <p>Minimum : ${menu.nb_personnes_min} personnes</p>
                        <a href="menu.php?id=${menu.id}">
                            Afficher le menu
                        </a>
                    </div>
                `;
            });

        });
});
