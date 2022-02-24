FROM php:7.4-fpm-alpine

# Apk install
RUN apk --no-cache update && \
apk --no-cache add bash git && \
apk add --update --no-cache yarn curl zlib-dev libzip-dev zip libpng-dev icu-dev

# Install pdo
RUN docker-php-ext-install pdo_mysql gd intl zip

# Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

COPY . /var/www/html/

RUN php composer.phar install --ignore-platform-req=ext-zip

RUN yarn install --dev

RUN symfony server:start -d

ENTRYPOINT yarn watch

