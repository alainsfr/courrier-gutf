# COURRIER GUTF — Manuel technique d’installation et d’exploitation

Version du 29 octobre 2026. Destinataires : responsables d’infrastructure et
administrateurs chargés du serveur. Le manuel utilisateur reste un document distinct.

## 1. Architecture et périmètre

Le paquet commun Linux/Windows contient les images de l’application et de MariaDB,
les scripts d’installation et ce manuel. Il ne contient aucune donnée de client.
Le système cible est **Linux x86_64 / amd64**. Sur Windows, il fonctionne dans une
machine virtuelle Linux ; les navigateurs des utilisateurs y accèdent sur le réseau.
Il ne s’agit pas d’une installation PHP native Windows.

Quatre services dédiés sont créés : application Apache/PHP, MariaDB, file de travaux
et planificateur. Le code, PHP, les extensions, les graphiques et le tableau Kanban
sont fournis localement. Aucun service cloud ni accès Internet n’est nécessaire au
fonctionnement quotidien. L’envoi de courriels exige un serveur SMTP joignable ;
la récupération de compte par code administrateur fonctionne sur le réseau local.

L’archive ne remplace pas le système d’exploitation, l’hyperviseur ou Docker Engine.
Ces prérequis doivent être préparés avant une installation totalement hors ligne.
La maintenance à distance exige une connexion Internet temporaire et un point
WireGuard joignable sous le contrôle du constructeur. Les logiciels retenus sont
gratuits ; une éventuelle machine hébergeant ce point d’accès reste une ressource
d’infrastructure à fournir, pas un abonnement applicatif.

## 2. Cohabitation avec d’autres applications

Chaque instance utilise un nom Compose `gutf-<identifiant>`, son réseau privé,
ses volumes de données et son cookie de session. La base n’expose aucun port sur
l’hôte. Seul un port HTTP choisi, supérieur ou égal à 1024, est publié. Par défaut,
il écoute sur `127.0.0.1:8088` pour un reverse proxy existant. Un port déjà occupé ou
un identifiant possédant encore des données provoque l’arrêt de l’installation.

Le script ne remplace pas Apache, Nginx, IIS, PHP ou MySQL de l’hôte ; il ne modifie
pas les sites, le pare-feu, les tâches planifiées ou les variables globales des
autres applications. Il ne crée pas de conteneur privilégié et ne monte pas le
socket Docker dans l’application. Les tâches propres à l’agence tournent dans ses
conteneurs, sans modifier le crontab de l’hôte.

Prévoir un port et un identifiant distincts pour chaque instance. Éviter de partager
une adresse de navigateur entre applications avec des chemins différents : utiliser
un nom DNS par application ou des ports différents. Dimensionner la mémoire et le
disque pour l’ensemble des logiciels : les limites évitent une consommation illimitée,
mais ne réservent pas de capacité physique.

## 3. Prérequis et préparation

Référence d’installation : Ubuntu Server 24.04 LTS ou Debian 12, Python 3.9 minimum,
Docker Engine actif et Docker Compose **2.30 minimum**. Cible initiale : 2 processeurs
virtuels, 4 Go de mémoire et 40 Go de disque pour une petite agence, à ajuster selon
les pièces jointes et le nombre d’utilisateurs. Les limites livrées sont 512 Mo et
1 CPU par service applicatif, 768 Mo et 1 CPU pour MariaDB. Conserver de la marge
pour le système et les autres applications ; superviser l’espace libre.

Installer Docker depuis la documentation officielle correspondant au système :
[Docker Engine](https://docs.docker.com/engine/install/) et
[Docker Compose](https://docs.docker.com/compose/install/linux/). Ne pas désinstaller
automatiquement un Docker existant : contrôler sa version et ses autres projets.

```bash
python3 --version
sudo docker info
sudo docker compose version
uname -m
timedatectl status
```

Configurer une adresse stable et un nom local, par exemple `courrier.agence.example`.
Maintenir une heure correcte. Une VM clonée doit recevoir une identité système
unique ; ne pas recopier l’état d’une agence existante pour créer une autre agence.

Pour un site sans Internet, préparer auparavant le système, Docker et leurs paquets
sur une machine de même distribution/architecture, ou fournir une VM Linux vierge
déjà préparée. Transférer ensuite l’archive applicative par support amovible. Une
fois ces prérequis installés, le script ne contacte aucun registre d’images ni npm.

## 4. Vérifier l’archive

Conserver le fichier `.sha256` obtenu avec l’archive. Comparer son empreinte à celle
transmise par le constructeur par un canal de confiance. La somme détecte une
altération ; elle ne prouve pas l’origine si l’archive et la somme ont été remplacées
ensemble.

```bash
sha256sum -c courrier-gutf-agence-<version>-linux-windows-amd64.zip.sha256
unzip courrier-gutf-agence-<version>-linux-windows-amd64.zip
cd courrier-gutf-agence
```

Sous Windows :

```powershell
Get-FileHash .\courrier-gutf-agence-<version>-linux-windows-amd64.zip -Algorithm SHA256
Expand-Archive .\courrier-gutf-agence-<version>-linux-windows-amd64.zip -DestinationPath C:\Installations\CourrierGutf
Set-Location C:\Installations\CourrierGutf\courrier-gutf-agence
.\Installer-Windows.ps1 -VerifierSeulement
```

Si Windows marque le script comme provenant d’Internet, vérifier d’abord l’origine
et les empreintes, puis utiliser `Unblock-File` sur ce seul script. Ne pas modifier
la politique d’exécution globale du serveur. Les empreintes internes de tous les
fichiers sont contrôlées avant l’installation.

## 5. Installation directe sur Linux

Dans le dossier extrait :

```bash
sudo bash install-linux.sh \
  --instance agence-centre \
  --url https://courrier.agence.example \
  --bind 127.0.0.1 \
  --port 8088 \
  --admin-email administration@agence.example
```

Le script demande et confirme le mot de passe initial, charge les images locales,
crée une base vierge, applique les migrations, prépare les bureaux et droits puis
crée le premier super-administrateur. Aucun courrier ou compte de démonstration n’est
ajouté. Le compte est utilisable sans validation par courriel. Le mot de passe n’est
pas conservé dans les fichiers de configuration.

Le dossier par défaut est `/opt/courrier-gutf/agence-centre`. `--directory` permet
de choisir un autre dossier dédié. Un dossier existant non vide est refusé ; le
script ne remplace jamais automatiquement une installation ou ses données.
`--admin-name` personnalise le nom du premier administrateur.

Pour une recette sur réseau isolé avant configuration HTTPS :

```bash
sudo bash install-linux.sh --instance agence-recette --url http://192.168.1.20:8089 --bind 192.168.1.20 --port 8089 --admin-email administration@agence.example
```

Ouvrir uniquement ce port dans le pare-feu pour le sous-réseau de l’agence si la
politique existante le nécessite. Le script ne crée aucune règle de pare-feu.
Ne pas publier le port de la base ni les fichiers `.env`, `app.env`, `db.env`.

## 6. Installation depuis Windows 10/11 ou Windows Server

### 6.1 Préparer la VM une seule fois

Sur Windows Server ou une édition Windows disposant d’Hyper-V, utiliser le
gestionnaire Hyper-V. Sur une édition sans Hyper-V, utiliser
[VirtualBox de base](https://www.virtualbox.org/wiki/Downloads), sans Extension Pack.
Docker Desktop n’est ni requis ni utilisé ; il n’est pas pris en charge sur Windows
Server. L’ajout d’un hyperviseur peut demander un redémarrage : le planifier avec
le responsable des autres applications et ne pas modifier leur virtualisation.

Créer une VM Linux de génération 2 si Hyper-V est utilisé, 2 vCPU, 4 Go RAM minimum,
40 Go de disque extensible, avec démarrage automatique après remise en route de
l’hôte. Installer Ubuntu Server 24.04 LTS ou Debian 12 x86_64. Prévoir une adresse
stable visible depuis le réseau local, via un commutateur externe existant ou un
adaptateur en pont. La création d’un nouveau commutateur doit être planifiée : elle
peut modifier temporairement le réseau de l’hôte. Aucun commutateur n’est créé par
le lanceur applicatif.

Dans la VM, activer OpenSSH Server, créer un compte d’exploitation avec `sudo`,
puis installer Docker Engine et Compose comme en section 3. Tester depuis Windows :

```powershell
ssh exploitation@192.168.1.20
```

Comparer l’empreinte SSH affichée à celle de la console de la VM avant de l’accepter.
Le client OpenSSH Windows est un prérequis ; il peut être ajouté via les fonctionnalités
facultatives de Windows. Le lanceur refuse de continuer si `ssh` ou `scp` manque.

### 6.2 Lancer le transfert et l’installation

Dans PowerShell, depuis le dossier extrait :

```powershell
.\Installer-Windows.ps1 `
  -ServeurLinux exploitation@192.168.1.20 `
  -Instance agence-centre `
  -Url https://courrier.agence.example `
  -Port 8088 `
  -EmailAdministrateur administration@agence.example
```

Pour une recette directe dans le LAN, ajouter `-AdresseEcoute 192.168.1.20` et fournir
l’URL HTTP avec son port. `-CleSsh C:\chemin\cle-privee` utilise une clé SSH existante ;
elle n’est pas transférée dans la VM. Le compte SSH et ses éventuels mots de passe
restent ceux de la VM. Le mot de passe du nouvel administrateur est saisi dans la
session SSH, sans copie dans la ligne de commande.

La VM reçoit un dossier temporaire unique sous `/tmp/courrier-gutf-...`. Conserver
ce dossier le temps de la validation, puis retirer **uniquement ce chemin identifié**.
L’application reste dans `/opt/courrier-gutf/<instance>`. Les utilisateurs n’installent
rien : ils ouvrent l’URL depuis leur navigateur Windows, Linux ou mobile.

## 7. HTTPS et intégration au serveur web existant

Utiliser un nom DNS dédié, avec un certificat émis par l’autorité interne de l’agence
si elle n’a pas Internet. Installer cette autorité dans les postes clients selon la
politique de l’agence. Aucun certificat fictif ni contournement d’alerte n’est ajouté
par le paquet.

Ajouter un site distinct dans le reverse proxy existant. Le backend est
`http://127.0.0.1:8088` si le proxy est sur le même hôte Linux ; s’il est sur l’hôte
Windows, publier le port uniquement sur l’IP de la VM et limiter son accès au proxy.
Préserver le nom d’hôte et transmettre `X-Forwarded-Proto: https` pour le site.
La section suivante donne les paramètres applicatifs nécessaires ; le nom et le
certificat sont propres à l’agence, jamais ceux de PlanetHoster.

Exemple Nginx, dans un **nouveau** bloc de site, avec certificats déjà préparés :

```nginx
server {
    listen 443 ssl;
    server_name courrier.agence.example;
    ssl_certificate /etc/ssl/agence/courrier-fullchain.pem;
    ssl_certificate_key /etc/ssl/agence/courrier-key.pem;
    client_max_body_size 25m;
    location / {
        proxy_pass http://127.0.0.1:8088;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

Tester la configuration du proxy avant son rechargement. Pour Apache ou IIS ARR,
créer également un site/nom dédié et une règle de proxy limitée à ce site ; suivre
la procédure déjà utilisée pour les autres applications du serveur.

## 8. Configuration de l’agence et première recette

Les fichiers privés sont détenus par root, accessibles uniquement avec `sudo` :

Lorsqu’une procédure utilise `cd` vers ce dossier protégé, ouvrir d’abord une
session avec `sudo -i`, puis la quitter avec `exit` après intervention. Ne pas
élargir les permissions du dossier pour permettre une navigation ordinaire.

| Fichier | Rôle |
|---|---|
| `.env` | Identifiant Compose, images, interface et port publiés |
| `app.env` | URL, clé applicative, session, SMTP et mot de passe de sauvegarde |
| `db.env` | Identifiants de la base dédiée |
| `installation.json` | Référence de l’installation terminée |
| `release.json` | Révision et identités des images fournies |

`app.env` est lu au format Compose **raw** : écrire `CLE=valeur` sans guillemets
ajoutés ni échappement des dollars. Une valeur occupe une seule ligne.
Ne pas changer `APP_KEY`, les identifiants de base ou le mot de passe de sauvegarde
en routine. Le mot de passe d’archive est indispensable pour restaurer les sauvegardes
chiffrées ; le conserver séparément, avec les procédures de secours.

Pour HTTPS : `APP_URL=https://courrier.agence.example`, `SESSION_SECURE_COOKIE=true`.
Configurer la confiance du proxy uniquement pour les adresses du proxy contrôlé,
selon le paramètre `TRUSTED_PROXIES` décrit dans les fichiers applicatifs. Ne jamais
faire confiance à tous les relais d’un réseau non maîtrisé.

Pour un proxy installé sur l’hôte Linux, relever la passerelle du réseau du conteneur
et confirmer que les requêtes proviennent effectivement de cette adresse :

```bash
cd /opt/courrier-gutf/agence-centre
sudo docker inspect "$(sudo docker compose ps -q app)" --format '{{range .NetworkSettings.Networks}}{{.Gateway}}{{end}}'
```

Reporter cette adresse explicite dans `TRUSTED_PROXIES` de `app.env`. Pour plusieurs
proxies contrôlés, séparer les adresses par une virgule. Les jokers et les routes
universelles sont refusés ; la valeur reste vide sans reverse proxy.

Sans SMTP, conserver `MAIL_MAILER=log` et privilégier la récupération de compte par
code administrateur. Les courriels sont alors écrits dans les journaux, pas envoyés.
Avec SMTP, renseigner `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, puis tester la réception.

Après modification, recréer les services applicatifs pour relire leur environnement :

```bash
cd /opt/courrier-gutf/agence-centre
sudo docker compose up -d --force-recreate app queue scheduler
```

Effectuer la mise en service avec le constructeur puis vérifier : connexion du
super-administrateur, bureaux et droits, création et affectation d’un courrier,
traitement et archivage dans le bureau destinataire, pièces jointes, graphiques et
Kanban sans Internet, déconnexion après inactivité et récupération par code.
Ouvrir ensuite l’accès aux utilisateurs de l’agence.

## 9. Exploitation courante et sauvegardes

```bash
sudo python3 /opt/courrier-gutf/agence-centre/control.py status
sudo python3 /opt/courrier-gutf/agence-centre/control.py backup
sudo python3 /opt/courrier-gutf/agence-centre/control.py restart
sudo python3 /opt/courrier-gutf/agence-centre/control.py stop
sudo python3 /opt/courrier-gutf/agence-centre/control.py start
```

Les commandes ciblent uniquement les services de cette instance. `restart` relance
l’application, le planificateur et la file ; il ne redémarre pas MariaDB. `stop`
arrête les services, conserve les volumes et ne supprime aucune donnée.
Après installation, laisser cinq minutes au premier témoin d’exploitation ou
demander au constructeur de lancer un contrôle immédiat.

Les sauvegardes quotidiennes sont préparées à 02:00, nettoyées à 03:00, heure
`Africa/Douala` par défaut. Elles comprennent la base et les pièces jointes.
Contrôler leur présence et leur réception hors du serveur. Un volume Docker seul
ne remplace pas une sauvegarde ; une archive sur le même disque ne couvre pas une
panne physique. Les sauvegardes chiffrées nécessitent leur mot de passe conservé
séparément.

Après une sauvegarde réussie, depuis le dossier d’installation :

```bash
sudo mkdir -m 700 /chemin-prive/export-courrier
sudo docker compose cp app:/var/www/html/storage/app/backups/. /chemin-prive/export-courrier/
```

Copier les archives sur un support ou serveur de sauvegarde approuvé. Conserver
aussi les paramètres privés nécessaires à une reconstruction, dans un coffre
administratif. Les journaux des conteneurs tournent avec une limite de trois
fichiers de 10 Mo par service ; les journaux d’exploitation quotidiens sont purgés
après quatorze jours par le planificateur.

## 10. Maintenance à distance sans abonnement

L’agence reste autonome lorsque la maintenance est indisponible. WireGuard et
OpenSSH ne nécessitent aucun service tiers. Le constructeur fournit un point
WireGuard avec une adresse Internet joignable et un port UDP autorisé, par exemple
51820. Il peut s’agir d’une machine existante sous son contrôle. Internet seul sur
le serveur de l’agence ne suffit pas à créer cet accès.

L’accès est configuré lors d’une intervention prévue avec le responsable du site.
Installer `wireguard`, `openssh-server` et `sudo` dans le serveur/VM Linux depuis
les paquets officiels. Le tunnel de l’agence est une connexion **sortante** ; aucune
redirection du routeur de l’agence n’est nécessaire. Les équipements doivent autoriser
le trafic UDP vers l’endpoint constructeur. Un réseau interdisant ce trafic exige
une intervention du responsable réseau ; le paquet ne contourne pas sa politique.

Le script `maintenance/configure-maintenance.py` crée un compte de support distinct,
une clé WireGuard propre à l’agence et une interface dédiée. Le constructeur fournit
sa clé publique SSH, sa clé publique WireGuard, l’endpoint et deux adresses VPN /32.
Il n’ajoute aucune route par défaut, aucun DNS, aucun accès au reste du LAN.

L’exécution du script, l’association du pair sur le serveur constructeur et la
validation des clés font partie de la mise en service de maintenance. Les détails
réservés au constructeur sont dans son manuel distinct.

Le compte de support n’a ni shell libre ni accès général à Docker. Les actions
permises sont le contrôle d’état, une sauvegarde, le redémarrage applicatif et les
opérations de configuration constructeur. Chaque action produit une trace système.
Le dépannage système complet et les mises à jour demandent un compte d’exploitation
distinct et une intervention planifiée.

Pour suspendre un tunnel, utiliser son nom exact communiqué à l’installation :

```bash
sudo systemctl stop wg-quick@wg-gXXXXXXXX
```

Pour le rétablir : `sudo systemctl start wg-quick@wg-gXXXXXXXX`. La suspension ne
change pas le fonctionnement de COURRIER GUTF. Le responsable réseau doit s’assurer
que SSH n’est pas exposé publiquement et que les règles existantes sont préservées.

## 11. Mise à jour planifiée

La première version du paquet n’installe pas de mise à jour automatique. Les images
sont figées et conservées localement. Pour une nouvelle livraison, faire une
sauvegarde et effectuer une restauration de recette avant de modifier l’agence.
Conserver les anciens fichiers `.env`, `app.env`, `db.env`, le dossier de travail et
les volumes ; **ne pas relancer le script de première installation dessus**.

Le constructeur vérifie les empreintes de la nouvelle livraison, charge ses images
avec `docker load`, relève les nouveaux noms dans `release.json`, puis modifie
uniquement `GUTF_IMAGE` dans le `.env` de l’installation. Le changement de version
majeure de MariaDB constitue une intervention séparée : ne pas remplacer son image
à chaque mise à jour applicative.

Depuis le dossier protégé de l’instance :

```bash
sudo docker compose exec -T app php artisan down
sudo docker compose stop app queue scheduler
sudo docker compose run --rm -T --no-deps app php artisan migrate --force
sudo docker compose up -d --wait
sudo docker compose exec -T app php artisan up
```

Vérifier ensuite l’accès et les opérations planifiées. Un retour à l’ancienne image
n’annule pas des migrations de base : décider d’un retour arrière avec le constructeur
à partir d’une sauvegarde testée. Ne jamais utiliser `docker system prune`, une
suppression globale de volumes ou une commande visant les projets voisins.

## 12. Incidents et reconstruction

| Symptôme | Première vérification |
|---|---|
| Port déjà occupé | Choisir un autre port disponible, sans arrêter l’autre application. |
| Docker indisponible | Vérifier son service et la virtualisation ; ne pas le réinstaller aveuglément. |
| Connexion web impossible | Vérifier `docker compose ps`, DNS, IP, proxy, certificat et port autorisé. |
| Erreur après première installation interrompue | Conserver le dossier et les journaux ; faire reprendre la préparation par le constructeur. Ne pas effacer les volumes. |
| Témoins de file/planificateur absents | Vérifier les deux services et attendre leur premier passage. |
| E-mail non reçu | Vérifier SMTP et son accessibilité depuis la VM ; utiliser le code de récupération si nécessaire. |
| Action indisponible | Contacter l’administrateur, puis le support constructeur. |
| Disque plein | Exporter les sauvegardes, examiner la rétention et agrandir le disque sans supprimer de pièces jointes. |

Une reconstruction complète exige une nouvelle instance de recette, le paquet
correspondant aux migrations sauvegardées, la base, les pièces jointes et les secrets
conservés séparément. Faire restaurer et valider la recette par le constructeur avant
de basculer DNS et utilisateurs. La procédure n’efface jamais automatiquement les
données d’une agence existante.

## 13. Références officielles

- [Isolation des projets Docker Compose](https://docs.docker.com/compose/how-tos/project-name/)
- [Installer Docker Engine](https://docs.docker.com/engine/install/)
- [Créer une VM Hyper-V](https://learn.microsoft.com/en-us/windows-server/virtualization/hyper-v/get-started/create-a-virtual-machine-in-hyper-v)
- [Conditions des composants VirtualBox](https://www.virtualbox.org/wiki/Licensing_FAQ)
- [WireGuard : configuration et maintien des connexions](https://www.wireguard.com/quickstart/)
