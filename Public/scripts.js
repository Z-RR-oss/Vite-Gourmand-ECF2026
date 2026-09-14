const burger = document.querySelector('.burger-menu');
const navLinks = document.querySelector('.navbar-links');
const searchInput = document.querySelector('#search-menu');
const menuContainer = document.querySelector('#menus');
const prixMinInput = document.querySelector('#prix-min');
const prixMaxInput = document.querySelector('#prix-max');
const themeSelect = document.querySelector('#theme');
const regimeSelect = document.querySelector('#regime');
const personnesInput = document.querySelector('#personnes');


burger.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('menu-open');

    burger.textContent = isOpen ? '✕' : '☰';
});


function chargerMenus() {

    const params = new URLSearchParams({
        search: searchInput.value,
        prix_min: prixMinInput.value,
        prix_max: prixMaxInput.value,
        theme: themeSelect.value,
        regime: regimeSelect.value,
        personnes: personnesInput.value
    });

    const url = `api-menu.php?${params.toString()}`;

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
}


searchInput.addEventListener('input', chargerMenus);
prixMinInput.addEventListener('input', chargerMenus);
prixMaxInput.addEventListener('input', chargerMenus);
themeSelect.addEventListener('change', chargerMenus);
regimeSelect.addEventListener('change', chargerMenus);
personnesInput.addEventListener('input', chargerMenus);