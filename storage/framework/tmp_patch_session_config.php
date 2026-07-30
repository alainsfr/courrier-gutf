<?php

$f = __DIR__ . '/../../config/session.php';
$c = file_get_contents($f);
$c = str_replace("env('SESSION_LIFETIME', 120)", "env('SESSION_LIFETIME', 30)", $c);
$c = str_replace("env('SESSION_ENCRYPT', false)", "env('SESSION_ENCRYPT', true)", $c);

// Update comment block for lifetime
$c = preg_replace(
    '/\| Here you may specify the number of minutes that you wish the session\r?\n\s+\| to be allowed to remain idle before it expires\. If you want them\r?\n\s+\| to expire immediately when the browser is closed then you may\r?\n\s+\| indicate that via the expire_on_close configuration option\./',
    "| Idle timeout in minutes before the session expires (auto-logout).\n    | Institutional default: 30 minutes of inactivity.",
    $c
);

file_put_contents($f, $c);
echo "session config patched\n";
