# Sécurité et enregistrements simultanés

## Documents

Les ajouts initiaux et complémentaires passent par `CourrierDocument` : extension
autorisée, type MIME déterminé sur les octets avec Fileinfo, puis reconnaissance
de l’image pour les JPG/PNG. Le type déclaré par le navigateur ne fait pas foi.
Les fichiers restent limités à 10 Mo et conservés dans le stockage privé.
Il s’agit d’un contrôle de format, pas d’un antivirus.

Les ajouts et suppressions de pièces verrouillent le courrier avant de modifier
ses documents. Une archive ne peut plus recevoir ou perdre une pièce par ces
actions, y compris lorsqu’un administrateur appelle directement l’URL.
La suppression physique d’une pièce attend la validation de la transaction.

## PIN et visas

- Le changement ou la création du PIN exige le mot de passe actuel, vérifié une
  seconde fois sous verrou. La route est limitée à six appels par minute.
- Cinq PIN erronés bloquent les approbations pendant dix minutes pour le compte,
  indépendamment du visa et de l’adresse IP. Le compteur est conservé en base.
- Une réussite ou une redéfinition du PIN avec le mot de passe réinitialise le
  compteur. Une erreur de saisie ne renvoie aucun PIN dans les anciennes valeurs.
- Les décisions concurrentes sont sérialisées : une approbation et un rejet ne
  peuvent pas écraser réciproquement leur résultat.
- Les soumissions de visas verrouillent le dossier pour n’avoir qu’une demande en
  attente. Les validateurs inactifs sont exclus.

Les permissions `courrier.visa.submit` et `courrier.link` sont attribuées aux agents
d’enregistrement, de traitement, à la direction et aux administrateurs. Les profils
Consultation et Validateur ne les reçoivent pas. Les droits de lecture sur les deux
dossiers restent obligatoires pour établir un lien. Aucune de ces modifications
n’est permise sur un dossier source archivé.

## Création d’un courrier

Le formulaire reçoit un UUID. La création verrouille son auteur et compare une
empreinte des informations et du contenu du fichier. Le renvoi inchangé du même
formulaire ouvre le dossier existant, sans recréer pièce, historique ni notification.
Un renvoi avec des informations différentes est refusé avec un lien vers un nouveau
formulaire. L’UUID est propre à son auteur.

Les formulaires anciens sans UUID restent acceptés pour ne pas interrompre les
saisies ouvertes avant la mise à jour ; leurs renvois ne sont pas dédupliqués.
Deux formulaires neufs peuvent créer deux courriers aux champs identiques : la
recherche préalable reste utile pour éviter les doublons métier.

Une ligne de compteur est verrouillée par type et année. La réservation du numéro
et la création du dossier partagent la transaction, avec reprise sur interblocage.
Les anciens numéros sont pris en compte à l’initialisation ; les contraintes uniques
de la table des courriers sont conservées. La migration ne réinitialise aucun dossier.

## Vérifications

Les tests fonctionnels couvrent les fichiers déguisés, les renvois de formulaire,
les droits, les archives, les échecs de PIN et la récupération après blocage.

`tests/Concurrency/registration.php` lance de vrais processus PHP simultanés sur
MySQL : huit créations distinctes, quatre renvois du même formulaire, six essais de
PIN concurrents, deux décisions opposées et quatre demandes de visa concurrentes.
Le test refuse toute exécution hors de `APP_ENV=testing`, `DB_CONNECTION=mysql`
et `DB_DATABASE=courrier_concurrency_test`. Il réinitialise cette base de test.
Le workflow GitHub fournit une instance MySQL 8 jetable, avant tout déploiement.

Le manuel en ligne et le PDF ont été actualisés ; le PDF comporte désormais
22 pages avec 22 chapitres et leurs signets.
