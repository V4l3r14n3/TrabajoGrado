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

# Usar el puerto definido por Railway
ENV PORT=8080
RUN sed -i "s/80/\${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}
