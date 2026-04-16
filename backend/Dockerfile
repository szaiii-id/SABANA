FROM dunglas/frankenphp:1.4-php8.4

# Instal driver PostgreSQL, Redis, dan GD (untuk gambar)
RUN install-php-extensions pgsql pdo_pgsql redis gd

# Set working directory
WORKDIR /app

# Copy kodingan kita
COPY . /app

# Beri izin akses ke folder storage dan cache (khas Laravel)
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache