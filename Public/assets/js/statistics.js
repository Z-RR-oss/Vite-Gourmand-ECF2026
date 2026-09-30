'use strict';

(() => {
    const period = document.getElementById('periode');
    const from = document.getElementById('du');
    const to = document.getElementById('au');
    const updateDates = () => {
        const custom = period.value === 'personnalisee';
        from.disabled = !custom;
        to.disabled = !custom;
        from.required = custom;
        to.required = custom;
    };
    if (period && from && to) {
        period.addEventListener('change', updateDates);
        updateDates();
    }

    const metric = document.getElementById('chart-metric');
    const controls = document.getElementById('chart-controls');
    const summary = document.getElementById('chart-summary');
    if (!metric || !controls || !summary) return;

    const rows = Array.from(document.querySelectorAll('.chart-row'));
    const number = new Intl.NumberFormat('fr-FR');
    const currency = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
    // Sans JavaScript, le tableau et les barres initiaux restent consultables.
    controls.hidden = false;
    metric.addEventListener('change', () => {
        const revenue = metric.value === 'ca_centimes';
        const values = rows.map((row) =>
            Number(row.dataset[revenue ? 'ca_centimes' : 'commandes']),
        );
        const maximum = Math.max(1, ...values);
        rows.forEach((row, index) => {
            const bar = row.querySelector('meter');
            bar.max = maximum;
            bar.value = values[index];
            row.querySelector('.chart-value').textContent = revenue
                ? currency.format(values[index] / 100)
                : number.format(values[index]);
        });
        summary.textContent = `Comparaison ${revenue ? 'du chiffre d’affaires livré en euros' : 'du nombre de commandes hors annulations'}. Les valeurs exactes figurent dans le tableau ci-dessous.`;
    });
})();
