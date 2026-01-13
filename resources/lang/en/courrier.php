<?php

return [
    'title' => 'Mail management',
    'new' => 'New mail',
    'ref' => 'Reference',
    'type' => 'Type',
    'type_in' => 'Incoming',
    'type_out' => 'Outgoing',
    'subject' => 'Subject',
    'status' => 'Status',
    'actions' => 'Actions',
    'view' => 'View',
    'empty' => 'No mail items yet.',

    'create_title' => 'Create mail',
    'back' => 'Back',
    'description' => 'Description',
    'external_ref' => 'External reference',
    'sender' => 'Sender',
    'recipient' => 'Recipient',
    'date_mail' => 'Mail date',
    'date_received' => 'Received date',
    'date_sent' => 'Sent date',

    'assign_to' => 'Assign to',
    'assign_none' => 'Do not assign (V1)',
    'assign' => 'Assign',

    'save' => 'Save',
    'cancel' => 'Cancel',

    'history' => 'History',
    'no_history' => 'No history.',

    'submit_validation' => 'Submit for validation',
    'validate' => 'Validate',
    'reject' => 'Reject',
    'reject_reason' => 'Rejection reason',
    'archive' => 'Archive',

    'form_error_title' => 'Please fix the errors below.',

    // Attachments (upload only)
    'document_title' => 'Document (scan)',
    'document_help' => 'Attach the scanned document as a file (already scanned).',
    'document_optional' => 'Optional',

    'attachments' => 'Attachments',
    'attachments_help' => 'Allowed: PDF, JPG, PNG (max 10MB). Stored privately.',
    'upload' => 'Upload',
    'download' => 'Download',
    'no_attachments' => 'No attachments.',

    'status_label' => [
        'recu' => 'Received',
        'affecte' => 'Assigned',
        'en_traitement' => 'In progress',
        'en_validation' => 'Pending validation',
        'valide' => 'Validated',
        'rejete' => 'Rejected',
        'archive' => 'Archived',
    ],

    'no_attach_permission' => 'You do not have permission to add attachments.',

    'delete_attachment' => 'Delete',
    'confirm_delete_attachment' => 'Confirm deletion of this attachment?',
    'preview' => 'Voir',

    'history_actions' => [
        'created' => 'Mail created',
        'assigned' => 'Mail assigned',
        'submitted_for_validation' => 'Submitted for validation',
        'validated' => 'Mail validated',
        'rejected' => 'Mail rejected',
        'archived' => 'Mail archived',
        'attachment_uploaded' => 'Attachment added',
        'attachment_deleted' => 'Attachment deleted',
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
        'reference_interne' => 'Internal reference',
        'assigned_to_user_id' => 'Assigned to (ID)',
        'validated_by_user_id' => 'Validated by (ID)',
        'reason' => 'Reason',
        'original_name' => 'File name',
        'stored_name' => 'Stored name',
        'path' => 'Path',
        'assigned_to' => 'Assigned to',
        'validated_by' => 'Validated by',
    ],


    'filters' => [
        'search' => 'Search',
        'search_placeholder' => 'Reference, subject, sender, recipient...',
        'type' => 'Type',
        'status' => 'Status',
        'attachments' => 'Attachments',
        'attachments_yes' => 'With attachments',
        'attachments_no' => 'Without attachments',
        'all' => 'All',
        'apply' => 'Apply',
        'reset' => 'Reset',
        'scope' => 'Scope',
        'scope_mine' => 'My mail',
        'scope_all' => 'All (Admin)',
        'date_from' => 'From',
        'date_to' => 'To',
    ],

    'assigned_to' => 'Assigned to',

    'export_excel' => 'Export Excel',
    'export_pdf' => 'Export PDF',
    'export_generated_at' => 'Generated at',
    'export_generated_by' => 'User',
    'export_filters' => 'Filters',
    'export_period' => 'Period',
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
