/* Réservé au routeur de recette local, exclu de l'application déployée. */
window.addEventListener('load', async () => {
    const output = document.createElement('pre');
    output.id = 'axe-report';
    output.setAttribute('aria-label', 'Résultat de la recette axe');
    try {
        const results = await axe.run(document, {
            runOnly: {
                type: 'tag',
                values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'],
            },
        });
        output.textContent = JSON.stringify(
            {
                page: location.pathname,
                version: results.testEngine.version,
                viewport: { width: innerWidth, height: innerHeight },
                violations: results.violations.map(({ id, impact, description, nodes }) => ({
                    id,
                    impact,
                    description,
                    nodes: nodes.map(({ target, failureSummary }) => ({ target, failureSummary })),
                })),
                incomplete: results.incomplete.map(({ id, nodes }) => ({
                    id,
                    targets: nodes.map((n) => n.target),
                })),
                passedRules: results.passes.map(({ id }) => id),
            },
            null,
            2,
        );
    } catch (error) {
        output.textContent = JSON.stringify({ error: error.message });
    }
    // Le rapport est ajouté après le scan afin de ne pas modifier son échantillon.
    output.style.cssText =
        'white-space:pre-wrap;overflow-wrap:anywhere;background:white;color:black;padding:20px';
    document.body.append(output);
});
