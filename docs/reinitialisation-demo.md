# Remise à zéro ponctuelle de la démonstration

Cette opération supprime définitivement les anciens courriers, fichiers, échanges,
comptes de test et sauvegardes présentes dans le stockage de cette application.
Elle ne crée ni sauvegarde ni copie temporaire des anciennes données.

Le code actuel et sa configuration restent en place. Un seul compte
super-administrateur actif conserve ses identifiants pour permettre la connexion.
À défaut, un administrateur actif de l'ancien rôle `admin` est reconnu et conserve
ses identifiants avec le rôle système `super-admin`.
Les bureaux, rôles et étapes sont recréés sans dossiers fictifs.

La commande exige la confirmation littérale `DELETE_OLD_DEMO_DATA` et le nom exact
de la base ciblée. L'environnement hébergé exige aussi l'option `--production`.
Elle refuse d'opérer sans super-administrateur actif et vérifie les chemins de
stockage avant toute suppression. En cas d'échec après le début du nettoyage,
l'application reste en maintenance pour éviter d'utiliser un état incomplet.

Le workflow manuel `reset-demo.yml` lance cette opération sur la démonstration
PlanetHoster et supprime les archives des anciennes versions dans `shared/releases`.
Il ne s'exécute jamais à l'occasion d'un simple envoi de code sur GitHub.

Les déploiements ordinaires ne réinitialisent pas les données nouvellement créées.
