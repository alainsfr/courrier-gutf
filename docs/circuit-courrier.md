# Affectation directe des courriers

À l'enregistrement, l'agent choisit obligatoirement un bureau destinataire actif.
Il peut désigner un agent actif de ce bureau ou laisser le bureau prendre en charge le dossier.

Le circuit des nouveaux courriers est :

**À traiter → En traitement → Terminé → Archivé**

Le bureau responsable reste le même pendant tout ce circuit. Le passage à une
nouvelle étape ne réaffecte plus le courrier au bureau historiquement associé à cette étape.
L'agent de traitement peut terminer puis archiver lui-même le dossier. Si un agent
est désigné, les autres agents du bureau ne peuvent pas traiter le dossier à sa place.
Les administrateurs conservent leurs droits de supervision.

Le traitement terminé arrête le calcul du délai. Le courrier archivé reste
consultable dans le registre avec le filtre « Archivés » et conserve son historique.
Le Kanban présente les courriers actifs du bureau ; les étapes Réception et Validation
n'apparaissent que si des dossiers existants les utilisent encore.

Les anciens dossiers et leurs bureaux sont conservés. Un dossier déjà en validation
reste accessible à un validateur de son bureau ou à un administrateur. Les nouveaux
dossiers n'empruntent plus cette étape obligatoire. Le parapheur reste disponible
pour les visas facultatifs.

## Mise en ligne

Le déploiement sur `main` exécute les seeders `RolesAndPermissionsSeeder` et
`WorkflowEtapesSeeder` pour mettre à jour les droits d'archivage et les libellés.
Aucune remise à zéro de la base n'est nécessaire.

## Vérification

`tests/Feature/DirectOfficeWorkflowTest.php` couvre l'affectation directe, le choix
du bureau et de l'agent, le parcours complet, les transitions interdites, les droits
par bureau, le tableau de bord, le Kanban et les canaux privés.
