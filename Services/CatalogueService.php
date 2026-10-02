<?php

declare(strict_types=1);

require_once __DIR__ . '/CatalogueValidation.php';

/** Persistance du catalogue ; les contrôleurs vérifient le rôle avant tout appel. */
final class CatalogueService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function menu(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM menus WHERE id = ?');
        $query->execute([$id]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function allergens(): array
    {
        return $this->pdo->query('SELECT id, nom FROM allergenes ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dish(int $id): ?array
    {
        $query = $this->pdo->prepare('SELECT * FROM plats WHERE id = ?');
        $query->execute([$id]);
        $dish = $query->fetch(PDO::FETCH_ASSOC);
        if (!$dish) {
            return null;
        }
        $query = $this->pdo->prepare('SELECT allergene_id FROM plat_allergene WHERE plat_id = ?');
        $query->execute([$id]);
        $dish['allergenes'] = array_map('strval', $query->fetchAll(PDO::FETCH_COLUMN));
        return $dish;
    }

    public function saveMenu(array $input, ?int $id = null): int
    {
        $data = validateMenuInput($input);
        if ($id === null) {
            $sql = 'INSERT INTO menus (titre, description, theme, regime, conditions_menu, prix,
                nb_personnes_min, stock_disponible, delai_commande_heures, actif)
                VALUES (:titre, :description, :theme, :regime, :conditions_menu, :prix,
                :nb_personnes_min, :stock_disponible, :delai_commande_heures, :actif)';
        } else {
            $sql = 'UPDATE menus SET titre = :titre, description = :description, theme = :theme,
                regime = :regime, conditions_menu = :conditions_menu, prix = :prix,
                nb_personnes_min = :nb_personnes_min, stock_disponible = :stock_disponible,
                delai_commande_heures = :delai_commande_heures, actif = :actif WHERE id = :id';
            $data['id'] = $id;
        }
        $this->pdo->prepare($sql)->execute($data);
        return $id ?? (int) $this->pdo->lastInsertId();
    }

    public function saveDish(array $input, ?int $id = null): int
    {
        $data = validateDishInput($input, $this->allergens());
        $this->pdo->beginTransaction();
        try {
            if ($id === null) {
                $this->pdo->prepare('INSERT INTO plats (nom, description, type_plat) VALUES (?, ?, ?)')
                    ->execute([$data['nom'], $data['description'], $data['type_plat']]);
                $id = (int) $this->pdo->lastInsertId();
            } else {
                // Sérialiser les modifications du même plat et de ses associations.
                $lock = $this->pdo->prepare('SELECT id FROM plats WHERE id = ? FOR UPDATE');
                $lock->execute([$id]);
                if (!$lock->fetchColumn()) {
                    throw new DomainException('Plat introuvable.');
                }
                $this->pdo->prepare('UPDATE plats SET nom = ?, description = ?, type_plat = ? WHERE id = ?')
                    ->execute([$data['nom'], $data['description'], $data['type_plat'], $id]);
                $this->pdo->prepare('DELETE FROM plat_allergene WHERE plat_id = ?')->execute([$id]);
            }
            $ids = $data['allergenes'];
            // L'unicité SQL évite aussi les doublons entre deux requêtes concurrentes.
            $insert = $this->pdo->prepare('INSERT INTO allergenes (nom) VALUES (?) ON DUPLICATE KEY UPDATE id = id');
            $select = $this->pdo->prepare('SELECT id FROM allergenes WHERE nom = ?');
            foreach ($data['nouveaux_allergenes'] as $name) {
                $insert->execute([$name]);
                $select->execute([$name]);
                $ids[] = (int) $select->fetchColumn();
            }
            $link = $this->pdo->prepare('INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (?, ?)');
            foreach (array_unique($ids) as $allergenId) {
                $link->execute([$id, $allergenId]);
            }
            $this->pdo->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
