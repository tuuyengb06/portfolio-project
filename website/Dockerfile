FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' \
       /etc/apache2/sites-available/000-default.conf \
    && mkdir -p /var/log/portfolio \
    && chown -R www-data:www-data \
       /var/run/apache2 \
       /var/lock/apache2 \
       /var/log/apache2 \
       /var/log/portfolio

RUN printf '%s\n' \
    'expose_php=Off' \
    'display_errors=Off' \
    'log_errors=On' \
    'error_log=/var/log/portfolio/app.log' \
    'session.cookie_httponly=1' \
    'session.cookie_samesite=Lax' \
    > /usr/local/etc/php/conf.d/portfolio.ini

COPY --chown=root:root . /var/www/html/

RUN find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

USER www-data

EXPOSE 8080

CMD ["apache2-foreground"]