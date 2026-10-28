# Conventions de présentation

Le socle se trouve dans resources/css/design-system.css. Les variables
--gutf-primary et --gutf-accent viennent des paramètres de marque ; les tokens
--ui-* définissent surfaces, texte, bordures et dimensions. Les couleurs des
étapes métier restent distinctes de la marque.

## Composants à utiliser

- Actions : x-buttons.primary, x-buttons.secondary, x-buttons.danger.
  Les anciens x-primary-button, etc. sont des alias de compatibilité.
- Formulaires : ui-input, ui-label, x-input-error, x-form-errors.
  Chaque champ doit disposer d’un label associé ou d’un nom accessible explicite.
  Conserver validations, valeurs précédentes et attributs d’autocomplétion.
- Contenu : ui-card, x-empty-state, ui-alert avec variante sémantique.
- Résultats : ui-table dans ui-table-scroll. Le script ajoute une aide et un accès
  clavier uniquement lorsque le tableau déborde de son conteneur.
- Suppression/retrait : data-confirm-message sur le formulaire ; conserver la
  confirmation inline comme repli si le module d’amélioration ne charge pas.
  Les suppressions AJAX de messages demandent confirmation dans leur gestionnaire.
- Dialogues : élément HTML dialog, titre accessible, commandes explicites.
  Le navigateur fournit le confinement du focus et la fermeture par Échap.

## Navigation et adaptation

Le layout partagé sépare la barre de compte du titre de page. Sur téléphone,
le menu est masqué du parcours clavier lorsqu’il est fermé ; son ouverture
confine le focus, sa fermeture le restitue à la commande d’ouverture.
Les animations respectent la préférence de mouvement réduit.

Utiliser les espacements Tailwind et ces composants ; ne pas réintroduire la
feuille Bootstrap globale ni une police distante. Les classes Bootstrap et
Tailwind de même nom avaient des valeurs et priorités différentes.
Les supports imprimés et les e-mails ont leurs gabarits autonomes.

## Vérifications avant livraison

Contrôler une liste pleine et vide, formulaire valide et invalide, droits du
rôle, actions annulées, téléphone, bureau et navigation clavier. Vérifier que
le contenu ne déborde pas de la page et que les tableaux restent consultables.
Exécuter la suite de tests et la compilation avant le déploiement.
Les manuels et leurs captures seront finalisés à la fin du développement.
