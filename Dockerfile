FROM php:8.2-apache

# Install the MySQL PDO driver used by api.php, loans_api.php, payments_api.php
RUN docker-php-ext-install pdo pdo_mysql
