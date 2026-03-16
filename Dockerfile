# Use official PHP Apache image
FROM php:8.2-apache

# Install PostgreSQL PDO extension
RUN docker-php-ext-install pdo pdo_pgsql

# Copy your API files into the container's web root
COPY ./api/ /var/www/html/

# Enable mod_rewrite for .htaccess routing
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Expose default HTTP port (Render uses 10000 internally)
EXPOSE 10000

# Start Apache in foreground
CMD ["apache2-foreground"]