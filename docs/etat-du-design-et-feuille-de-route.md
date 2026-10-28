# État du design et travaux restants

Analyse du 28 octobre 2026, après la mise en service des e-mails et des tâches automatiques.

## Conclusion

L'harmonisation est **partielle**. L'application possède une identité reconnaissable
(bleu profond, orange, navigation latérale, en-tête, avatars et composants communs),
mais ne dispose pas encore d'un système de présentation appliqué à tous les modules.
La modernisation du parcours courrier ne constitue pas une refonte intégrale.

Ce constat repose sur l'examen des gabarits, composants, styles et scripts.
Il ne remplace pas une recette visuelle exhaustive de chaque état sur chaque appareil.

## Constats vérifiables

| Domaine | État et exemples |
|---|---|
| Navigation et identité | Socle partagé dans `layouts/app.blade.php`, paramètres de marque et variables `--gutf-primary`/`--gutf-accent`. |
| Parcours courrier | Enregistrement guidé, prochaine action mise en avant, aperçu, rubriques secondaires et adaptations mobiles déjà réalisés. |
| Boutons | Deux familles coexistent : `components/primary-button.blade.php` utilise fond gris, capitales et petits caractères ; `components/buttons/primary.blade.php` utilise la marque, une autre taille et un autre arrondi. |
| Champs et erreurs | Le parcours courrier dispose de `courrier-field`/`courrier-input`, tandis que l'administration répète ses propres classes de champs, avec des accents bleus distincts de la marque. Les pages de compte utilisent encore d'autres composants. |
| Couleurs | Les paramètres de marque sont partagés, mais des valeurs fixes restent dans des gabarits et les styles de messagerie/connexion. Une modification de la couleur principale n'est donc pas appliquée partout. |
| Typographie | La connexion utilise Fraunces/Outfit chargées depuis Google Fonts ; Tailwind déclare Figtree ; des écrans héritent des réglages système/Bootstrap. |
| Cartes et titres | En-têtes, sous-titres, espacements, ombres, arrondis et position des actions sont encore définis écran par écran. |
| États d'interface | Il existe un composant d'état vide, mais les confirmations, erreurs, chargements, dialogues et messages ne suivent pas tous une convention commune. |
| Styles techniques | Bootstrap et Tailwind sont chargés ensemble. Leur coexistence exige des vérifications : certaines classes communes ont déjà nécessité des dispositions explicites dans le parcours courrier. |
| Mobile et accessibilité | Des vérifications ont été réalisées sur le parcours principal. Une revue complète de l'administration, recherche, rapports, messagerie, clavier, contrastes et zoom reste à faire. |

## Ordre de réalisation proposé

1. **Sessions et confidentialité** : expiration sur activité humaine, contrôle serveur,
   avertissement, plusieurs onglets, refus des anciennes reconnexions persistantes.
   La valeur serveur héritée était de 120 minutes et les sondages prolongeaient la
   session. La correction conserve la politique de 30 minutes, distincte des appels
   automatiques, et nécessite une reconnexion après changement du cookie serveur.
2. **Fondations visuelles** : formaliser palette, couleurs d'état, typographie,
   espacements, arrondis, largeurs de contenu et tailles des commandes. Consolider
   les composants existants au lieu d'ajouter une troisième famille.
3. **Composants partagés** : unifier boutons, champs, labels, aides, erreurs,
   cartes, en-têtes, filtres, tableaux, pagination, badges, dialogues et confirmations.
   Les changements doivent préserver les droits, validations et parcours métier.
4. **Reprise des écrans** : tableau de bord et registre, recherche et fiche,
   administration, parapheur, messagerie, rapports, profil et récupération.
   Vérifier aussi les listes vides, erreurs, états désactivés et chargements.
5. **Recette de l'expérience** : bureau et téléphone, petit écran, zoom à 200 %,
   clavier, focus, contrastes, débordements et tableaux, documents volumineux.
   Actualiser les captures du manuel après stabilisation du design.

## Autres travaux avant une exploitation définitive

- Tester une restauration complète de données actuelles dans un environnement
  séparé ; valider une copie de sauvegarde hors serveur et les alertes de sauvegarde.
- Ajouter une surveillance externe de disponibilité et un destinataire d'alerte
  choisi par le responsable. Le contrôle de console actuel ne constitue pas cette surveillance.
- Vérifier les recherches et exports sur un volume représentatif, les documents
  volumineux et les comportements lorsque le réseau ralentit.
- Faire accepter le parcours par chaque rôle concerné avec le client, puis mettre
  le manuel et la fiche de recette en accord avec les décisions finales.

## Documentation de fin de développement

À la demande de l'utilisateur, les mises à jour du manuel d'utilisation et la
création du manuel technique de l'administrateur sont regroupées en fin de
développement. Les notes de travail de `docs/` servent à conserver les décisions
entre-temps ; elles ne constituent pas le manuel technique final.

Le manuel technique final devra couvrir l'architecture, les prérequis Linux,
l'installation, la configuration, les rôles et droits, les mises à jour,
les sessions, SMTP, le planificateur et les files, les sauvegardes/restaurations,
les journaux, la supervision, le dépannage et les procédures d'exploitation.

Les e-mails réels, la récupération et le déclenchement du planificateur ont déjà
été vérifiés. La sauvegarde réalisée au déploiement ne prouve pas qu'une restauration
complète a été testée.
