<?php

return [
    'title' => 'Récupération par code',
    'intro' => 'Demandez un code au super-administrateur de votre agence après vérification de votre identité. Le serveur local doit rester accessible.',
    'option' => 'Utiliser un code de récupération', 'option_help' => 'Sans accès à la messagerie ? Un code temporaire peut vous être remis directement par le super-administrateur.',
    'code' => 'Code de récupération', 'code_help' => 'Usage unique, valable 15 minutes. Les espaces et tirets sont acceptés.',
    'submit' => 'Enregistrer mon nouveau mot de passe', 'back_email' => 'Recevoir plutôt un lien par e-mail',
    'invalid' => 'Code incorrect, expiré ou indisponible. Vérifiez les informations ou demandez un nouveau code au super-administrateur.',
    'success' => 'Votre mot de passe a été réinitialisé. Connectez-vous avec votre nouveau mot de passe.',
    'admin_title' => 'Récupération du compte', 'admin_intro' => 'Remettre un code temporaire après vérification de l’identité du titulaire.',
    'admin_password' => 'Votre mot de passe de super-administrateur', 'admin_password_invalid' => 'Votre mot de passe est incorrect.',
    'identity_checked' => 'J’ai vérifié l’identité du titulaire de ce compte et lui remettrai le code directement.',
    'generate' => 'Générer un code temporaire',
    'generate_help' => 'Le code est affiché une seule fois, expire après 15 minutes et remplace les codes précédents. Il ne change pas immédiatement le mot de passe.',
    'issued' => 'Code temporaire généré', 'once' => 'Remettez ce secret directement au titulaire. Il ne sera plus consultable après avoir quitté cette page.',
    'expires' => 'Expire le :time (:zone).', 'instructions' => 'Sur la même installation : Connexion → Mot de passe oublié → Utiliser un code de récupération. Le titulaire saisit son adresse e-mail comme identifiant, le code, puis choisit son nouveau mot de passe. Aucun e-mail n’est envoyé.',
    'inactive' => 'La récupération est indisponible pour un compte désactivé.',
    'history' => 'Historique des codes', 'history_help' => 'Les 20 derniers codes émis pour ce compte. Les secrets ne sont jamais affichés dans l’historique.',
    'empty' => 'Aucun code de récupération émis.', 'date' => 'Émis le', 'issuer' => 'Émis par', 'status' => 'État',
    'console' => 'Console du serveur', 'deleted_issuer' => 'Ancien administrateur',
    'used' => 'Utilisé', 'revoked' => 'Invalidé', 'expired' => 'Expiré', 'active' => 'Disponible',
    'used_at' => 'Utilisé le :time',
];
