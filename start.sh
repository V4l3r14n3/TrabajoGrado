#!/bin/bash

echo "Valor recibido de \$PORT: $PORT"

# Verifica si la variable PORT está definida
if [ -z "$PORT" ]; then
  echo "ERROR: La variable de entorno PORT no está definida."
  exit 1
fi

# Crea configuración personalizada de Apache con el puerto de Railway
echo "Listen $PORT" > /etc/apache2/ports.conf

cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:$PORT>
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Inicia Apache
exec apache2-foreground
