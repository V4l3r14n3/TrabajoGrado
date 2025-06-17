FROM php:8.2-apache

# Instalar dependencias necesarias para MongoDB y GD
RUN apt-get update && apt-get install -y \
    libssl-dev pkg-config unzip git curl \
    libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos del proyecto
COPY . /var/www/html/

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html

# Puerto expuesto
EXPOSE 80

# Instalar dependencias PHP (Composer)
WORKDIR /var/www/html
RUN composer install --no-dev --ignore-platform-reqs
