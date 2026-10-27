# syntax=docker/dockerfile:1
FROM php:8.5.12-apache-bookworm@sha256:5e4418695f9b13b75edefaff547125bf6be267e865931731bdd5946461505736 AS extensions
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libzip-dev \
    && docker-php-ext-install -j2 pdo_mysql mbstring zip pcntl

FROM node:24.22.1-bookworm-slim@sha256:55fb5f5a527ea2c5c4a95f963e48ef90aa38c7b346054fdc8ee9957a2b55d205 AS node-base
FROM node-base AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM node-base AS blockchain-runtime
WORKDIR /build/blockchain
COPY blockchain/package.json blockchain/package-lock.json ./
RUN npm ci --omit=dev --ignore-scripts --no-audit --no-fund \
    && test ! -d node_modules/hardhat && test ! -d node_modules/zod

FROM composer:2.10.3@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc AS composer-binary
FROM extensions AS dependencies
COPY --from=composer-binary /usr/bin/composer /usr/local/bin/composer
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts --no-progress \
    && composer check-platform-reqs --no-dev

FROM php:8.5.12-apache-bookworm@sha256:5e4418695f9b13b75edefaff547125bf6be267e865931731bdd5946461505736 AS runtime
ARG GIT_SHA=uncommitted
LABEL org.opencontainers.image.title="ESPACIO-U" \
      org.opencontainers.image.source="https://github.com/Dan10104/Proyecto-de-Taller-de-Grado" \
      org.opencontainers.image.revision=$GIT_SHA
RUN apt-get update && apt-get install -y --no-install-recommends libonig5 libzip4 libstdc++6 curl \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers && a2dismod -f autoindex status
COPY --from=extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=node-base /usr/local/bin/node /usr/local/bin/node
WORKDIR /var/www/html
COPY --from=dependencies /build/vendor ./vendor
COPY --from=blockchain-runtime /build/blockchain/node_modules ./blockchain/node_modules
COPY artisan composer.json composer.lock ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database/migrations ./database/migrations
COPY database/seeders ./database/seeders
COPY lang ./lang
COPY routes ./routes
COPY resources/views ./resources/views
# Inertia 3 validates page existence on the server; these sources remain outside public/.
COPY resources/js/Pages ./resources/js/Pages
COPY resources/chatbot ./resources/chatbot
COPY public ./public
COPY blockchain/package.json blockchain/package-lock.json ./blockchain/
COPY blockchain/runtime ./blockchain/runtime
COPY blockchain/abi ./blockchain/abi
COPY --from=frontend /build/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/mpm.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY docker/entrypoint.sh docker/web.sh /usr/local/bin/
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf \
    && printf 'ServerName localhost\nServerTokens Prod\nServerSignature Off\nGracefulShutdownTimeout 8\n' > /etc/apache2/conf-available/espacio-u.conf \
    && a2enconf espacio-u \
    && mkdir -p storage/app/private/reports storage/framework/cache/data storage/framework/cache/locks storage/framework/sessions storage/framework/views storage/logs bootstrap/cache /var/run/apache2 /var/lock/apache2 \
    && chown -R www-data:www-data storage bootstrap/cache /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache \
    && chmod 755 /usr/local/bin/entrypoint.sh /usr/local/bin/web.sh \
    && test ! -e .env && test ! -d blockchain/node_modules/hardhat && test ! -d blockchain/node_modules/zod
ENV PORT=8080 APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning LOG_EMERGENCY_PATH=php://stderr \
    SESSION_DRIVER=database CACHE_STORE=database SESSION_BLOCK_STORE=database IOT_RATE_STORE=database \
    QUEUE_CONNECTION=database QUEUE_EFFECTS_CONNECTION=database QUEUE_FAILED_DRIVER=null \
    DB_QUEUE_RETRY_AFTER=120 BLOCKCHAIN_NODE_BINARY=/usr/local/bin/node \
    CHATBOT_ENABLED=false BLOCKCHAIN_ENABLED=false
USER www-data
EXPOSE 8080
STOPSIGNAL SIGTERM
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD curl --fail --silent "http://127.0.0.1:${PORT}/up" > /dev/null || exit 1
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["web"]
