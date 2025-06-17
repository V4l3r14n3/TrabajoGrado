FROM php:8.2-apache

# Instala extensiones necesarias (como MongoDB)
RUN apt-get update && apt-get install -y libssl-dev pkg-config && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb

# Copia el código fuente al contenedor
COPY . /var/www/html/

# Configura Apache
EXPOSE 80
