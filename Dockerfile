FROM ubuntu:xenial

RUN apt update && apt upgrade -y && \
    apt install -y git php php-dom php-mbstring php-zip php-dev zip unzip

COPY driver /tmp/driver

RUN ( cd /tmp/driver; \
        tar -xaf cassandra-cpp-driver.tar.bz2 && \
        ( cd cpp-driver; dpkg -i *.deb ) && \
        tar -xaf cassandra-php-drivers.tar.bz2 && \
        ( cd php-drivers; dpkg -i php7.0-cassandra-driver_1.3.2~stable-1_amd64.deb ) \
    ) && rm -rf /tmp/driver && \
    apt purge -y php-dev && apt autoremove -y && apt autoclean -y

RUN ( cd /usr/bin; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php --filename=composer && \
    php -r "unlink('composer-setup.php');" )

CMD ["/bin/bash"]
