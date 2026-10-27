# Prise en charge, corrections et affectations

## Prendre en charge

Un agent de traitement peut prendre un courrier « À traiter » de son bureau.
Son nom devient celui du responsable. Un second clic du même agent ne crée pas
de doublon et un collègue ne peut pas s'approprier le dossier à sa place.
Le démarrage depuis le Kanban identifie également l'agent responsable.

## Corriger les informations

Avant la prise en charge, l'agent d'enregistrement qui a créé le courrier peut
corriger sa saisie. Ensuite, l'agent responsable peut corriger les informations
pendant le traitement. Les administrateurs conservent leur supervision.
Les courriers terminés ou archivés restent en lecture seule pour ces opérations.

Le motif est obligatoire. L'historique conserve l'acteur, la date et les valeurs
avant et après correction. La référence, le type et le circuit ne peuvent pas
être changés par le formulaire de correction. Un formulaire ouvert avant un
changement du dossier doit être rechargé pour éviter d'écraser les modifications.

## Corriger une affectation

L'agent d'enregistrement peut corriger le bureau destinataire de son courrier
tant qu'il n'est pas pris en charge. Dans son propre bureau, un agent disposant
du droit de réaffectation peut corriger l'affectation d'un dossier en cours.
Les administrateurs peuvent superviser cette opération.

Le motif est obligatoire. Un changement de bureau remet le courrier à
« À traiter » et libère l'ancien agent. Un agent du nouveau bureau peut être
désigné immédiatement ; il doit être actif et disposer du droit de traitement.
Une simple modification d'agent dans le même bureau conserve l'avancement et
l'échéance. Le nouveau bureau reçoit une notification.

## Administration

Les écrans présentent les noms métier des rôles et expliquent leur différence
avec les bureaux. Toute modification qui laisserait l'application sans
super-administrateur actif est refusée, y compris lors d'opérations concurrentes.

## Mise à jour

Le déploiement initialise les permissions `courrier.update` et `courrier.reassign`
sans réinitialiser les données. Les tests sont exécutés sous Linux avant toute
installation sur le serveur ; les dépendances de développement sont ensuite
retirées du paquet. Les déploiements ne créent plus d'archives de l'ancien code.
