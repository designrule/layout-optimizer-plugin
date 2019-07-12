FROM wordpress

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
apt-get update && \
apt-get install -y nodejs \
 less \
 wget \
 subversion \
 mysql-client && \
 rm -rf /var/lib/apt/lists/*
 
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV LAYOUT_OPTIMIZER_API_URL http://docker.for.mac.host.internal:3000/api/v1/themes/

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp