FROM php:8.2-cli

# Install dependencies
RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        unzip \
        git \
        wget \
    && docker-php-ext-install -j$(nproc) bcmath sockets \
    && docker-php-ext-install pdo pdo_mysql


# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/symfony

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application files
COPY . .

# Generate autoload files
RUN composer dump-autoload --no-scripts --no-dev --optimize

# Change ownership of the application files to the www-data user
RUN chown -R www-data:www-data /var/www/symfony

# Expose port 8000
EXPOSE 8000

CMD php -S 0.0.0.0:8000 -t ./public