FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip zip curl libzip-dev libonig-dev libicu-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www
ENTRYPOINT ["/entrypoint.sh"]