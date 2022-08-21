FROM --platform=linux/amd64 php:7.4-apache

RUN echo 'upload_max_filesize = 16M' >> /usr/local/etc/php/conf.d/docker-php-upload_max_filesize.ini
RUN echo 'post_max_size = 16M' >> /usr/local/etc/php/conf.d/docker-post_max_size.ini
RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini
RUN echo 'extension=pdo_sqlsrv.so' >> /usr/local/etc/php/conf.d/docker-php-extension.ini
RUN echo 'extension=sqlsrv.so' >> /usr/local/etc/php/conf.d/docker-php-extension.ini
RUN a2enmod rewrite \
    && apt-get update \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libpq-dev libcurl4-openssl-dev openssl wget libmp3lame-dev\
    && apt-get install -y ffmpeg yasm libldap2-dev libzip-dev zip x264 libx264-dev unixodbc-dev

# Env variables
ENV ACCEPT_EULA=y

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install apt-utils libxml2-dev gnupg apt-transport-https 

RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/9/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update

# Install sqlsrv
RUN apt-get update
RUN apt-get install -y wget
RUN wget http://ftp.br.debian.org/debian/pool/main/g/glibc/multiarch-support_2.24-11+deb9u4_amd64.deb && \
    dpkg -i multiarch-support_2.24-11+deb9u4_amd64.deb
RUN apt-get -y install msodbcsql17

RUN apt-get update -yqq \
&& apt-get install -y --no-install-recommends openssl \ 
&& echo '[default_conf]' >> /etc/ssl/openssl.cnf \
&& echo 'ssl_conf = ssl_sect' >> /etc/ssl/openssl.cnf \
&& echo '[ssl_sect]' >> /etc/ssl/openssl.cnf \
&& echo 'system_default = system_default_sect' >> /etc/ssl/openssl.cnf \
&& sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf \
&& sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf


RUN pecl install sqlsrv pdo_sqlsrv

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install gd pdo pdo_pgsql curl ldap zip exif
RUN apt-get install -y libc-client-dev libkrb5-dev \ 
    && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap


RUN wget https://www.ffmpeg.org/releases/ffmpeg-4.4.tar.gz
RUN tar -xzf ffmpeg-4.4.tar.gz; rm -r ffmpeg-4.4.tar.gz
RUN cd ./ffmpeg-4.4; ./configure --enable-gpl --enable-libmp3lame --enable-libx264 --enable-decoder=mjpeg,png --enable-encoder=png


RUN cd ./ffmpeg-4.4; make
RUN  cd ./ffmpeg-4.4; make install
