FROM dunglas/frankenphp:1.4-php8.4

RUN install-php-extensions pgsql pdo_pgsql redis gd pcntl zip opcache

RUN apt-get update && apt-get install -y unzip git && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

ARG CLOUDINARY_URL=cloudinary://skip:skip@skip

RUN composer install --no-dev --optimize-autoloader --classmap-authoritative

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 80