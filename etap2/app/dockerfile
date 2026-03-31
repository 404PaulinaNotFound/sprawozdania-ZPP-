FROM php:8.2-apache

# Instalacja narzędzi systemowych
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalacja rozszerzeń PHP
RUN docker-php-ext-install pdo_mysql mysqli zip
RUN a2enmod rewrite

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ustaw workdir
WORKDIR /var/www/html

# Skopiuj kod źródłowy
COPY ./src/ /var/www/html/

# Instaluj zależności Composer
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Uprawnienia
RUN chown -R www-data:www-data /var/www/html
