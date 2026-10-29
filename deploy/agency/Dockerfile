FROM node:22-bookworm-slim AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libonig-dev libicu-dev \
    mariadb-client unzip util-linux ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pdo_mysql gd mbstring zip bcmath intl pcntl exif \
    && a2enmod rewrite headers remoteip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY resources ./resources
COPY routes ./routes
COPY public ./public
COPY artisan ./artisan
COPY deploy/run-operations.sh ./deploy/run-operations.sh
COPY deploy/agency/issuer-public.pem ./config/runtime-public.pem
COPY --from=assets /build/public/build ./public/build
COPY deploy/agency/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/agency/php.ini /usr/local/etc/php/conf.d/agency.ini
COPY deploy/agency/entrypoint.sh /usr/local/bin/agency-entrypoint
RUN printf "<?php\nreturn ['managed' => true];\n" > config/distribution.php \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && rm -f bootstrap/cache/*.php public/hot \
    && composer dump-autoload --no-dev --no-scripts --optimize \
    && php artisan package:discover --ansi \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's@/var/run/apache2@/tmp/apache2@g; s@/var/lock/apache2@/tmp/apache2@g' /etc/apache2/envvars \
    && chmod 755 /usr/local/bin/agency-entrypoint deploy/run-operations.sh \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -s /var/www/html/storage/app/public public/storage \
    && (test -e /usr/bin/mysqldump || ln -s /usr/bin/mariadb-dump /usr/local/bin/mysqldump)
ENV APACHE_RUN_DIR=/tmp/apache2 APACHE_LOCK_DIR=/tmp/apache2 APACHE_PID_FILE=/tmp/apache2/apache2.pid
USER www-data
EXPOSE 8080
ENTRYPOINT ["agency-entrypoint"]
CMD ["apache2-foreground"]
