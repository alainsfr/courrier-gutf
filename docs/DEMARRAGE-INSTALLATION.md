# Installer COURRIER GUTF dans une agence

Un seul paquet contient l’application et sa base de données, pour Linux et Windows.
Il est destiné aux serveurs **x86_64 / amd64**. Les fichiers sont prêts à être utilisés
sans téléchargement de dépendances applicatives.

## Linux

Préparer Ubuntu Server 24.04 LTS ou Debian 12 avec Python 3, Docker Engine et
Docker Compose 2.30 minimum, puis extraire l’archive. Dans le dossier extrait :

```bash
sudo bash install-linux.sh --instance agence-centre --url http://192.168.1.20:8088 --bind 192.168.1.20 --port 8088 --admin-email admin@agence.example
```

Le mot de passe du premier administrateur est demandé sans être affiché.
Pour une installation derrière un reverse proxy HTTPS existant, conserver
`--bind 127.0.0.1` et fournir l’URL HTTPS définitive.

## Windows 10/11 et Windows Server

Créer une VM Linux x86_64 disposant de sa propre adresse sur le réseau de l’agence.
Utiliser Hyper-V s’il est disponible, ou VirtualBox de base sans Extension Pack.
Préparer Docker Engine et OpenSSH **dans cette VM**. Depuis le dossier extrait,
ouvrir PowerShell :

```powershell
.\Installer-Windows.ps1 -ServeurLinux exploitation@192.168.1.20 -Instance agence-centre -Url http://192.168.1.20:8088 -AdresseEcoute 192.168.1.20 -Port 8088 -EmailAdministrateur admin@agence.example
```

Le lanceur vérifie le paquet, le transfère dans la VM et lance l’installation.
Il demande les identifiants existants de la VM et le mot de passe du nouvel
administrateur à travers la connexion SSH. Docker Desktop n’est pas nécessaire.

## Avant l’ouverture aux utilisateurs

Effectuer la mise en service avec le constructeur, vérifier les sauvegardes et
configurer HTTPS selon le manuel technique. Les adresses ci-dessus sont des exemples.
Le mode HTTP est réservé à la préparation et à un réseau isolé de recette.

Les procédures détaillées, la cohabitation avec d’autres logiciels, l’accès gratuit
de maintenance à distance, les sauvegardes et les mises à jour sont décrits dans
**MANUEL-TECHNIQUE.md**, inclus dans cette archive.
