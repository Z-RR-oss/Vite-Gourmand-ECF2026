# Audit initial — 28 septembre 2026

## Références lues intégralement

- `Prompt_Maitre_Vite_Gourmand_Transfert_IA.pdf`, 12 pages, retrouvé sur le Bureau.
- `Sujet Studi.pdf`, 12 pages, retrouvé dans Téléchargements. L'annexe MCD en page 10 a également été inspectée visuellement.
- Demande de finalisation fournie dans cette conversation.

Le sujet Studi définit les besoins; le dépôt définit l'état du code. Le transfert décrit une version plus ancienne (trois tables). Le schéma actif comporte aujourd'hui douze tables. La demande explicite de réalisation autonome remplace la posture pédagogique ancienne du transfert. Les PDF privés ne sont pas recopiés dans le dépôt public.

## État Git constaté avant modification

Arbre propre; `feature/statistiques-admin`, `develop` et `origin/develop` à `e7f6cee`. Historique feature -> PR -> develop confirmé. Aucune fusion vers main réalisée. Aucun changement préalable de l'utilisateur à écraser.

## Écarts constatés et ordre de correction

| Priorité | Écart observé | Traitement prévu |
|---|---|---|
| P0 | Changement statut/avis et déconnexion via GET, aucun CSRF | POST, jeton central, revalidation des sessions et rôles |
| P0 | Transitions arbitraires, lecture statut avant transaction | Service métier et verrous SQL |
| P0 | Dump absent de password_reset_tokens et notifications matériel | Dump complet, import sur base distincte |
| P0 | Statistiques NoSQL absentes | Firebase REST, agrégation CLI, dashboard lecture NoSQL |
| P0 | Mails attente matériel / création employé non couverts | Notifications au bon événement, aucun mot de passe envoyé |
| P1 | Formulaire contact et pages légales absents | Pages réelles, PHPMailer, mentions explicitement démonstration |
| P1 | Galerie menus non administrable | Édition de chemins locaux et textes alternatifs |
| P1 | PDO expose erreur, configuration liée au Mac | Environnement/local ignoré, erreurs génériques |
| P1 | Styles/nav répétés et formulaires peu accessibles | Layout commun, design system, labels, responsive |
| P1 | Données SQL anciennes incohérentes (statut livrée, minimum ignoré) | Jeu fictif cohérent et comptes connus |
| P1 | Aucun livrable final ni preuve de tests | Manuel, charte, maquettes, diagrammes, plan et rapport |
| Externe | Aucun compte Firebase/hébergement fourni | Code complet et procédure; intégration réelle à valider |

## Hypothèses métier explicites

- Le prix du menu correspond au minimum de personnes (confirmé p. 4 du sujet). Prix proportionnel aux convives; remise 10 % dès minimum + 5; remise hors livraison.
- Bordeaux : distance 0 et livraison gratuite. Hors Bordeaux : distance déclarative et 5 € + 0,59 €/km. Pas de service de géocodage; vérification humaine nécessaire avant acceptation.
- Un stock représente une commande possible et non un nombre de couverts.
- Jours ouvrés calculés lundi-vendredi, hors jour de départ; jours fériés non déduits, conformément au périmètre fourni. Au-delà de dix jours : 600 €.
- Livré peut devenir terminé sans matériel; avec matériel, seul son retour peut terminer la commande.
- Le CA opérationnel est une convention de gestion documentée dans `nosql.md`, pas une comptabilité certifiée.

## Précautions de test

Base distincte `vite_gourmand_test_20260928`, serveur HTTP 127.0.0.1:8091 et capture SMTP 127.0.0.1:1025. Aucun courrier de test envoyé à un tiers. Aucun export de données personnelles de la base active.
