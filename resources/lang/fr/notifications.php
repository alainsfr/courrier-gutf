<?php

return [
    'title' => 'Notifications',
    'subtitle' => 'Affectations, validations et alertes SLA.',
    'unread' => 'Non lues',
    'read' => 'Lues',
    'none' => 'Aucune notification.',
    'none_unread' => 'Aucune notification non lue',
    'none_unread_hint' => 'Les nouvelles affectations, validations et retards apparaîtront ici.',
    'none_read' => 'Aucun historique pour le moment.',
    'unread_count' => ':count non lue|:count non lues',
    'open' => 'Ouvrir',
    'mark_read' => 'Marquer lue',
    'mark_all' => 'Tout marquer comme lu',
    'marked_read' => 'Notification marquée comme lue.',
    'all_read' => 'Toutes les notifications ont été marquées comme lues.',

    'type_assigned' => 'Affectation',
    'type_validation' => 'Validation',
    'type_validated' => 'Validé',
    'type_rejected' => 'Rejet',
    'type_late' => 'Retard SLA',
    'type_info' => 'Info',

    'courrier_assigned_title' => 'Nouveau courrier affecté',
    'courrier_assigned_msg' => 'Le courrier :ref vous a été affecté.',

    'courrier_validation_title' => 'Courrier à valider',
    'courrier_validation_msg' => 'Le courrier :ref est en attente de validation.',

    'courrier_validated_title' => 'Courrier validé',
    'courrier_validated_msg' => 'Le courrier :ref a été validé.',

    'courrier_rejected_title' => 'Courrier rejeté',
    'courrier_rejected_msg' => 'Le courrier :ref a été rejeté. Motif : :reason',

    'courrier_late_title' => 'Courrier en retard (SLA)',
    'courrier_late_msg' => 'Le courrier :ref a dépassé le délai de l’étape « :etape » (limite : :limite).',
];
