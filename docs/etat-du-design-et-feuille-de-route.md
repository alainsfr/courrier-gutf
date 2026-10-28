# État du design et travaux restants

Mise à jour du 28 octobre 2026.

## Harmonisation réalisée

Le socle visuel est commun aux écrans de courrier, tableau de bord, recherche,
parapheur, messagerie, statistiques, notifications, audit, administration,
profil et authentification. Les supports imprimés et les e-mails conservent
leur présentation adaptée à leur support.

- Palette de marque configurable, typographie système sans téléchargement de
  polices externes, surfaces, bordures, arrondis, espacements et focus communs.
- Une famille de boutons, champs, labels, erreurs, cartes et tableaux. Les anciens
  composants de boutons délèguent aux composants canoniques.
- Titres dans le contenu, barre de compte compacte, fil d’Ariane, repère de page
  active, lien d’évitement et navigation mobile avec confinement du focus.
- Connexion et récupération dans une même présentation ; les pages de compte
  partagent les champs et commandes de l’application.
- Confirmations, signature et membres d’un groupe dans des dialogues natifs ;
  annulation au clavier et focus gérés sans modifier les contrôles métier.
- Tableaux contenus dans leur zone de défilement, indication de défilement et
  accès clavier quand nécessaire. Le registre conserve ses cartes sur téléphone.
- Suppression des feuilles et scripts Bootstrap globaux : leurs utilitaires
  d’espacement entraient en conflit avec Tailwind. Le dialogue de signature
  utilise maintenant la même infrastructure native que les autres dialogues.

## Vérifications

La suite complète comporte 159 tests. Le contrôle de rendu couvre 24 écrans
avec données, l’unicité des identifiants HTML et les cibles des labels.
Les contrôles métier, autorisations et sessions restent couverts.

La recette navigateur isolée utilise des comptes et dossiers fictifs : bureau et
petit écran de 390 pixels, registre, formulaires courrier et administration,
profil, recherche, statistiques, audit, sauvegardes, messagerie et dialogues.
Les données de démonstration ne sont pas ajoutées en production.

Ces contrôles ne constituent pas une certification WCAG ni une vérification de
toutes les combinaisons d’appareil, document et contenu. La validation avec les
utilisateurs du client demeure nécessaire.

## Autres travaux avant une exploitation définitive

- Tester une restauration complète dans un environnement séparé et valider une
  copie hors serveur ainsi que les alertes de sauvegarde.
- Ajouter une surveillance externe de disponibilité et choisir le destinataire
  des alertes. Le contrôle de console actuel ne constitue pas cette surveillance.
- Vérifier recherches, exports et documents sur un volume représentatif, ainsi
  que les comportements lorsque le réseau ralentit.
- Faire accepter le parcours par chaque rôle concerné avec le client.

Les e-mails réels, la récupération de mot de passe et le planificateur ont déjà
été vérifiés. L’expiration après 30 minutes d’inactivité humaine est contrôlée
côté serveur, indépendamment des sondages automatiques.

## Documentation en fin de développement

À la demande de l’utilisateur, l’actualisation du manuel d’utilisation et la
création du manuel technique administrateur sont regroupées en fin de
développement. Les notes de docs/ conservent les décisions intermédiaires.
Les sources et captures du manuel existant ne sont pas régénérées dans ce lot.

Le manuel technique final couvrira architecture, prérequis Linux, installation,
configuration, rôles, mises à jour, sessions, SMTP, planificateur et files,
sauvegardes/restaurations, journaux, supervision et dépannage.
