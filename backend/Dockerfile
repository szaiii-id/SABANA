FROM dunglas/frankenphp:1.4-php8.4

RUN install-php-extensions pgsql pdo_pgsql redis gd pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache