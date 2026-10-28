<?php

return [
    'title' => 'Recover with a code',
    'intro' => 'Ask your branch super-administrator for a code after verifying your identity. The local server must remain accessible.',
    'option' => 'Use a recovery code', 'option_help' => 'Cannot access email? Your super-administrator can hand you a temporary code directly.',
    'code' => 'Recovery code', 'code_help' => 'Single use, valid for 15 minutes. Spaces and hyphens are accepted.',
    'submit' => 'Save my new password', 'back_email' => 'Receive an email link instead',
    'invalid' => 'Incorrect, expired or unavailable code. Check your details or ask your super-administrator for a new code.',
    'success' => 'Your password has been reset. Sign in with your new password.',
    'admin_title' => 'Account recovery', 'admin_intro' => 'Hand over a temporary code after verifying the account owner’s identity.',
    'admin_password' => 'Your super-administrator password', 'admin_password_invalid' => 'Your password is incorrect.',
    'identity_checked' => 'I verified the account owner’s identity and will hand the code to them directly.',
    'generate' => 'Generate a temporary code',
    'generate_help' => 'The code is shown once, expires after 15 minutes and replaces previous codes. It does not change the password immediately.',
    'issued' => 'Temporary code generated', 'once' => 'Hand this secret directly to the account owner. It cannot be displayed again after leaving this page.',
    'expires' => 'Expires at :time (:zone).', 'instructions' => 'On this same installation: Sign in → Forgot password → Use a recovery code. The account owner enters their email address as their username, the code and a new password. No email is sent.',
    'inactive' => 'Recovery is unavailable for a disabled account.',
    'history' => 'Code history', 'history_help' => 'The last 20 codes issued for this account. Secrets are never displayed in history.',
    'empty' => 'No recovery code has been issued.', 'date' => 'Issued at', 'issuer' => 'Issued by', 'status' => 'Status',
    'console' => 'Server console', 'deleted_issuer' => 'Former administrator',
    'used' => 'Used', 'revoked' => 'Invalidated', 'expired' => 'Expired', 'active' => 'Available',
    'used_at' => 'Used at :time',
];
