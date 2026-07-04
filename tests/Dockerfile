FROM php:7.4.33-cli-alpine

RUN apk add --no-cache $PHPIZE_DEPS linux-headers

RUN pecl install xdebug-3.1.6
RUN docker-php-ext-enable xdebug

ENV XDEBUG_MODE=coverage
