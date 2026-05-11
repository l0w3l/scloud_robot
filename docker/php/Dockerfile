FROM php:8.3-fpm

WORKDIR /var/www/html

ARG APP_XDEBUG=false
# Добавлено из старого конфига для корректной работы прав (если нужно)
ARG HOST_UID=1000
ARG HOST_GID=1000

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    pigz \
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
    unzip \
    cron \
    # УНИКАЛЬНЫЕ пакеты из старого конфига
    python3 \
    python3-pip \
    python3-venv \
    ffmpeg \
    atomicparsley
RUN curl -fsSL https://deno.land/install.sh | sh
RUN mv /root/.deno/bin/deno /usr/local/bin/deno
RUN python3 -m venv /opt/venv
RUN /opt/venv/bin/pip install --no-cache-dir \
      "yt-dlp[default,curl-cffi]" \
      yt-dlp-ejs
RUN docker-php-ext-configure gd --with-jpeg --with-webp
RUN docker-php-ext-install \
    gmp \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    bz2 \
 && pecl install imagick redis \
 && docker-php-ext-enable imagick redis \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Добавляем путь к venv в PATH
ENV PATH="/opt/venv/bin:$PATH"

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy scripts and config
COPY ./scripts /usr/local/bin/scripts
COPY ./config/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set permissions for scripts
RUN chmod +x /usr/local/bin/scripts/*.sh

# Run install script (for NVM, Node, Xdebug)
RUN bash /usr/local/bin/scripts/install.sh -d "${APP_XDEBUG}"

# Настройка пользователя (Внимание: в новом конфиге используется www-data, а в старом был app)
# Если ваши скрипты внутри scripts/*.sh полагаются на www-data, оставляйте так.
# Если нужны кастомные UID/GID, раскомментируйте блок ниже:
# RUN groupmod -g ${HOST_GID} www-data && usermod -u ${HOST_UID} -g www-data www-data

USER www-data

CMD ["php-fpm"]
