# Note de développement — récupération sans e-mail

Cette option complète le lien envoyé par e-mail. Elle fonctionne sur une
installation accessible sur le réseau local, sans connexion Internet.
Elle ne rend pas une application hébergée à distance accessible sans réseau.

## Parcours

1. Le super-administrateur ouvre Administration → Utilisateurs → Récupération
   du compte, vérifie l'identité de l'agent et confirme son propre mot de passe.
2. Un code à usage unique est affiché dans la réponse, sans stockage en clair
   dans une session ou une URL. Il expire après 15 minutes.
3. Le titulaire ouvre Mot de passe oublié → Utiliser un code de récupération,
   saisit son identifiant e-mail, le code et son nouveau mot de passe confirmé.
   L'identifiant e-mail n'implique pas l'envoi d'un message.
4. Après réussite, il doit se connecter. Les anciens mots de passe, sessions,
   liens e-mail et codes ne permettent plus d'accéder au compte.

Les codes sont propres à l'installation qui les émet. Un code PlanetHoster ne
fonctionne pas sur une installation locale indépendante.

## Contrôles

- Aléa cryptographique de 128 bits ; seule l'empreinte SHA-256 est conservée.
- Verrouillage transactionnel du compte pour émission et consommation.
- Cinq erreurs invalident le code, sans bloquer la connexion habituelle.
- Limitation des tentatives HTTP ; aucune indication sur l'existence d'un compte
  dans le message d'échec public.
- Contrôle du compte actif et de l'empreinte des identifiants au moment de l'usage.
- L'émission d'un nouveau code invalide les précédents. Un changement de mot de
  passe ou d'adresse e-mail rend les anciens codes inutilisables.
- Les pages contenant les codes ne doivent pas être mises en cache.
- Historique des 20 dernières émissions sur la fiche de récupération du compte,
  avec auteur, date, expiration, utilisation et invalidation ; sans secret brut.

## Secours du super-administrateur

L'opérateur disposant d'un accès autorisé à la console du serveur local peut
exécuter `php artisan app:recovery-code adresse-du-compte`. La commande demande
confirmation de l'identité et émet un code temporaire sans choisir le mot de
passe de l'utilisateur. Elle permet de débloquer le seul super-administrateur.
Ne pas rediriger sa sortie vers un journal ni l'utiliser dans une tâche planifiée.

## Déploiement local

Appliquer la migration `account_recoveries`, installer les ressources compilées
localement et configurer l'adresse de cette installation. Conserver un accès
réseau sécurisé au serveur de l'agence. Les préparatifs d'installation locale et
l'autonomie complète des autres modules sont des travaux distincts : certains
graphiques et outils du tableau Kanban utilisent encore des ressources de CDN.

Les manuels utilisateur et technique seront finalisés en fin de développement.

Référence de conception : [OWASP Forgot Password Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).
