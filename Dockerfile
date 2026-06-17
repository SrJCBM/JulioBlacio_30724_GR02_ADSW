FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql

COPY ["3.Codigo/IRONBOX V1.0.0/", "/var/www/html/"]

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
