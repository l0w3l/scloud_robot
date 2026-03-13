FROM php:8.3-cli

ARG HOST_UID
ARG HOST_GID

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libgmp-dev \
 && docker-php-ext-install zip pcntl gmp \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

RUN echo "opcache.enable_cli=0" > /usr/local/etc/php/conf.d/opcache-cli.ini

RUN mkdir -p /usr/local/bin/scripts
COPY ./scripts /usr/local/bin/scripts

RUN groupadd -g ${HOST_GID} app \
  && useradd -u ${HOST_UID} -g app -m app

USER app
