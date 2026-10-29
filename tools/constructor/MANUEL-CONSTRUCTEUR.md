# COURRIER GUTF — Manuel constructeur : licences hors ligne

Document réservé au développeur et à l’émetteur des licences.
Version du 29 octobre 2026. Ce document et les outils de ce dossier sont exclus
des copies préparées pour les agences et du déploiement PlanetHoster.
Il ne fait pas partie du manuel utilisateur ni du manuel de l’administrateur d’agence.

Le paquet autonome Linux/Windows et la maintenance gratuite décrits en sections
11 à 13 complètent cette procédure. Le manuel technique livré à l’agence décrit
l’installation et l’exploitation sans exposer les détails de licence ci-dessous.

## 1. Politique retenue

- Chaque licence autorise une installation identifiée sur un serveur déterminé.
- Le constructeur choisit les dates pour chaque client et chaque agence.
- Aucune connexion Internet ni appel de validation distant n’est nécessaire.
- À l’expiration, les données restent consultables ; les opérations métier,
  créations, modifications, transmissions, suppressions, impressions de bordereaux
  et exports de rapports sont refusés.
- La consultation des pièces jointes originales reste possible. Les sauvegardes
  automatiques, contrôles d’exploitation, sessions, préférences de langue et
  opérations de récupération de compte continuent de fonctionner.
- Aucun menu, bandeau ou avertissement de licence n’apparaît dans l’interface.
  Une opération refusée affiche uniquement :

  > Cette action est actuellement indisponible. Veuillez contacter l’administrateur pour résoudre ce problème.

La démonstration PlanetHoster et le checkout de développement restent sans
contrôle de durée. Seules les copies agence préparées par l’outil ci-dessous
activent la politique. Il n’existe pas d’option de désactivation dans `.env`.

## 2. Où se définit exactement la durée ?

**La durée est définie par `--starts` et `--expires` dans l’outil constructeur
`issue-license.php`. Elle ne se règle jamais dans les paramètres de l’application.**

Les deux valeurs sont des dates ISO 8601 avec heure et décalage UTC obligatoire.
Le début est inclus ; la fin est exclue. Si `--starts` est omis, le début correspond
à l’instant où le constructeur signe le fichier.

| Autorisation souhaitée | `--starts` | `--expires` |
|---|---|---|
| Novembre 2026 complet, heure du Cameroun | `2026-11-01T00:00:00+01:00` | `2026-12-01T00:00:00+01:00` |
| Trois mois à partir du 1er novembre | `2026-11-01T00:00:00+01:00` | `2027-02-01T00:00:00+01:00` |
| Douze mois à partir du 1er novembre | `2026-11-01T00:00:00+01:00` | `2027-11-01T00:00:00+01:00` |

Avec la dernière ligne, les écritures sont autorisées jusqu’au 31 octobre 2027 à
23:59:59 inclus et refusées à partir du 1er novembre 2027 à 00:00:00, heure indiquée.
Les dates sont stockées et comparées comme des instants UTC ; le fuseau d’affichage
du navigateur ne change pas leur effet. Pour un autre pays, fournir le décalage
correspondant aux dates du contrat, notamment s’il existe un changement d’heure.

La date de fin doit être future et postérieure au début. Modifier directement
une date dans un fichier signé invalide sa signature.

## 3. Première préparation du poste constructeur

Prérequis : PHP 8.2 ou supérieur, extension OpenSSL, dépendances Composer du projet.
Les exemples Windows utilisent PowerShell. Adapter les chemins si nécessaire.

```powershell
$projectPath = 'C:\xampp\htdocs\courrier-gutf'
$keyRoot = Join-Path $env:USERPROFILE 'Documents\Courrier-GUTF-Constructeur\cles'
New-Item -ItemType Directory -Force -Path $keyRoot | Out-Null
$issuerSid = [Security.Principal.WindowsIdentity]::GetCurrent().User.Value
icacls $keyRoot /inheritance:r /grant:r "*$($issuerSid):(OI)(CI)F"
Set-Location -LiteralPath $projectPath
php tools/constructor/generate-keys.php --directory="$keyRoot"
```

Deux fichiers sont créés :

- `issuer-private.pem` : clé privée de signature, à garder exclusivement sur le
  poste constructeur protégé. Elle n’est pas chiffrée par une phrase secrète :
  les droits d’accès du dossier sont donc essentiels. Conserver une copie de
  secours chiffrée, séparée du poste et des paquets clients.
- `issuer-public.pem` : clé publique, à intégrer dans les copies agences.

Ne jamais placer la clé privée dans le dépôt Git, `.env`, une sauvegarde client,
un serveur d’agence, une pièce jointe de support ou un paquet de livraison.
L’outil refuse de remplacer des clés existantes et de les créer dans le projet.
La génération n’est à faire qu’une fois pour une même identité de constructeur.

## 4. Préparer une copie agence

Assembler d’abord une copie de livraison Laravel distincte du checkout :
dépendances de production, ressources compilées localement, aucun `.env`, aucune
donnée client, aucun cache de configuration préexistant ni dossier Git.
Le futur paquet Linux autonome devra également embarquer les ressources encore
chargées depuis des CDN dans certains modules ; le contrôle de licence ne réalise
pas à lui seul cette préparation de déploiement.

```powershell
php tools/constructor/prepare-agency.php --target="C:\Livraisons\agence-centre\app" --public-key="$keyRoot\issuer-public.pem"
```

Cet outil :

1. vérifie qu’il s’agit d’une copie distincte, sans identité existante, `.env`,
   cache de configuration ou clé privée susceptible d’être livrée ;
2. installe la clé publique dans `config/runtime-public.pem` ;
3. écrit `managed=true` dans `config/distribution.php` de **cette copie** ;
4. retire `tools/constructor` — y compris ce manuel — et `tests` de cette copie.

Ne pas distribuer directement le checkout de développement : sa politique reste
volontairement inactive pour ne pas perturber la démonstration existante.
Pour une copie agence, le répertoire public standard est `app/public`, contrairement
à l’aménagement particulier du sous-domaine PlanetHoster actuel.

## 5. Installer sur le serveur Linux et obtenir la demande

Installer la copie agence sur le serveur, configurer son `.env`, sa base de données,
sa clé applicative et les droits du stockage. Appliquer les migrations et les
initialisations nécessaires selon la procédure de déploiement.
Le serveur doit fournir un `/etc/machine-id` valide et propre à cette machine.
Ne pas partager un identifiant de système entre plusieurs machines clonées.
Le code, les fichiers de configuration et la clé publique doivent être en lecture
seule pour le compte PHP ; limiter les écritures à `storage` et aux caches requis.
Les administrateurs applicatifs ne doivent pas disposer d’un accès d’écriture aux
fichiers du programme.

Exécuter les commandes sous le même compte système que PHP, par exemple `www-data` :

```bash
cd /var/www/courrier-gutf
sudo -u www-data php artisan app:installation-request /var/www/courrier-gutf/storage/app/private/demande-agence-centre.json
```

La demande contient un identifiant d’installation, une empreinte dérivée du serveur
et un numéro de génération. L’identifiant brut du système n’est pas exporté.
L’empreinte tient aussi compte de l’UUID matériel exposé par Linux lorsqu’il est
lisible ; changer son accessibilité entre émission et exploitation change l’empreinte.
Cette raison impose d’utiliser le même compte système pour la demande et PHP.

Transférer la demande au constructeur par le moyen convenu : clé USB ou poste
disposant d’Internet. La demande n’implique aucune activation automatique.
L’outil refuse d’écraser un fichier de demande existant : choisir un nouveau nom.

Avant réception d’un fichier valide, la copie reste en consultation seule.

## 6. Émettre la licence pour ce client

Sur le poste constructeur, conserver un dossier par client/agence contenant les
demandes reçues et les fichiers signés émis. La clé privée reste dans son dossier
protégé, séparée de ces fichiers transmissibles.

```powershell
Set-Location -LiteralPath 'C:\xampp\htdocs\courrier-gutf'
php tools/constructor/issue-license.php --request="C:\Dossiers-clients\client-a\agence-centre\demande.json" --key="$keyRoot\issuer-private.pem" --client="Client A" --agency="Agence Centre" --starts="2026-11-01T00:00:00+01:00" --expires="2027-11-01T00:00:00+01:00" --output="C:\Dossiers-clients\client-a\agence-centre\configuration-2026.json"
```

**Pour changer la durée, modifier les deux dates dans cette commande avant
l’émission. Pour un renouvellement, utiliser une nouvelle demande du serveur.**

Le fichier de sortie est signé avec RSA 3072 bits et SHA-256. Le client et l’agence
ne sont pas affichés dans un écran de licence. Le fichier n’est pas chiffré : son
authenticité est protégée, mais un opérateur disposant des fichiers peut en examiner
le contenu. La discrétion de l’interface n’est pas une garantie de secret face à
un administrateur du système d’exploitation.

## 7. Importer le fichier dans l’agence

Transférer uniquement le fichier signé ; ne pas transférer la clé privée ni ce manuel.

```bash
cd /var/www/courrier-gutf
sudo -u www-data php artisan app:installation-import /chemin-prive/configuration-2026.json
```

Le début peut être futur : le fichier s’installe mais les écritures restent refusées
jusqu’à la date prévue. Si le fichier est incorrect, expiré, destiné à une autre
installation ou d’une ancienne génération, il est refusé.
Après import valide, la reprise ne nécessite pas de connexion Internet.
Le fichier original ne doit pas être placé dans un répertoire servi par le navigateur.

## 8. Renouveler, transférer ou corriger

### Renouvellement

1. Générer une nouvelle demande sur le même serveur avec un nouveau nom de fichier.
2. Signer cette demande avec les nouvelles dates.
3. Importer le nouveau fichier signé dans l’agence.

La génération augmente à chaque import. Un ancien fichier ne peut pas remplacer
le renouvellement. La réutilisation de la demande initiale ne convient donc pas.

### Changement de serveur ou réinstallation du système

Créer une nouvelle demande sur la nouvelle installation, puis émettre une licence
liée à cette nouvelle demande. Reporter la date de fin convenue avec le client.
Décommissionner l’ancienne installation selon la procédure d’exploitation.
Hors ligne, l’émission d’un remplacement ne révoque pas instantanément un ancien
serveur qui conserve encore un fichier valide.

### Sauvegarde et restauration

L’état privé est dans `storage/app/private/.runtime` et doit rester hors du répertoire
public. Les sauvegardes métier actuelles comprennent les courriers et assets publics,
pas ce dossier. Restaurer uniquement les données métier sur le même serveur conserve
l’état courant. Une restauration sur un autre serveur nécessite une nouvelle demande.

### Horloge ou état endommagé

Un recul observé de plus de cinq minutes provoque la consultation seule. Une
expiration déjà observée reste acquise, même si un faible recul rentre ensuite dans
cette tolérance. Les petits ajustements d’horloge restent tolérés.
Corriger d’abord l’heure système sans modifier les fichiers d’état à la main.
Si l’état a été perdu ou endommagé, le constructeur doit préparer une réinitialisation
contrôlée de l’identité et signer une nouvelle demande ; il n’existe pas de bouton
utilisateur de remise à zéro. Ne pas supprimer cet état en routine ni dans un script
de mise à jour. Une clé `APP_KEY` changée rend aussi l’état existant invalide.

## 9. Diagnostic constructeur

Le diagnostic n’est accessible par aucune route web. Le script ci-dessous est dans
les outils constructeur, retirés du paquet agence. Le transférer seulement pour une
intervention autorisée, hors du répertoire public, puis le retirer après intervention.

```bash
php /chemin-constructeur/diagnose.php --application=/var/www/courrier-gutf
```

| Motif interne | Vérification |
|---|---|
| `valid` | Le fichier est valide et les écritures sont autorisées. |
| `unmanaged` | Copie de développement/démonstration, sans contrôle de durée. |
| `expired` | Date de fin atteinte ou expiration déjà observée ; renouveler. |
| `not_started` | Date de début non atteinte ou horloge trop ancienne. |
| `clock_rollback` | Horloge revenue en arrière ; contrôler l’heure et l’historique. |
| `binding` | Serveur, installation ou contexte de lecture de l’identité différent. |
| `generation` | État et fichier signé incompatibles. |
| `unavailable` | Fichier absent, signature invalide, état altéré, permissions ou clé applicative incorrectes. |

Ces motifs ne sont pas envoyés au navigateur et ne sont pas écrits par ce contrôle
dans les journaux applicatifs. Le diagnostic peut afficher la fin UTC lorsque l’état
est valide. Conserver le diagnostic dans le dossier de support du constructeur.

## 10. Limites et conservation des clés

La signature empêche de fabriquer ou de modifier des licences acceptées sans la clé
privée. Le contrôle du serveur freine la réinstallation et la copie sur une autre
machine ; il ne peut pas prouver l’unicité mondiale de clones entièrement déconnectés
qui reproduisent tous leurs identifiants.

Un client ayant les droits `root` et le code PHP peut modifier le programme ou ses
fichiers. De même, restaurer une image complète avec son horloge et ses identifiants
peut contourner des contrôles purement logiciels hors ligne. Une protection plus
forte nécessiterait un composant matériel ou des vérifications externes et ne fait
pas partie de cette version. Ne pas promettre une protection absolue ou une
révocation distante immédiate d’un serveur sans Internet.

En cas de compromission de la clé privée, remplacer l’identité de signature et
mettre à jour les clés publiques des installations concernées. En cas de perte de
toutes les copies privées, les anciennes licences restent vérifiables mais leur
renouvellement exigera une nouvelle identité et une mise à jour côté agence.

Références de conception : [signature OpenSSL PHP](https://www.php.net/manual/en/function.openssl-sign.php),
[vérification OpenSSL PHP](https://www.php.net/manual/en/function.openssl-verify.php),
[identité système Linux](https://www.freedesktop.org/software/systemd/man/latest/machine-id.html).

## 11. Construire le paquet autonome Linux / Windows

Le paquet unique contient des images Linux `amd64` déjà construites, la base MariaDB,
les bibliothèques graphiques locales et deux points d’entrée : `install-linux.sh`
et `Installer-Windows.ps1`. Windows utilise une VM Linux, pas Docker Desktop ni une
installation PHP native. La démonstration PlanetHoster reste une livraison distincte,
sans contrôle de durée ; aucune commande ci-dessous ne doit l’activer sur ce site.

La construction exige Docker Engine, Python 3, Git et OpenSSL sur le poste de
construction. La clé **publique** est fournie au constructeur de paquet ; aucune
clé privée n’entre dans les images, GitHub ou les archives agence.

```bash
python3 tools/constructor/build-agency.py --public-key=/coffre/issuer-public.pem --output=/livraisons/courrier-2026-10-29
```

Le workflow GitHub Actions `Build agency installer` réalise également cette tâche :
son unique paramètre est le contenu de `issuer-public.pem` encodé en base64. Il vérifie
les scripts, construit les images, installe deux agences vierges simultanément,
contrôle les tâches et la sauvegarde, puis publie l’archive et sa somme SHA-256.
Le passage de ce paramètre public ne compromet pas la clé privée.

La préparation écrit `managed=true` uniquement **dans l’image agence** et y installe
la clé publique. Les outils et ce manuel ne sont pas copiés. L’image contient aussi
les bibliothèques Chart.js, Sortable et Lucide : le navigateur ne dépend pas de CDN.
Conserver une copie pérenne des archives vérifiées hors des artefacts GitHub temporaires.

L’installation d’agence génère ses propres secrets, une base sans données métier et
son premier administrateur. Avant d’ouvrir les écritures, recueillir la demande,
signer la durée convenue puis importer le fichier comme ci-dessous.

```bash
sudo python3 /opt/courrier-gutf/agence-centre/control.py request > demande-agence.json
sudo python3 /opt/courrier-gutf/agence-centre/control.py import --file /chemin-prive/configuration.json
sudo python3 /opt/courrier-gutf/agence-centre/control.py diagnostic
```

Le stockage privé reste dans le volume de l’instance. Les conteneurs lisent en
lecture seule le `/etc/machine-id` de l’hôte Linux/VM ; changer de conteneur ne crée
donc pas une nouvelle identité système. Ne pas cloner les volumes ni la VM activée
pour une deuxième agence. La migration vers une autre machine suit la section 8.

## 12. Préparer le point de maintenance constructeur, sans abonnement

Logiciels : **WireGuard + OpenSSH**, hébergés sur une machine Linux sous ton contrôle.
Aucun compte Tailscale, service cloud VPN ou abonnement logiciel n’est requis.
Il faut néanmoins disposer d’un endpoint Internet joignable. Un hébergement partagé
PlanetHoster ne doit pas être considéré comme un serveur VPN sans prise en charge
explicite du fournisseur. Utiliser une machine existante capable de recevoir UDP,
ou prévoir cette infrastructure séparément.

1. Installer `wireguard` et `openssh-server` depuis les paquets officiels du serveur.
2. Réserver un plan d’adressage VPN distinct des réseaux des agences, par exemple
   `10.203.0.1` pour le point constructeur et une adresse /32 unique par agence.
3. Créer une paire de clés WireGuard sur cette machine et protéger les fichiers :

   ```bash
   umask 077
   wg genkey | tee constructeur-wg-private.key | wg pubkey > constructeur-wg-public.key
   ```

4. Préparer `/etc/wireguard/wg-gutf.conf`, propriétaire root, mode 600 :

   ```ini
   [Interface]
   PrivateKey = CLE_PRIVEE_WIREGUARD_CONSTRUCTEUR
   Address = 10.203.0.1/32
   ListenPort = 51820
   ```

   Remplacer la valeur de clé dans un éditeur local protégé. Autoriser uniquement
   le port UDP retenu dans la politique existante ; si nécessaire, configurer sa
   redirection sur le routeur du constructeur. Ne pas changer les autres règles.
5. Activer l’interface avec `sudo systemctl enable --now wg-quick@wg-gutf`.
6. Conserver l’accès SSH du constructeur séparé, par clé. Depuis Windows, un saut
   SSH vers ce serveur permet d’atteindre une agence : la clé privée de support
   reste sur le poste constructeur, grâce à `ssh -J`.

Ce point de maintenance termine le tunnel ; il n’a pas besoin de router Internet
ou les LAN des agences. Ne pas ajouter de NAT ni de route par défaut. Si la machine
fait déjà du routage pour un autre service, son pare-feu doit interdire les transferts
entre pairs de l’interface `wg-gutf`, sans désactiver le routage des autres services.
L’hébergement, l’électricité et la connectivité ne deviennent pas gratuits parce
que les logiciels utilisés sont gratuits.

## 13. Associer une agence et intervenir

Préparer une clé **SSH** dédiée au support, distincte de la clé de signature RSA et
de la clé WireGuard. Exemple sur le poste constructeur :

```powershell
ssh-keygen -t ed25519 -f "$env:USERPROFILE\.ssh\courrier-support"
```

Protéger la clé privée avec une phrase secrète ; transférer seulement le fichier
`.pub` dans un chemin privé du serveur d’agence. Sur l’agence, depuis le dossier
du paquet extrait, exécuter avec les valeurs convenues :

```bash
sudo python3 maintenance/configure-maintenance.py \
  --directory /opt/courrier-gutf/agence-centre \
  --ssh-public-key /chemin-prive/courrier-support.pub \
  --peer-public-key CLE_PUBLIQUE_WIREGUARD_CONSTRUCTEUR \
  --endpoint support.exemple:51820 \
  --address 10.203.0.2/32 \
  --peer-address 10.203.0.1/32
```

Le script vérifie les prérequis, prépare un compte `gutf-<identifiant>`, ses
restrictions SSH et une interface `wg-g<identifiant>`, puis affiche la clé publique
de l’agence et le nom du compte. Il refuse d’écraser une configuration existante.
Conserver ce résultat dans le dossier de support de l’agence, sans sa clé privée.

Ajouter au fichier WireGuard du point constructeur :

```ini
[Peer]
PublicKey = CLE_PUBLIQUE_WIREGUARD_AGENCE
AllowedIPs = 10.203.0.2/32
```

Appliquer cette configuration dans une fenêtre de maintenance. Utiliser une adresse
/32 distincte pour chaque agence. Vérifier `sudo wg show` et le dernier échange.
La sortie à Internet de l’agence doit permettre UDP vers l’endpoint ; une liaison
qui bloque UDP exige une adaptation autorisée de l’infrastructure réseau.

Depuis le poste constructeur, utiliser les clés SSH configurées pour le relais et
pour l’agence, par exemple via des entrées `Host` dans `~/.ssh/config`. `ProxyJump`
utilise le relais, sans transférer la clé privée ni activer l’agent forwarding.
Exemple avec un compte de support réellement affiché lors de l’installation :

```powershell
ssh -J exploitation@support.exemple gutf-IDENTIFIANT@10.203.0.2 status
ssh -J exploitation@support.exemple gutf-IDENTIFIANT@10.203.0.2 diagnostic
ssh -J exploitation@support.exemple gutf-IDENTIFIANT@10.203.0.2 request > demande-agence.json
ssh -J exploitation@support.exemple gutf-IDENTIFIANT@10.203.0.2 backup
ssh -J exploitation@support.exemple gutf-IDENTIFIANT@10.203.0.2 restart
```

Sous Windows PowerShell 5, sa redirection de sortie peut écrire de l’UTF-16 :
convertir la demande reçue en UTF-8 avant `issue-license.php`, ou utiliser
PowerShell 7 / un transfert depuis un terminal UTF-8. Après signature locale,
transmettre le JSON signé sur l’entrée standard de la commande `import` ; sa
signature doit toujours rester valide et le fichier ne dépasse pas 16 Ko :

```bash
ssh -J exploitation@support.exemple gutf-IDENTIFIANT@10.203.0.2 import < configuration.json
```

Dans PowerShell, utiliser un transfert binaire par flux ou un terminal bash ; éviter
de réencoder arbitrairement le contenu du fichier signé. Seules les commandes
exactes `status`, `diagnostic`, `request`, `import`, `backup`, `restart` sont acceptées.
Les ajouts d’arguments, commandes shell, transferts SCP/SFTP et tunnels SSH sont
refusés pour le compte de support. Les actions sont tracées avec
`journalctl -t gutf-IDENTIFIANT` sur le serveur agence.

La clé SSH de support est limitée à l’adresse VPN du constructeur. Le compte ne fait
pas partie du groupe Docker et ne peut pas modifier ses restrictions. Les opérations
système complètes et les mises à jour se font avec le compte d’exploitation du site,
dans une intervention distincte. L’application fonctionne lorsque le VPN est arrêté.
Pour révoquer le support, retirer le pair côté constructeur et désactiver l’interface
de l’agence ; retirer aussi la clé publique SSH si l’accès doit être supprimé durablement.

### Lanceur de maintenance Windows sans conversion de fichiers

Le script constructeur `Invoke-Maintenance.ps1` fonctionne sous PowerShell 5 et 7.
Il transmet les fichiers par flux binaires, sans la redirection UTF-16 de PowerShell 5.
Il reste sur ton poste et n’est pas livré aux agences. Exemple :

```powershell
.\tools\constructor\Invoke-Maintenance.ps1 -Agence gutf-IDENTIFIANT@10.203.0.2 -Relais exploitation@support.exemple -Action request -Sortie C:\Dossiers-clients\agence\demande.json
.\tools\constructor\Invoke-Maintenance.ps1 -Agence gutf-IDENTIFIANT@10.203.0.2 -Relais exploitation@support.exemple -Action import -Fichier C:\Dossiers-clients\agence\configuration.json
.\tools\constructor\Invoke-Maintenance.ps1 -Agence gutf-IDENTIFIANT@10.203.0.2 -Relais exploitation@support.exemple -Action diagnostic
```

Remplacer `IDENTIFIANT` par les douze caractères hexadécimaux réellement affichés
par la configuration de maintenance. `-CleSsh` sélectionne une clé de support
existante ; les paramètres du relais peuvent aussi être fixés dans `~/.ssh/config`.
La commande refuse d’écraser une demande déjà enregistrée.
