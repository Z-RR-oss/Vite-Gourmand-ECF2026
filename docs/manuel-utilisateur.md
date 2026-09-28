# Manuel utilisateur

Vite & Gourmand • ECF DWWM • Édition du 28 septembre 2026

## Présentation et accès

Julie et José proposent les menus de leur maison bordelaise, créée depuis 25 ans dans le scénario de l'épreuve. Le site permet de découvrir les menus, préparer une commande et suivre sa réalisation. Aucun paiement en ligne n'est effectué.

L'adresse locale est http://vite-gourmand.local. L'URL publique n'existe pas encore : hébergement et Firebase restent à configurer. Les écrans reproduits viennent d'une base de démonstration isolée.

## Comptes de démonstration

Après import du dump, utiliser Demo-Vg2026! avec quentin@example.com (client), charlie@example.com (employé), ou corentin@example.com (administrateur). Ces comptes sont exclusivement pédagogiques; l'exploitant doit les remplacer avant publication.

## Visiteur : découvrir et contacter

Depuis Nos menus, combiner recherche, budget minimum/maximum, thème, régime et convives. Les résultats changent sans rechargement. Réinitialiser retire les filtres. Les menus épuisés restent visibles avec une indisponibilité; les menus désactivés sont absents.

Ouvrir un menu pour lire galerie, prix du forfait, minimum, plats, allergènes, stock et conditions. Lire les délais et consignes de conservation avant de commander. Les illustrations servent à présenter le menu et ne constituent pas des photos contractuelles.

Contact : saisir objet, email et message de 10 à 5 000 caractères. Envoyer puis lire la confirmation ou l'erreur. Une erreur d'envoi ne signifie pas que l'entreprise a reçu le message. Les horaires et liens légaux sont en bas de chaque page.

## Créer un compte et se connecter

Ouvrir Mon espace puis Créer mon compte. Renseigner nom, prénom, email, téléphone et adresse. Choisir un mot de passe de 10 à 72 caractères avec majuscule, minuscule, chiffre et caractère spécial; éviter les caractères multioctets à la limite supérieure de 72 octets. Le rôle client est automatique. Un email de bienvenue est envoyé lorsque SMTP est disponible.

Se connecter avec email et mot de passe. Mot de passe oublié envoie un lien valable une heure; le message reste neutre si l'adresse n'est pas connue. Après utilisation, le lien ne fonctionne plus et les anciennes sessions sont révoquées. Se déconnecter via le bouton de navigation.

## Commander : formulaire et récapitulatif

Cliquer Commander ce menu. Une connexion est demandée au visiteur. Le menu et les informations du compte sont repris. Corriger email/téléphone depuis Mon profil si nécessaire. Indiquer adresse, lieu, date, heure, convives et distance; respecter le minimum et le délai du menu.

Distance : saisir 0 pour Bordeaux. Hors Bordeaux, déclarer les kilomètres à confirmer avec l'équipe; aucun calcul cartographique automatique n'est effectué. Le tarif est 5 € + 0,59 € par kilomètre hors Bordeaux.

Le prix affiché correspond au nombre minimum de convives. Exemple : forfait 120 € pour 4 personnes, 9 convives = 270 € de repas, remise 27 €, soit 243 €. À 20 km hors Bordeaux, livraison 16,80 €, total 259,80 €. La remise de 10 % porte sur le repas dès minimum + 5 personnes.

Lire le récapitulatif, puis confirmer. La disponibilité et le prix sont revérifiés; si le menu a changé, refaire le récapitulatif. Une confirmation crée la commande, réserve une disponibilité et déclenche l'email. Ne pas considérer un simple récapitulatif comme une commande enregistrée.

## Client : suivi, modifications et avis

Mes commandes affiche les détails et l'historique horodaté. Tant que le statut est en attente, modifier la prestation ou annuler avec confirmation. Le menu ne peut pas être changé. Après acceptation, contacter l'équipe pour toute demande.

Le suivi passe par accepté, en préparation, en cours de livraison, livré, puis terminée sans prêt de matériel, ou attente du retour de matériel. En cas de prêt, contacter l'entreprise pour restituer le matériel. Le délai est de 10 jours ouvrés, lundi-vendredi sans jours fériés dans cette version; au-delà, 600 € de frais sont appliqués.

Après clôture, l'email invite à déposer une note de 1 à 5 et un commentaire. Un seul avis est possible par commande. Il apparaît en accueil seulement après validation par l'équipe. Mon profil permet de changer ses coordonnées, jamais son rôle.

## Employé : gérer les prestations

Commandes : filtrer par client ou statut. Suivre l'ordre proposé dans le sélecteur; les étapes impossibles sont refusées. Pour annuler, contacter le client puis enregistrer le mode de contact et le motif. L'historique et la commande sont conservés.

Après livraison sans prêt, terminer. Avec prêt, passer en attente du retour de matériel : un email décrit immédiatement le délai et les 600 €. À la restitution, utiliser Retour matériel; la commande se termine et l'invitation à donner un avis part. Le contrôle automatique des retards est une tâche serveur à installer.

## Employé : catalogue et horaires

Menus : créer ou modifier les caractéristiques, stock, délai et activation. Gérer les plats pour sélectionner et ordonner les compositions. Un plat peut servir dans plusieurs menus. Une suppression de menu déjà commandé entraîne sa désactivation pour préserver l'historique.

Images : ajouter un JPEG, PNG ou WebP pris en charge par le serveur, de 5 Mo et 16 mégapixels maximum. Décrire l'image pour son texte alternatif. Les images sont réencodées en PNG; les SVG téléversés sont refusés. La première image est la couverture, les suivantes complètent la galerie. Retirer une image enlève sa référence dans la galerie.

Plats : choisir entrée, plat ou dessert; sélectionner ou ajouter les allergènes. Horaires : modifier un jour, renseigner ouverture/fermeture ou marquer fermé. Avis : valider ou refuser les avis en attente; seuls les validés sont publics.

## Administrateur : équipe et statistiques

L'administrateur dispose de toutes les fonctions employé. Dans Équipe, créer un employé avec email et mot de passe sécurisé. L'email de création ne contient jamais le mot de passe; le communiquer séparément. Désactiver un employé bloque aussi sa session déjà ouverte. Aucun administrateur ne peut être créé depuis l'interface.

Statistiques lit Firebase après synchronisation serveur. Filtrer par menu, durée ou dates, puis afficher. Comparer les commandes et le CA dans le graphique et le tableau. Le CA livré comprend remise et livraison, exclut annulations et pénalités, et est groupé par date de création des commandes. Vérifier l'heure de synchronisation.

À ce jour Firebase n'est pas configuré : une erreur explicite remplace les indicateurs. La connexion réelle et le graphique alimenté par Firebase restent à recetter; les chiffres ne sont jamais simulés sur cette page.

## Aide et limites

Une page interdite indique généralement un rôle insuffisant ou une session expirée. Revenir à la connexion; ne pas contourner les autorisations. Une erreur de formulaire demande de corriger les champs. Une erreur de service SMTP/Firebase doit être transmise à l'administrateur.

La navigation fonctionne au clavier, avec focus visible, lien d'accès au contenu et menu mobile. La recette couvre les principaux écrans à 390, 768 et 1 440 pixels; elle ne constitue pas une certification RGAA exhaustive. Les pages légales sont explicitement des documents de démonstration à compléter pour l'exploitation réelle.
