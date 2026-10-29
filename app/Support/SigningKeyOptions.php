<?php

namespace App\Support;

class SigningKeyOptions
{
    public static function rsa(): array
    {
        $options = ['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 3072];
        $config = getenv('OPENSSL_CONF') ?: dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
        if (is_file($config)) {
            $options['config'] = $config;
        }

        return $options;
    }
}
