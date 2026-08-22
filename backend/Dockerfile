FROM dunglas/frankenphp:php8.3-alpine

# install ekstensi PostgreSQL, Redis, dll
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    pcntl \
    intl \
    bcmath \
    opcache

# install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# expose port buat FrankenPHP Octane
EXPOSE 8000 443

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--watch"]
