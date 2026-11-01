# Back-office: PHP 8.2 con Apache y PDO MySQL.
# Se construye desde la carpeta backoffice/:  docker build -t blacklotus-bo backoffice
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

# public/ es la raíz web; la API se sirve en /bo-api. config.php queda fuera de la raíz.
ENV APACHE_DOCUMENT_ROOT=/var/www/backoffice/public
RUN sed -ri 's#/var/www/html#${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/sites-available/000-default.conf \
 && printf '%s\n' \
      'Alias /bo-api /var/www/backoffice/api' \
      '<Directory /var/www/backoffice/api>' \
      '    AllowOverride All' \
      '    Require all granted' \
      '</Directory>' \
      > /etc/apache2/conf-available/bo-api.conf \
 && a2enconf bo-api

COPY api/ /var/www/backoffice/api/
COPY public/ /var/www/backoffice/public/
# config.php solo lee variables de entorno, así que vale la plantilla
COPY config.example.php /var/www/backoffice/config.php

# Algunas plataformas (Render, Railway) indican el puerto en PORT
CMD sed -i "s/Listen 80$/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
 && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
