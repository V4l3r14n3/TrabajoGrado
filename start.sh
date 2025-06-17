#!/bin/bash

# Verifica si PORT está definido
if [ -z "$PORT" ]; then
  echo "ERROR: La variable PORT no está definida."
  exit 1
fi

# Reescribe puertos en Apache
echo "Escuchando en el puerto: $PORT"
echo "Listen ${PORT}" > /etc/apache2/ports.conf

cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
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
apache2-foreground
