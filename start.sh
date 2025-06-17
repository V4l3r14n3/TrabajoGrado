#!/bin/bash

# Reemplaza el puerto en Apache con la variable $PORT de Railway
sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Inicia Apache en primer plano
apache2-foreground
