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

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar solo composer.json y composer.lock primero (mejora uso de caché)
COPY composer.json composer.lock ./

# Instalar dependencias PHP
RUN composer install --no-dev --ignore-platform-reqs

# Copiar el resto del proyecto
COPY . .

# Hacer pública la carpeta uploads para Apache
RUN chmod -R 755 /var/www/html/uploads

# Establecer permisos
RUN chown -R www-data:www-data /var/www/html

# Puerto expuesto
EXPOSE 80
