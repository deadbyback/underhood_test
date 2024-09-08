FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get install -y curl git zip unzip

WORKDIR /var/www
COPY . .

RUN composer install

RUN chown -R www-data:www-data /var/www

CMD ["/bin/bash"]
