<?php

return [
    'title' => 'Notifications',
    'subtitle' => 'Track assignments, validations, and SLA alerts.',
    'unread' => 'Unread',
    'read' => 'Read',
    'none' => 'No notifications.',
    'none_unread' => 'No unread notifications',
    'none_unread_hint' => 'New assignments, validations, and overdue alerts will show up here.',
    'none_read' => 'No history yet.',
    'unread_count' => ':count unread|:count unread',
    'open' => 'Open',
    'mark_read' => 'Mark read',
    'mark_all' => 'Mark all as read',
    'marked_read' => 'Notification marked as read.',
    'all_read' => 'All notifications have been marked as read.',

    'type_assigned' => 'Assignment',
    'type_validation' => 'Validation',
    'type_validated' => 'Validated',
    'type_rejected' => 'Rejected',
    'type_late' => 'SLA overdue',
    'type_info' => 'Info',

    'courrier_assigned_title' => 'New mail assigned',
    'courrier_assigned_msg' => 'Mail :ref has been assigned to you.',

    'courrier_validation_title' => 'Mail to validate',
    'courrier_validation_msg' => 'Mail :ref is pending validation.',

    'courrier_validated_title' => 'Mail validated',
    'courrier_validated_msg' => 'Mail :ref has been validated.',

    'courrier_rejected_title' => 'Mail rejected',
    'courrier_rejected_msg' => 'Mail :ref was rejected. Reason: :reason',

    'courrier_late_title' => 'Overdue mail (SLA)',
    'courrier_late_msg' => 'Mail :ref exceeded the deadline for step “:etape” (due : :limite).',
];
