FROM php:8.3-cli

COPY image-files/ /

RUN apt-get clean
RUN apt-get update && apt-get install -y \
     software-properties-common

RUN apt-get install -y unzip
#  libmpdec-dev

RUN pecl channel-update pecl.php.net

RUN pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug

RUN apt install -y libxml2-dev


RUN apt-get autoclean

