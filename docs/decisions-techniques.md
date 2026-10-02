# Décisions techniques — revue du 2 octobre 2026

Ces décisions décrivent les ajustements réalisés à cette date ; elles ne reconstituent pas artificiellement une phase de conception antérieure.

## Extraire les formulaires les plus dupliqués

Les pages d’ajout et modification des menus/plats répétaient SQL, validation et HTML. Les conserver séparées faisait diverger les contrôles. Une migration complète vers un framework aurait élargi le périmètre sans besoin fonctionnel. Le choix est une extraction progressive : routes `Public/`, orchestration `Controllers/`, règles et persistance `Services/`, vues `Templates/forms/`. Les contrôleurs imposent le rôle avant l’accès aux données. La validation serveur reste indépendante des attributs HTML.

## Préserver les saisies et associer les erreurs

Un rejet ne doit pas effacer le travail de la personne. Les valeurs originales sont conservées pour l’affichage puis échappées à la sortie. Les valeurs normalisées sont seules envoyées à SQL après validation complète. Le résumé focalisable renvoie aux champs ; le détail ne dépend pas uniquement de la couleur. Le test serveur contourne les validations du navigateur pour vérifier ce contrat.

## Regrouper les écritures liées

Un plat et sa liste d’allergènes forment une modification unique. Une transaction et le verrouillage du plat évitent les associations partiellement enregistrées. Les contraintes uniques SQL complètent la validation. Le test de rollback déclenche une vraie erreur de base après la première écriture, et contrôle qu’aucun plat partiel ne subsiste.

## Calculer localement les jours fériés

Le délai matériel est un calcul métier, pas une dépendance à une API disponible à chaque commande. Les dates fixes et mobiles sont calculées localement et comparées aux calendriers officiels 2026/2027. Les fixtures comportent leur provenance. Une évolution réglementaire demanderait une mise à jour explicite de cette règle.

## Conserver les limites visibles

La ville contrôle la gratuité et une distance positive est exigée hors Bordeaux ; aucune précision de géocodage n’est revendiquée. Les emails sont envoyés après validation SQL pour ne pas annuler une commande en cas de panne SMTP. Une outbox serait la prochaine étape pour garantir une reprise durable. Les scans axe complètent les tests clavier et HTML, mais ne remplacent pas un lecteur d’écran ou un audit RGAA manuel.
