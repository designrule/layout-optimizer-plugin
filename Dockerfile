FROM wordpress

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
apt-get update && \
apt-get install -y nodejs \
 less \
 wget \
 subversion \
 mysql-client && \
 rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN wget https://phar.phpunit.de/phpunit-7.5.13.phar && \
    chmod +x phpunit-7.5.13.phar && \
    mv phpunit-7.5.13.phar /usr/local/bin/phpunit

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp