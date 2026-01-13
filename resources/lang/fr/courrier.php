<?php

return [
    'title' => 'Gestion du courrier',
    'new' => 'Nouveau courrier',
    'ref' => 'Référence',
    'type' => 'Type',
    'type_in' => 'Entrant',
    'type_out' => 'Sortant',
    'subject' => 'Objet',
    'status' => 'Statut',
    'actions' => 'Actions',
    'view' => 'Voir',
    'empty' => 'Aucun courrier pour le moment.',

    'create_title' => 'Créer un courrier',
    'back' => 'Retour',
    'description' => 'Description',
    'external_ref' => 'Référence externe',
    'sender' => 'Expéditeur',
    'recipient' => 'Destinataire',
    'date_mail' => 'Date du courrier',
    'date_received' => 'Date de réception',
    'date_sent' => 'Date d’envoi',

    'assign_to' => 'Affecter à',
    'assign_none' => 'Ne pas affecter (V1)',
    'assign' => 'Affecter',

    'save' => 'Enregistrer',
    'cancel' => 'Annuler',

    'history' => 'Historique',
    'no_history' => 'Aucun historique.',

    'submit_validation' => 'Soumettre à validation',
    'validate' => 'Valider',
    'reject' => 'Refuser',
    'reject_reason' => 'Motif du refus',
    'archive' => 'Archiver',

    'form_error_title' => 'Veuillez corriger les erreurs ci-dessous.',

    // Attachments (upload only)
    'document_title' => 'Document (scan)',
    'document_help' => "Ajoutez le document du courrier (scan déjà fait) sous forme de fichier.",
    'document_optional' => 'Optionnel',

    'attachments' => 'Pièces jointes',
    'attachments_help' => 'Formats autorisés : PDF, JPG, PNG (max 10 Mo). Stockage privé.',
    'upload' => 'Uploader',
    'download' => 'Télécharger',
    'no_attachments' => 'Aucune pièce jointe.',

    'status_label' => [
        'recu' => 'Reçu',
        'affecte' => 'Affecté',
        'en_traitement' => 'En traitement',
        'en_validation' => 'En validation',
        'valide' => 'Validé',
        'rejete' => 'Rejeté',
        'archive' => 'Archivé',
    ],

    'no_attach_permission' => "Vous n’avez pas l’autorisation d’ajouter des pièces jointes.",

    'delete_attachment' => 'Supprimer',
    'confirm_delete_attachment' => 'Confirmer la suppression de cette pièce jointe ?',
    'preview' => 'Voir',

    'history_actions' => [
        'created' => 'Courrier créé',
        'assigned' => 'Courrier affecté',
        'submitted_for_validation' => 'Soumis à validation',
        'validated' => 'Courrier validé',
        'rejected' => 'Courrier rejeté',
        'archived' => 'Courrier archivé',
        'attachment_uploaded' => 'Pièce jointe ajoutée',
        'attachment_deleted' => 'Pièce jointe supprimée',
    ],

    'history_icons' => [
        'created' => '🆕',
        'assigned' => '📌',
        'submitted_for_validation' => '📤',
        'validated' => '✅',
        'rejected' => '❌',
        'archived' => '📦',
        'attachment_uploaded' => '📎',
        'attachment_deleted' => '🗑️',
    ],

    'history_meta' => [
        'reference_interne' => 'Référence interne',
        'assigned_to_user_id' => 'Affecté à (ID)',
        'validated_by_user_id' => 'Validé par (ID)',
        'reason' => 'Motif',
        'original_name' => 'Nom du fichier',
        'stored_name' => 'Nom interne',
        'path' => 'Chemin',
        'assigned_to' => 'Affecté à',
        'validated_by' => 'Validé par',
    ],

    'filters' => [
        'search' => 'Recherche',
        'search_placeholder' => 'Référence, objet, expéditeur, destinataire...',
        'type' => 'Type',
        'status' => 'Statut',
        'attachments' => 'Pièces jointes',
        'attachments_yes' => 'Avec pièces jointes',
        'attachments_no' => 'Sans pièces jointes',
        'all' => 'Tous',
        'apply' => 'Appliquer',
        'reset' => 'Réinitialiser',
        'scope' => 'Périmètre',
        'scope_mine' => 'Mes courriers',
        'scope_all' => 'Tous (Admin)',
        'assigned_to' => 'Assigné à',
        'date_from' => 'Du',
        'date_to' => 'Au',
    ],

    'assigned_to' => 'Assigné à',

    'export_excel' => 'Exporter Excel',
    'export_pdf' => 'Exporter PDF',
    'export_generated_at' => 'Généré le',
    'export_generated_by' => 'Utilisateur',
    'export_filters' => 'Filtres',
    'export_period' => 'Période',
    'export_date' => 'Date',

    'export_summary_title' => 'Résumé de l’export',
    'export_summary_kpis' => 'Indicateurs',
    'export_summary_total' => 'Total courriers',
    'export_summary_with_attachments' => 'Courriers avec pièces jointes',
    'export_summary_by_status' => 'Répartition par statut',
    'export_summary_by_type' => 'Répartition par type',
    'export_summary_by_assignee' => 'Répartition par assignation',
    'export_summary_count' => 'Nombre',








];
