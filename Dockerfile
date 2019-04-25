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

CMD ["/bin/bash"]
