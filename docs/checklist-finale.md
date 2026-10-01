# Checklist finale confrontée au sujet

Statuts : **OK** = périmètre précisément vérifié dans la colonne test; **BLOQUÉE** = condition externe empêchant la preuve finale; **À FAIRE** = action restante. Une ligne code OK ne signifie pas que l’intégration distante voisine est validée. Le site public, MySQL et Firebase sont validés depuis alwaysdata. Les deux tâches sont exécutées avec succès et la réception du mail est confirmée. L’intégration feature → develop → main est réalisée et publiée.

Sources : Prompt_Maitre_Vite_Gourmand_Transfert_IA.pdf (12 pages) relu intégralement et Sujet Studi.pdf (12 pages, annexe visuelle comprise). Recette locale du 28 septembre 2026, complétée par la recette Firebase réelle du 29 septembre 2026.

| Exigence | Statut | Fichier(s) | Test effectué | Commentaire |
| --- | --- | --- | --- | --- |
| Sources de vérité relues intégralement | OK | docs/audit-initial.md | 12 pages du transfert + 12 pages du sujet, annexe MCD inspectée | Le sujet prime sur le transfert historique; la demande actuelle autorise le développement. |
| Audit dépôt, branches et données existantes | OK | docs/audit-initial.md | status, branches, graphe, schéma actif | Branche feature/statistiques-admin; données personnelles conservées. |
| Présentation Julie/José, Bordeaux, 25 ans et équipe | OK | Public/index.php | HTTP + capture accueil | Vérifié localement. |
| Avis validés sur accueil uniquement | OK | Public/index.php; Public/changer-avis.php | HTTP : avis en attente masqué puis validé affiché | Vérifié localement. |
| Navigation publique, client, employé et admin | OK | Templates/layout.php | Trois connexions + captures et droits directs | Vérifié localement. |
| Horaires lundi-dimanche issus de SQL et footer légal | OK | Templates/layout.php | Modification horaire -> footer; liens parcourus | Vérifié localement. |
| Mentions et CGV identifiées comme démonstration | OK | Public/mentions-legales.php; Public/cgv.php | Lecture et capture | Aucune identité juridique réelle inventée; compléter avant exploitation. |
| Catalogue titre, description, minimum et prix | OK | Public/menus.php; Templates/menu-list.php | HTTP + navigateur | Vérifié localement. |
| Détail, galerie, thème, régime, plats et allergènes | OK | Public/menu.php | HTTP + capture; relations SQL | Vérifié localement. |
| Conditions, délai et stock visibles | OK | Public/menu.php; Services/OrderRules.php | Détail et rejets serveur | Vérifié localement. |
| Filtres maximum, fourchette, thème, régime, convives | OK | Templates/catalogue.php; Public/api-menu.php | HTTP sur chaque filtre | Vérifié localement. |
| Actualisation dynamique sans rechargement | OK | Public/scripts.js | Navigateur : Vegan puis réinitialisation sans navigation | Vérifié localement. |
| Menus inactifs absents, stock nul non commandable | OK | Public/api-menu.php; Public/commander.php | HTTP actif=0 et stock=0 | Vérifié localement. |
| Inscription avec toutes les coordonnées | OK | Public/register.php; Config/security.php | HTTP champs, email invalide et dupliqué | Vérifié localement. |
| Mot de passe fort, hash et rôle utilisateur forcé | OK | Config/security.php; Public/register.php | Mot de passe faible et rôle injecté refusés | Vérifié localement. |
| Connexion, session renouvelée, trois rôles | OK | Public/login.php; Config/auth.php | HTTP trois comptes, mot de passe faux | Vérifié localement. |
| Employé désactivé et sessions révoquées | OK | Config/auth.php; Public/desactiver-employe.php | Session ouverte refusée après désactivation | Vérifié localement. |
| Reset aléatoire haché, une heure, usage unique | OK | Public/mot-de-passe-oublie.php; Public/reinitialiser-mot-de-passe.php | Jetons valides/utilisés/expirés; ancienne session révoquée | Vérifié localement. |
| Commande invité -> connexion, menu conservé | OK | Config/auth.php; Public/commander.php | HTTP redirection et formulaire présélectionné | Vérifié localement. |
| Coordonnées préremplies, adresse/date/heure/lieu | OK | Public/commander.php | HTTP + inspection formulaire | Vérifié localement. |
| Prix minimum, prorata et remise dès minimum+5 | OK | Services/OrderRules.php | 25 assertions dont seuils et calculs | Prix forfaitaire explicitement confirmé par le sujet p.4. |
| Livraison hors Bordeaux 5 € + 0,59 €/km | OK | Services/OrderRules.php | Tests distance zéro,20km,négative | Distance déclarative, pas de géocodage; 0 pour Bordeaux. |
| Récapitulatif avant confirmation et prix revalidé | OK | Public/commander.php; Services/OrderService.php | Devis, prix changé, double confirmation | Vérifié localement. |
| Stock transactionnel et dernière disponibilité | OK | Services/OrderService.php | Deux processus concurrents : une seule réussite | Vérifié localement. |
| Commandes client détaillées et historique horodaté | OK | Public/mes-commandes.php | HTTP, captures et SQL historique | Vérifié localement. |
| Modification en attente seulement, menu inchangé | OK | Public/modifier-commande.php; Services/OrderService.php | Propriétaire, intrus, après acceptation | Vérifié localement. |
| Annulation client et stock restauré une fois | OK | Public/supprimer-commande.php; Services/OrderService.php | Annulation répétée + contrôle SQL | Vérifié localement. |
| Profil modifiable, rôle protégé et email unique | OK | Public/mon-profil.php | HTTP rôle injecté et balises échappées | Vérifié localement. |
| Avis 1..5, commentaire, fin requise, un par commande | OK | Public/laisser-avis.php; vite_gourmand.sql.sql | Avis avant fin, doublon et XSS | Vérifié localement. |
| Validation/refus avis par équipe | OK | Public/admin-avis.php; Public/changer-avis.php | HTTP modération, GET et CSRF | Vérifié localement. |
| Filtres commandes par statut ou client | OK | Public/admin-commandes.php | HTTP filtres avec PDO natif | Vérifié localement. |
| Transitions cohérentes et historique atomique | OK | Services/OrderRules.php; Services/OrderService.php | Chaîne complète, saut refusé, états finaux | Vérifié localement. |
| Annulation employé après contact et motif | OK | Public/annuler-commande-employe.php | Motif/contact requis; SQL conservé | Vérifié localement. |
| Livraison sans prêt -> terminée | OK | Services/OrderService.php | Assertion métier dédiée | Vérifié localement. |
| Prêt : mail immédiat, délai dix jours,600 € | OK | Services/OrderNotifications.php; Services/OrderRules.php | SMTP capturé, frontière dix jours et week-ends | Jours ouvrés lundi-vendredi sans fériés, hors jour initial. |
| Retour matériel clôture, date et frais | OK | Public/retour-materiel.php; Services/OrderService.php | Retour HTTP, retard et double retour | Vérifié localement. |
| Rappel retard idempotent en exécution normale | OK | Scripts/verifier-retards-materiel.php | Deux exécutions, un email et marqueur | Crash SMTP/commit : fenêtre de doublon documentée. |
| CRUD menus, stock, délai, actif, suppression sûre | OK | Public/ajouter-menu.php; Public/modifier-menu.php; Public/supprimer-menu.php | Création/modification/suppression; référencé -> désactivé | Vérifié localement. |
| Galerie configurable par employé/admin | OK | Services/MenuImages.php; Public/gerer-menu-images.php | 4 contrôles uploads | Raster réencodé PNG; SVG téléversé interdit. |
| CRUD plats, types et allergènes | OK | Public/ajouter-plat.php; Public/modifier-plat.php; Public/supprimer-plat.php | HTTP création/modification/suppression | Vérifié localement. |
| Plats multi-menus et ordre | OK | Public/gerer-menu-plats.php; vite_gourmand.sql.sql | HTTP pivot/ordre, schéma composite | Vérifié localement. |
| Horaires création/modification/suppression | OK | Public/modifier-horaire.php | HTTP ouvert/fermé, heure invalide, suppression | Vérifié localement. |
| Employés créés par admin, rôle imposé | OK | Public/ajouter-employe.php | HTTP rôle injecté; email sans mot de passe | Vérifié localement. |
| Aucune création admin depuis interface | OK | Public/register.php; Public/ajouter-employe.php | Rôles forcés et revue code | Vérifié localement. |
| Contact titre/email/description + PHPMailer | OK | Public/contact.php; Config/mail.php | Validation et SMTP capturé avec Reply-To | Vérifié localement. |
| SQL complet : douze tables, FK,index,UNIQUE,INSERT | OK | vite_gourmand.sql.sql; Scripts/migrer-schema.php | Import propre + recette HTTP sur base importée | Vérifié localement. |
| Véritable accès NoSQL REST, sans JSON local | OK | Repositories/StatisticsRepository.php; Config/nosql.php | 41 vérifications locales; revue chemin GET/PUT | Code réel Firebase; tests transport explicitement simulés. |
| Agrégation MySQL -> instantané NoSQL | OK | Services/StatisticsService.php; Scripts/synchroniser-statistiques.php | Agrégats/filtres/centimes/OAuth testés localement | Vérifié localement. |
| Synchro Firebase réelle et lecture distante | OK | docs/nosql.md; tests/statistics-live.php | PUT réel puis test distant code0, 5 menus et 2 jours | Clé privée hors dépôt; données fictives uniquement; lecture anonyme HTTP401. |
| Graphique, commandes/menu, CA et filtres sur données Firebase réelles | OK | Public/admin-statistiques.php; Public/assets/js/statistics.js | Navigateur : 3 commandes/19 convives/120 €; menu Vegan 1/9/0 €; journée 2/15/0 €; graphique CA | Capture statistiques-firebase-reel.png; PHP local connecté à la vraie base Firebase. |
| Emails métier et absence de mot de passe dans email employé | OK | Config/mail.php; Services/OrderNotifications.php | Huit familles SMTP capturées | Pas de mail réel envoyé pendant la recette. |
| Délivrabilité des emails sur hébergement final | OK | docs/deploiement.md | STARTTLS, journal Envoyé sans rebond, réception confirmée par la propriétaire | Un message réel autorisé; huit modèles métier validés par capture locale. |
| CSRF central, mutations POST et requêtes préparées | OK | Config/bootstrap.php; Config/security.php; Public | CSRF absent/falsifié, GET405, SQLi, rôles/IDs | Vérifié localement. |
| Échappement XSS, validation serveur, erreurs privées | OK | Config/security.php; Config/database.php; Public/scripts.js | Balises échappées, valeurs invalides, revue logs | Vérifié localement. |
| Secrets ignorés, exemples fictifs, racine HTTP Public | OK | .gitignore; Config/*.example.php; Public/.htaccess | Scan sans valeurs; fichiers privés HTTP403/404 | Faux positifs du scan : chaînes de log et paramètre SQL; aucun secret identifié dans livrable. |
| Design system et cohérence des pages principales | OK | Public/style.css; Templates/layout.php; docs/captures | Revue visuelle des 22 pages | Vérifié localement. |
| Responsive mobile/tablette/desktop | OK | docs/recette-responsive.json | 72 mesures à390/768/1440; contact320 sans débordement | Vérifié localement. |
| Accessibilité pragmatique : lang,h1,labels,alt,focus,clavier | OK | Templates/layout.php; Public/style.css; Public/scripts.js | Lien évitement, focus visible, Menu Entrée/Échap, mesures DOM | Contrastes principaux >4,5:1; pas de certification RGAA exhaustive. |
| Évaluation RGAA exhaustive, lecteur d’écran et zoom navigateur | À FAIRE | docs/accessibilite.md | 473 contrôles HTML ciblés et mesures de présentation effectués ; VoiceOver bloqué par les permissions Mac | Aucun taux de conformité déclaré. |
| JavaScript séparé, fetch et absence erreur console observée | OK | Public/scripts.js; Public/assets/js/statistics.js | node --check et journaux navigateur vides | Vérifié localement. |
| Architecture progressive, services POO et validations partagées | OK | Services; Repositories; docs/documentation-technique.md | Revue responsabilités et diagramme classes | Vérifié localement. |
| Composer et lock conservés, PHPMailer | OK | composer.json; composer.lock | validate réussi; audit sans vulnérabilité signalée | Avertissement licence non renseignée; aucun choix juridique inventé. |
| README et installation locale reproductible | OK | README.md; docs/deploiement.md | Procédure + import et démarrage isolés | Vérifié localement. |
| Manuel PDF avec comptes et parcours | OK | docs/manuel-utilisateur.pdf; docs/manuel-utilisateur.md | 4 pages rendues et inspectées | Vérifié localement. |
| Charte PDF palette,typographies,logo,composants | OK | docs/charte-graphique.pdf; docs/charte-graphique.md | 9 pages rendues et inspectées | Vérifié localement. |
| 3 desktop +3 mobile : wireframes ET mockups | OK | docs/wireframes; docs/maquettes | 6 SVG/PNG wireframes et6 mockups PNG intégrés au PDF | Wireframes documentaires reconstruits depuis la version finale. |
| MCD,classes,cas utilisation,séquences | OK | output/pdf/mcd-vite-gourmand.pdf; docs/mcd.md; docs/diagrammes.md | MCD conceptuel en 3 vues PDF/SVG, distinct du schéma SQL ; UML Mermaid | Cardinalités confrontées au SQL et règles applicatives ; PDF rendu et inspecté. |
| Gestion projet,backlog,user stories,priorités | À FAIRE | docs/gestion-projet.md | Document et lien fournis ; tableau distant consulté, carte maquettes corrigée | Reclassement des 19 tâches livrées et accès du jury au tableau à finaliser. |
| Documentation technique et déploiement | OK | docs/documentation-technique.md; docs/deploiement.md | Configuration,extensions,permissions,cron,rollback | Vérifié localement. |
| Plan et preuves des tests | OK | docs/plan-tests.md; tests | 196 contrôles automatisés, captures et recette | Vérifié localement. |
| Lint PHP,JS,diff,liens,logs | OK | docs/plan-tests.md | 68 PHP OK;2 JS OK;diff check;22 ressources sans erreur | Ancienne erreur GD corrigée;503 Firebase attendu. |
| Application publique déployée et fonctionnelle | OK | docs/deploiement.md; docs/recette-alwaysdata.json | 29 contrôles HTTPS, trois rôles, Firebase et fichiers privés | alwaysdata Free 0 €, app/Public, données fictives, mots de passe publics remplacés. |
| Cron installé et recette finale en ligne | OK | Scripts/verifier-retards-materiel.php; Scripts/synchroniser-statistiques.php | Jobs #33622/#33623 actifs, exécutions automatiques code 0; tâche matériel à 09:00:23 | Statistiques toutes les 15 minutes; matériel chaque jour à 09:00. |
| Workflow feature vers develop puis main | OK | docs/rapport-final.md | Fusion develop 3397f6e puis main 3d23419; ascendance et arbres vérifiés | Historique conservé; aucune modification du code testé ni poussée forcée. |
