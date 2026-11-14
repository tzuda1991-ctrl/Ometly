FROM php:8.1-apache-bookworm

RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN printf "error_reporting = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED\nupload_max_filesize = 100M\npost_max_size = 100M\nmemory_limit = 256M\n" > /usr/local/etc/php/conf.d/orange.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN rm -rf vendor bootstrap/cache/*.php bootstrap/cache/*.tmp \
    && composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts \
    && php artisan package:discover --ansi

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\nAlias /public/storage /var/www/html/storage/app/public\n' >> /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
