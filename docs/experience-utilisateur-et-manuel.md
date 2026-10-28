# Parcours utilisateur et manuel

Le formulaire d’enregistrement présente les informations essentielles et le bureau
destinataire avant les rubriques facultatives. La fiche privilégie l’action suivante,
l’identité du dossier et la lecture du document. Le registre et les rapports ont des
entrées distinctes dans le menu.

Le manuel en français est accessible aux utilisateurs connectés et actifs à `/aide`.
Son sommaire contient 22 chapitres. Le PDF imprimable possède 22 pages, un sommaire
cliquable et des signets. Les quatre captures proviennent d’une base de test isolée
et ne contiennent que des données fictives.

## Mettre à jour le manuel

1. Modifier `resources/docs/manuel-utilisation.md`, source affichée dans l’application.
2. Actualiser au besoin les captures fictives `public/docs/guide-*.png`.
3. Installer ReportLab dans un environnement Python de documentation, puis exécuter
   `python tools/build_user_guide.py`.
4. Rendre et vérifier visuellement toutes les pages du PDF, ainsi que le sommaire et
   les signets. Le générateur utilise Segoe UI sous Windows ou DejaVu Sans sous Linux.
5. Livrer la source, le PDF et les captures ensemble. Le déploiement ne nécessite pas
   Python ; il publie le document déjà généré.

## Vérifications de ce lot

- Parcours dans une base SQLite distincte : enregistrement par l’accueil, accès au
  bureau destinataire, prise en charge, démarrage, ajout d’une pièce, clôture,
  archivage et recherche de l’archive.
- Fiche et formulaire à 390 px : absence de débordement horizontal ; champ objet
  obligatoire ; visionneuse agrandie et fermeture vérifiées.
- Manuel : 22 chapitres, quatre images chargées, 22 signets dans le PDF ; mise en page
  contrôlée après rendu des 22 pages.
- Aucun courrier fictif n’est inséré dans les bases de l’application locale ou hébergée.

La prévention du double clic est complétée par un identifiant de formulaire unique
et un contrôle des informations côté serveur. Le compteur de références est verrouillé
en transaction. Les formulaires anciens sans identifiant restent compatibles, mais
ne bénéficient pas de la déduplication des renvois.
