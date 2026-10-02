FROM php:8.2-apache-bookworm

# Extensions natives de l'application ; les paquets PHP Debian ne sont pas utilisés.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 gd pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && composer check-platform-reqs --no-dev

# Liste explicite : aucun document privé, clé, fichier .local.php ou dump personnel.
COPY Config/ Config/
COPY Controllers/ Controllers/
COPY Templates/ Templates/
COPY Services/ Services/
COPY Repositories/ Repositories/
COPY Scripts/ Scripts/
COPY Public/ Public/
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/vite-gourmand.ini
RUN mkdir -p Public/assets/uploads \
    && chown -R www-data:www-data Public/assets/uploads \
    && apache2ctl configtest
