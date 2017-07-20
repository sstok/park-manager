FROM php:7.1.7-fpm-alpine

RUN set -xe \
    && apk add --update openssl ca-certificates \
    && apk add --no-cache --virtual .php-deps \
        libsodium \
        libpq \
        icu-libs \
        postgresql-client \
        make \
        git \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libsodium-dev \
        icu-dev  \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
    && pecl install apcu \
    && pecl install redis-3.1.2 \
    && pecl install libsodium-1.0.6 \

#    && pecl install xdebug \
#    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > "$PHP_INI_DIR/conf.d/xdebug.ini" \
#    && echo "xdebug.remote_enable=on" >> "$PHP_INI_DIR/conf.d/xdebug.ini" \
#    && echo "xdebug.remote_autostart=off" >> "$PHP_INI_DIR/conf.d/xdebug.ini" \

    && docker-php-ext-enable apcu redis libsodium \
    && echo "memory_limit=-1" > "$PHP_INI_DIR/conf.d/memory-limit.ini" \
    && echo "date.timezone=${PHP_TIMEZONE:-UTC}" > "$PHP_INI_DIR/conf.d/date_timezone.ini" \
    && { find /usr/local/lib -type f -print0 | xargs -0r strip --strip-all -p 2>/dev/null || true; } \
    && apk del .build-deps \
    && rm -rf /tmp/* /usr/local/lib/php/doc/* /var/cache/apk/*

ENV PATH "/composer/vendor/bin:$PATH"
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /composer
ENV COMPOSER_VERSION 1.4.2

# https://github.com/composer/docker/blob/master/1.4/Dockerfile
RUN curl -s -f -L -o /tmp/installer.php https://raw.githubusercontent.com/composer/getcomposer.org/da290238de6d63faace0343efbdd5aa9354332c5/web/installer \
    && php -r " \
       \$signature = '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410'; \
       \$hash = hash('SHA384', file_get_contents('/tmp/installer.php')); \
       if (!hash_equals(\$signature, \$hash)) { \
           unlink('/tmp/installer.php'); \
           echo 'Integrity check failed, installer is either corrupt or worse.' . PHP_EOL; \
           exit(1); \
       }" \
    && php /tmp/installer.php --no-ansi --install-dir=/usr/bin --filename=composer --version=${COMPOSER_VERSION} \
    && rm /tmp/installer.php \
    && composer --ansi --version --no-interaction

#COPY performance.ini /usr/local/etc/php/conf.d/

WORKDIR /var/www
RUN rm -rf /var/www/*
