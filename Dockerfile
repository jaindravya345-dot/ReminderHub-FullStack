FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers

WORKDIR /var/www/html
COPY . /var/www/html/

# Keep private local configuration and development artifacts out of the image.
RUN rm -f /var/www/html/config/local.php \
    && chown -R www-data:www-data /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD php -r '$c=@file_get_contents("http://127.0.0.1/api/health.php"); exit($c!==false && strpos($c,"\\"ok\\":true")!==false ? 0 : 1);'

CMD ["apache2-foreground"]
