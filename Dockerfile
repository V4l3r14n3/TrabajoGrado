FROM php:8.2-apache

# Instalar dependencias para MongoDB
RUN apt-get update && apt-get install -y \
    libssl-dev pkg-config unzip git curl && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar archivos del proyecto
COPY . /var/www/html/

# Establecer permisos y limpiar
RUN chown -R www-data:www-data /var/www/html

# Puerto expuesto
EXPOSE 80

# Instalar dependencias PHP (Composer)
WORKDIR /var/www/html
RUN composer install
