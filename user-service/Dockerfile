FROM php:7.4-apache

COPY . /var/www/html

RUN apt-get update && apt-get install -y libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-install mysqli
