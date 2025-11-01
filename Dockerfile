FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    supervisor \
    nodejs \
    npm

RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql zip bcmath

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

RUN pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www/html

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

RUN composer install --no-scripts --no-interaction --prefer-dist \
    && npm install

CMD ["php-fpm"]
