# docker/api.Dockerfile
FROM php:8.3-fpm

# system deps for composer
RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libzip-dev \
  && docker-php-ext-install pdo pdo_mysql zip \
  && rm -rf /var/lib/apt/lists/*

# optional: install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
