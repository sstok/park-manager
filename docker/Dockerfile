FROM composer:1.5
FROM php:7.1-alpine

RUN apk add --no-cache --virtual .persistent-deps \
		git \
		icu-libs \
		libpq \
		postgresql-client \
		libsodium \
		zlib \
		make

ENV APCU_VERSION 5.1.8
RUN set -xe \
	&& apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
		libsodium-dev \
		icu-dev \
		postgresql-dev \
		zlib-dev \
	&& docker-php-ext-install \
        	pdo_pgsql \
	        pgsql \
        	bcmath \
	        intl \
	        zip \
	&& pecl install \
		apcu-${APCU_VERSION} \
		libsodium-1.0.6 \
	&& docker-php-ext-enable --ini-name 20-apcu.ini apcu \
    	&& docker-php-ext-enable libsodium \
	&& apk del .build-deps

COPY --from=0 /usr/bin/composer /usr/bin/composer
RUN ln -s /usr/bin/composer /usr/bin/composer.phar

WORKDIR /srv/www

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative

#RUN mkdir -p var/cache var/logs var/sessions \
#	&& composer dump-autoload --classmap-authoritative --no-dev \
#	&& chown -R www-data var
