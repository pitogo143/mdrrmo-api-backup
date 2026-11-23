FROM php:8.3-apache

RUN apt-get update     && apt-get install -y --no-install-recommends libpq-dev libonig-dev     && docker-php-ext-install pdo_pgsql mbstring     && a2enmod rewrite headers     && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf     && sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf     && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000
CMD ["apache2-foreground"]
