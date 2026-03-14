FROM php:8.3-cli

ARG HOST_UID
ARG HOST_GID

RUN apt-get update && apt-get install -y \
    pigz \
    unzip \
    libbz2-dev \
    zlib1g-dev \
    libpng-dev \
    libgmp-dev \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libmagickwand-dev \
    curl \
    git \
    python3 \
    python3-pip \
    python3-venv \
    ffmpeg \
    atomicparsley \
 && curl -fsSL https://deno.land/install.sh | sh \
 && mv /root/.deno/bin/deno /usr/local/bin/deno \
 && python3 -m venv /opt/venv \
 && /opt/venv/bin/pip install --no-cache-dir \
      "yt-dlp[default,curl-cffi]" \
      yt-dlp-ejs \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install gmp pdo pdo_pgsql mbstring exif pcntl bcmath gd zip bz2 \
 && pecl install redis imagick \
 && docker-php-ext-enable redis imagick \
 && rm -rf /var/lib/apt/lists/*

ENV PATH="/opt/venv/bin:$PATH"

RUN echo "opcache.enable_cli=0" > /usr/local/etc/php/conf.d/opcache-cli.ini

RUN mkdir -p /usr/local/bin/scripts
COPY ./scripts /usr/local/bin/scripts

RUN groupadd -g ${HOST_GID} app \
  && useradd -u ${HOST_UID} -g app -m app

USER app
