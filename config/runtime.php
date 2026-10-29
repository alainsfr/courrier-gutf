<?php

return [
    'directory' => storage_path('app/private/.runtime'),
    'public_key' => base_path('config/runtime-public.pem'),
    'machine_id' => '/etc/machine-id',
    'machine_uuid' => '/sys/class/dmi/id/product_uuid',
    'clock_tolerance' => 300,
];
