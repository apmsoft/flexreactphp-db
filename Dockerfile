FROM php:8.3-alpine

# Install dependencies for Composer, ReactPHP, MySQL, PostgreSQL, GD
RUN apk add --no-cache \
    curl \
    libzip-dev \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    git \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /app

# Copy composer files first
COPY ./app/composer.json ./app/composer.lock* ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application files
COPY ./app .

# Generate autoloader and install dependencies
RUN composer clear-cache
RUN composer install --no-dev
RUN composer dump-autoload --optimize

# Create custom php.ini
RUN echo "display_errors = On" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = Asia/Seoul" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit=1255" >> /usr/local/etc/php/conf.d/custom.ini

# Create error log file and set permissions
RUN touch /var/log/php_errors.log && chmod 666 /var/log/php_errors.log

# Expose the port the application runs on
EXPOSE 80

# Command to run the ReactPHP server
CMD ["php", "/app/server.php"]