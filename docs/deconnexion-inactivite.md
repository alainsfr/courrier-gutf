# Déconnexion pour inactivité

## Règle appliquée

`SESSION_IDLE_TIMEOUT=30` fixe l'inactivité humaine maximale en minutes.
`SESSION_IDLE_WARNING_SECONDS=120` affiche un avertissement deux minutes avant.
`SESSION_LIFETIME=30` reste la durée technique du stockage de session.
Ce sont deux contrôles différents : les requêtes automatiques peuvent maintenir
le stockage technique en vie mais ne doivent pas repousser l'échéance humaine.

`EnforceIdleTimeout` vérifie l'échéance avant l'exécution du contrôleur, y compris
pour une requête de modification. Après expiration : déconnexion, invalidation
de session, nouveau jeton CSRF et réponse 401 JSON ou redirection vers la connexion.
Le serveur refuse de prolonger une session déjà expirée.

Une navigation normale ou `POST /session/activity` renouvelle l'activité.
`GET /session/status`, les requêtes JSON/XHR et les points de sondage connus
(notifications, mini-messagerie, documents) ne la renouvellent pas.
Le navigateur signale les interactions réelles, au plus une fois toutes les
30 secondes. Aucune minuterie ne simule une activité en l'absence d'interaction.

Le marqueur est propre à la connexion et conservé dans la session et le cache.
Le cache évite qu'une ancienne réponse de sondage concurrente ne rétablisse une
date d'activité antérieure. Aucune donnée du courrier n'est enregistrée dans ce marqueur.

## Expérience utilisateur

Un dialogue accessible présente le compte à rebours, « Continuer ma session »
et « Me déconnecter ». Continuer conserve le formulaire ouvert. Les données d'un
formulaire non enregistré ne sont pas sauvegardées automatiquement.

Les onglets du même navigateur synchronisent les échéances ; le serveur est
recontrôlé avant une expiration locale pour éviter de fermer un onglet lorsque
l'utilisateur travaille dans un autre. L'heure du serveur sert de référence.
Le retour au premier plan et la restauration d'une page déclenchent un contrôle.
Le contenu est masqué pendant le contrôle à échéance puis la connexion est affichée.

Une vérification de réseau échouée ne prolonge pas une session. Le dialogue
permet de réessayer avant l'échéance. La protection serveur reste effective sans JavaScript.

Les nouvelles connexions n'utilisent pas de cookie « Se souvenir de moi ».
Une restauration par un ancien cookie persistant est refusée et ce cookie est supprimé.
Une session antérieure au correctif et dépourvue de marqueur bénéficie d'une
initialisation unique ; la configuration du nouveau cookie en production exige
une nouvelle connexion et évite ce cas lors de cette mise en service.

## Production HTTPS

Conserver un cookie de session propre à cette application, un domaine hôte seul
(`SESSION_DOMAIN=null`), `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`
et `SESSION_SAME_SITE=lax`. Ne pas copier le nom du cookie de l'application de support.
Actualiser le cache de configuration après tout changement du `.env`.

Tests : sondage continu, expiration exacte, navigation active, prolongation,
refus de modification après expiration, refus d'une reconnexion persistante,
ancien écrit de session concurrent, fermeture et prolongation dans le navigateur.
