# PHP 8.4 для проверки сайта на этом компьютере (на хостинг этот файл не загружается).
# Официальный образ PHP + расширения для обработки картинок (GD с WebP), EXIF и ZIP — как на хостинге.
FROM php:8.4-cli-alpine
RUN apk add --no-cache libpng libjpeg-turbo libwebp libzip \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpng-dev libjpeg-turbo-dev libwebp-dev libzip-dev \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd exif zip \
 && apk del .build-deps
