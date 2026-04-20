# Minimal Laravel dev runtime vs upstream Sail (docker/8.4 layout kept for parity with start-container / supervisor).
# Omits: Playwright system deps, Mongo/PG stacks, extra JS runtimes (pnpm/bun/yarn installers), FFmpeg, etc.

FROM php:8.4-cli-bookworm

ARG WWWGROUP=1000
ARG XDEBUG=false
ARG NODE_VERSION=22.14.0

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC \
    LANG=C.UTF-8 \
    SUPERVISOR_PHP_COMMAND="/usr/local/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80" \
    SUPERVISOR_PHP_USER="sail"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gosu \
        libpng16-16 \
        libjpeg62-turbo \
        libwebp7 \
        libfreetype6 \
        libicu72 \
        libzip4 \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libicu-dev \
        libzip-dev \
        libsqlite3-dev \
        libcap2-bin \
        supervisor \
        unzip \
        default-mysql-client \
        xz-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        bcmath \
        pcntl \
        pdo_mysql \
        pdo_sqlite \
        zip \
    && pecl install redis pcov \
    && docker-php-ext-enable redis pcov \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    ARCH="$(uname -m)"; \
    case "$ARCH" in \
        aarch64|arm64) NODE_ARCH=arm64 ;; \
        x86_64|amd64) NODE_ARCH=x64 ;; \
        *) echo "unsupported arch: $ARCH" >&2; exit 1 ;; \
    esac; \
    curl -fsSL "https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-${NODE_ARCH}.tar.xz" \
        | tar -xJ -C /usr/local --strip-components=1 --no-same-owner; \
    node --version; \
    npm --version

RUN if [ "$XDEBUG" = "true" ]; then \
        pecl install xdebug \
        && docker-php-ext-enable xdebug; \
    fi

RUN setcap cap_net_bind_service=+ep /usr/local/bin/php \
    && groupadd --force -g "${WWWGROUP}" sail \
    && useradd -ms /bin/bash --no-user-group -g "${WWWGROUP}" -u 1337 sail \
    && git config --global --add safe.directory /var/www/html

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /usr/local/etc/php/conf.d/99-app.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
