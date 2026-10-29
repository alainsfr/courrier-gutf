<?php

$proxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));
foreach ($proxies as $proxy) {
    $parts = explode('/', $proxy, 2);
    $bits = str_contains($parts[0], ':') ? 128 : 32;
    if (! filter_var($parts[0], FILTER_VALIDATE_IP)
        || (isset($parts[1]) && (! ctype_digit($parts[1]) || (int) $parts[1] < 1 || (int) $parts[1] > $bits))) {
        throw new RuntimeException('TRUSTED_PROXIES doit contenir des adresses IP ou sous-réseaux explicites.');
    }
}

return ['proxies' => $proxies];
