FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

FROM php:8.2-apache AS runtime

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite headers

COPY . /var/www/html/
COPY --from=dependencies /app/vendor /var/www/html/vendor

RUN sed -ri \
    's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/gudang-kita.conf

RUN a2enconf gudang-kita
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/server-name.conf && a2enconf server-name

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && sed -ri 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

WORKDIR /var/www/html

USER www-data
EXPOSE 8080
