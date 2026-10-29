#!/bin/sh
set -eu
umask 027
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/framework/operations storage/logs /tmp/apache2
exec "$@"
