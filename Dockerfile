# Use official PHP Apache image
FROM php:8.2-apache

# Install dependencies for PostgreSQL PDO
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

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