# Thanks to: https://www.digitalocean.com/community/tutorials/how-to-containerize-a-laravel-application-for-development-with-docker-compose-on-ubuntu-18-04
FROM php:7.4-fpm

ARG user
ARG uid

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update
RUN apt-get install -y \
    libbz2-dev libxml2-dev \
    libpng-dev libonig-dev libzip-dev \
    bzip2 zip unzip

RUN docker-php-ext-install \
    bz2 bcmath mbstring \
    json opcache pdo \
    pdo_mysql sockets xml zip

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

WORKDIR /var/www/html
USER $user