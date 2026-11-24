FROM php:8.4-cli

COPY image-files/ /

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug-3.4.7 \
    && docker-php-ext-enable xdebug
