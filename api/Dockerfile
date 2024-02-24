FROM php:8.1-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
# GD for image processing 
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd
RUN apt-get update \
    && apt-get install -y libzip-dev \
    && docker-php-ext-install zip
RUN pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3
RUN docker-php-ext-enable xmlrpc
RUN pecl install mcrypt-1.0.6
RUN docker-php-ext-enable mcrypt
COPY vhost.conf /etc/apache2/sites-enabled/000-default.conf
RUN /usr/sbin/a2enmod rewrite
