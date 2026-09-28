<?php
require_once __DIR__ . '/OrderRules.php';

final class OrderService
{
    public function __construct(private PDO $pdo) {}

    private function transaction(callable $operation): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $value = $operation();
            $this->pdo->commit();
            return $value;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function order(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM commandes WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new DomainException('Commande introuvable.');
        }
        return $order;
    }

    private function history(int $id, string $status, int $actor): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO historique_statuts (commande_id, statut, modifie_par) VALUES (?, ?, ?)');
        $stmt->execute([$id, $status, $actor]);
    }

    public function create(int $userId, int $menuId, array $input): int
    {
        return $this->transaction(function () use ($userId, $menuId, $input): int {
            $stmt = $this->pdo->prepare('SELECT * FROM menus WHERE id = ? FOR UPDATE');
            $stmt->execute([$menuId]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$menu || !(int) $menu['actif'] || (int) $menu['stock_disponible'] <= 0) {
                throw new DomainException('Ce menu n’est plus disponible.');
            }
            $data = validateOrderInput($input, $menu);
            if (isset($input['_quoted_total']) && round((float) $input['_quoted_total'] * 100) !== round($data['prix_total'] * 100)) {
                throw new DomainException('Le tarif du menu a changé. Vérifiez un nouveau récapitulatif avant de confirmer.');
            }
            $stmt = $this->pdo->prepare("INSERT INTO commandes (user_id, menu_id, nb_personnes, prix_total, date_prestation, heure_prestation, lieu_prestation, adresse_prestation, distance_km, frais_livraison, remise_pourcentage, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en attente')");
            $stmt->execute([$userId, $menuId, $data['nb_personnes'], $data['prix_total'], $data['date_prestation'], $data['heure_prestation'], $data['lieu_prestation'], $data['adresse_prestation'], $data['distance_km'], $data['frais_livraison'], $data['remise_pourcentage']]);
            $id = (int) $this->pdo->lastInsertId();
            $stmt = $this->pdo->prepare('UPDATE menus SET stock_disponible = stock_disponible - 1 WHERE id = ? AND stock_disponible > 0');
            $stmt->execute([$menuId]);
            if ($stmt->rowCount() !== 1) {
                throw new DomainException('Le stock de ce menu est épuisé.');
            }
            $this->history($id, 'en attente', $userId);
            return $id;
        });
    }

    public function update(int $id, int $userId, array $input): void
    {
        $this->transaction(function () use ($id, $userId, $input): void {
            $order = $this->order($id);
            if ((int) $order['user_id'] !== $userId || $order['statut'] !== 'en attente') {
                throw new DomainException('Cette commande ne peut plus être modifiée.');
            }
            $stmt = $this->pdo->prepare('SELECT * FROM menus WHERE id = ? FOR UPDATE');
            $stmt->execute([$order['menu_id']]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);
            $data = validateOrderInput($input, $menu);
            $stmt = $this->pdo->prepare('UPDATE commandes SET nb_personnes = ?, prix_total = ?, date_prestation = ?, heure_prestation = ?, lieu_prestation = ?, adresse_prestation = ?, distance_km = ?, frais_livraison = ?, remise_pourcentage = ? WHERE id = ?');
            $stmt->execute([$data['nb_personnes'], $data['prix_total'], $data['date_prestation'], $data['heure_prestation'], $data['lieu_prestation'], $data['adresse_prestation'], $data['distance_km'], $data['frais_livraison'], $data['remise_pourcentage'], $id]);
        });
    }

    public function cancel(int $id, int $actor, bool $staff = false, string $contact = '', string $reason = ''): void
    {
        $this->transaction(function () use ($id, $actor, $staff, $contact, $reason): void {
            $order = $this->order($id);
            if (in_array($order['statut'], ['annulée', 'terminée'], true)) {
                throw new DomainException('Cette commande est déjà clôturée.');
            }
            if (!$staff && ((int) $order['user_id'] !== $actor || $order['statut'] !== 'en attente')) {
                throw new DomainException('Cette commande ne peut plus être annulée.');
            }
            if ($staff && (!in_array($contact, ['Téléphone', 'Email', 'SMS'], true) || trim($reason) === '' || mb_strlen($reason) > 3000)) {
                throw new DomainException('Indiquez le contact effectué et un motif d’annulation (3 000 caractères maximum).');
            }
            $stmt = $this->pdo->prepare("UPDATE commandes SET statut = 'annulée', date_annulation = NOW(), mode_contact_annulation = ?, motif_annulation = ? WHERE id = ?");
            $stmt->execute([$staff ? $contact : null, $staff ? trim($reason) : null, $id]);
            $stmt = $this->pdo->prepare('UPDATE menus SET stock_disponible = stock_disponible + 1 WHERE id = ?');
            $stmt->execute([$order['menu_id']]);
            $this->history($id, 'annulée', $actor);
        });
    }

    public function transition(int $id, int $actor, string $status): void
    {
        $this->transaction(function () use ($id, $actor, $status): void {
            $order = $this->order($id);
            if (!in_array($status, allowedOrderTransitions($order['statut']), true)) {
                throw new DomainException('Cette transition n’est pas autorisée depuis l’état actuel de la commande.');
            }
            if ($status === 'en attente du retour de matériel') {
                $stmt = $this->pdo->prepare('UPDATE commandes SET statut = ?, date_debut_attente_retour = NOW(), materiel_retourne = 0 WHERE id = ?');
            } else {
                $stmt = $this->pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
            }
            $stmt->execute([$status, $id]);
            $this->history($id, $status, $actor);
        });
    }

    public function returnEquipment(int $id, int $actor): void
    {
        $this->transaction(function () use ($id, $actor): void {
            $order = $this->order($id);
            if ($order['statut'] !== 'en attente du retour de matériel' || (int) $order['materiel_retourne'] || !$order['date_debut_attente_retour']) {
                throw new DomainException('Cette commande n’attend pas de retour de matériel.');
            }
            $late = calculerJoursOuvres($order['date_debut_attente_retour'], date('Y-m-d H:i:s')) > 10 ? 600 : 0;
            $stmt = $this->pdo->prepare("UPDATE commandes SET materiel_retourne = 1, date_retour_materiel = NOW(), frais_retard_materiel = ?, statut = 'terminée' WHERE id = ?");
            $stmt->execute([$late, $id]);
            $this->history($id, 'terminée', $actor);
        });
    }
}
