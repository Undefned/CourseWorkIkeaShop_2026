FROM php:8.2-apache

# Устанавливаем расширения для PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Включаем mod_rewrite
RUN a2enmod rewrite

# Настраиваем PHP для разработки
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors = 1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_startup_errors = 1" >> /usr/local/etc/php/conf.d/custom.ini

# Убираем предупреждение Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Включает .htaccess (по умолчанию в образе он отключён — AllowOverride None)
# и добавляет Alias /api -> Backend/Api, чтобы .htaccess из Backend/Api
# работал один в один и в Docker, и на Helios.
COPY apache-overrides.conf /etc/apache2/conf-enabled/tsogz-overrides.conf

WORKDIR /var/www/html
