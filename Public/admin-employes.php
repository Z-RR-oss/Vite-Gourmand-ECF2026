<?php

require_once __DIR__ . '/../Config/database.php';

// Vérifier la connexion
requireLogin();

// Seul l'administrateur peut gérer les employés
requireRole('admin');

// Récupérer uniquement les comptes employés
$sql = "
    SELECT
        id,
        nom,
        prenom,
        email,
        gsm,
        adresse,
        actif,
        created_at
    FROM users

    WHERE role = 'employe'

    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Gestion des employés'); ?>
<section class="content-panel">
    <h1>
        Gestion des employés
    </h1>
    <a
        class="ajouter"
        href="ajouter-employe.php"
    >
        + Créer un compte employé
    </a>
    <?php if (empty($employes)): ?>
        <p>
            Aucun compte employé.
        </p>
    <?php else: ?>
        <?php foreach ($employes as $employe): ?>
            <article class="employe">
                <h2>
                    <?= htmlspecialchars($employe['prenom'] . ' ' . $employe['nom']) ?>
                </h2>
                <p>
                    <strong>Email :</strong>
                    <?= htmlspecialchars($employe['email']) ?>
                </p>
                <p>
                    <strong>Téléphone :</strong>
                    <?= htmlspecialchars($employe['gsm']) ?>
                </p>
                <p>
                    <strong>Adresse :</strong>
                    <?= htmlspecialchars($employe['adresse']) ?>
                </p>
                <p>
                    <strong>Créé le :</strong>
                    <?= htmlspecialchars($employe['created_at']) ?>
                </p>
                <?php if ((int) $employe['actif'] === 1): ?>
                    <span class="actif">
                        Compte actif
                    </span>
                <?php else: ?>
                    <span class="inactif">
                        Compte désactivé
                    </span>
                <?php endif; ?>
                <form
                    method="POST"
                    action="desactiver-employe.php"
                >
        <?= csrfInput() ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $employe['id'] ?>"
                    >
                    <?php if ((int) $employe['actif'] === 1): ?>
                        <input
                            type="hidden"
                            name="action"
                            value="desactiver"
                        >
                        <button
                            class="desactiver"
                            type="submit"
                        >
                            Désactiver le compte
                        </button>
                    <?php else: ?>
                        <input
                            type="hidden"
                            name="action"
                            value="activer"
                        >
                        <button
                            class="activer"
                            type="submit"
                        >
                            Réactiver le compte
                        </button>
                    <?php endif; ?>
                </form>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php renderFooter($pdo); ?>
