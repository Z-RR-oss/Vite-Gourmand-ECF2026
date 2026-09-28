'use strict';
document.documentElement.classList.add('js-enabled');

const burger = document.querySelector('.burger-menu');
const navigation = document.querySelector('#main-navigation');
if (burger && navigation) {
    const closeNavigation = () => {
        navigation.classList.remove('menu-open');
        burger.setAttribute('aria-expanded', 'false');
    };
    burger.addEventListener('click', () => {
        const opened = navigation.classList.toggle('menu-open');
        burger.setAttribute('aria-expanded', String(opened));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && navigation.classList.contains('menu-open')) {
            closeNavigation();
            burger.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (!navigation.contains(event.target) && !burger.contains(event.target)) closeNavigation();
    });
}

// Construire les cartes avec textContent évite d'interpréter du contenu du catalogue comme du HTML.
function menuCard(menu) {
    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    };
    const href = `menu.php?id=${Number(menu.id)}`;
    const article = element('article', 'menu-card');
    const imageLink = element('a', 'menu-image-link');
    imageLink.href = href;
    imageLink.tabIndex = -1;
    imageLink.setAttribute('aria-hidden', 'true');
    const image = element('img');
    image.src = /^(?:assets\/[a-zA-Z0-9_./-]+|[a-zA-Z0-9_-]+)\.(?:webp|png|jpg|jpeg|svg)$/i.test(menu.image) && !menu.image.includes('..') ? menu.image : 'assets/images/table-partage.svg';
    image.alt = '';
    image.width = 600;
    image.height = 420;
    image.loading = 'lazy';
    imageLink.append(image, element('span', 'menu-theme', menu.theme));
    const body = element('div', 'menu-card-body');
    const meta = element('div', 'menu-card-meta');
    meta.append(element('span', '', menu.regime), element('span', '', `${Number(menu.nb_personnes_min)} pers. minimum`));
    const heading = element('h3');
    const link = element('a', '', menu.titre);
    link.href = href;
    heading.append(link);
    const bottom = element('div', 'menu-card-bottom');
    const price = element('div');
    price.append(element('strong', '', new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(menu.prix))), element('small', '', `pour ${Number(menu.nb_personnes_min)} personnes`));
    const arrow = element('a', 'round-link', '↗');
    arrow.href = href;
    arrow.setAttribute('aria-label', `Découvrir ${menu.titre}`);
    bottom.append(price, arrow);
    body.append(meta, heading, element('p', '', menu.description), bottom);
    if (Number(menu.stock_disponible) <= 0) body.append(element('span', 'badge badge-muted', 'Momentanément indisponible'));
    article.append(imageLink, body);
    return article;
}

const filtersForm = document.querySelector('#menu-filters');
const menuContainer = document.querySelector('#menus');
const resultsStatus = document.querySelector('#menu-results-status');
if (filtersForm && menuContainer && resultsStatus) {
    let activeController;
    let debounceTimer;
    let requestVersion = 0;
    async function loadMenus() {
        activeController?.abort();
        const version = ++requestVersion;
        if (!filtersForm.reportValidity()) return;
        activeController = new AbortController();
        const params = new URLSearchParams(new FormData(filtersForm));
        resultsStatus.textContent = 'Recherche des menus…';
        menuContainer.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(`api-menu.php?${params.toString()}`, { signal: activeController.signal, headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'La recherche n’a pas pu aboutir.');
            if (!Array.isArray(data)) throw new Error('La réponse du catalogue est indisponible.');
            if (version !== requestVersion) return;
            const fragment = document.createDocumentFragment();
            data.forEach((menu) => fragment.append(menuCard(menu)));
            if (!data.length) {
                const empty = document.createElement('p');
                empty.className = 'empty-state';
                empty.textContent = 'Aucun menu ne correspond à votre recherche. Essayez d’autres filtres.';
                fragment.append(empty);
            }
            menuContainer.replaceChildren(fragment);
            resultsStatus.textContent = `${data.length} menu(s) à découvrir`;
        } catch (error) {
            if (error.name !== 'AbortError' && version === requestVersion) resultsStatus.textContent = `${error.message} Les derniers résultats restent affichés.`;
        } finally {
            if (version === requestVersion) menuContainer.removeAttribute('aria-busy');
        }
    }
    filtersForm.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(debounceTimer);
        loadMenus();
    });
    filtersForm.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadMenus, 250);
    });
    filtersForm.addEventListener('change', () => {
        clearTimeout(debounceTimer);
        loadMenus();
    });
    filtersForm.addEventListener('reset', () => {
        clearTimeout(debounceTimer);
        // Réinitialiser aussi les valeurs venues de l’URL, pas seulement les valeurs modifiées.
        requestAnimationFrame(() => {
            filtersForm.querySelectorAll('input, select').forEach((input) => { input.value = ''; });
            loadMenus();
        });
    });
}
