# Usa una imagen base con PHP y Apache
FROM php:8.2-apache

# Instala dependencias necesarias
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    unzip \
    git \
    libcurl4-openssl-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-install mysqli \
    && apt-get clean

# Instala la extensión de MongoDB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Habilita mod_rewrite para Apache (útil si usas .htaccess)
RUN a2enmod rewrite

# Copia el código del proyecto al contenedor
COPY . /var/www/html/

# Da permisos correctos
RUN chown -R www-data:www-data /var/www/html

# Expone el puerto por defecto de Apache
EXPOSE 80
