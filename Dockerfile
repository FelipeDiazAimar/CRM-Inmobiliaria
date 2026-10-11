# CRM Inmobiliaria — PHP 8.4 + Apache (deploy en Render con runtime Docker).
FROM php:8.4-apache

# Extensiones para MySQL (PDO + mysqli). Sin MySQL la app igual funciona
# en modo demo (DEMO_MODE=true): los datos viven en sesión, no en DB.
RUN docker-php-ext-install mysqli pdo pdo_mysql \
  && docker-php-ext-enable mysqli pdo_mysql \
  && a2enmod rewrite headers

# Docroot = raíz del repo (index.php redirige a /pages/index.php).
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
  && chmod -R 755 /var/www/html

# Render inyecta $PORT: Apache debe escuchar ahí, no en el 80 fijo.
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen $PORT/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:$PORT>/\" /etc/apache2/sites-enabled/000-default.conf && apache2-foreground"]
