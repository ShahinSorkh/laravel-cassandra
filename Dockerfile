FROM ubuntu:xenial

RUN apt update \
    && apt install -y -m --autoremove --no-install-recommends \
        vim git curl php php-xdebug php-dom php-curl php-mbstring php-zip zip unzip \
        openssl ca-certificates php-dev php-pear libgmp3-dev make \
    && git clone git://git.launchpad.net/~sorkh.shahin/cassandra-php-driver /tmp/driver \
    && cd /tmp/driver && dpkg -i *.deb && cd - && rm -rf /tmp/driver \
    && pecl channel-update pecl.php.net \
    && pecl install cassandra \
    && apt purge -y php-dev php-pear libgmp3-dev make && apt autoremove -y && apt autoclean -y \
    && echo 'extension=cassandra.so' >$(php --ini | awk '/\(php.ini\)/ {sub(/cli$/, "mods-available", $NF);print $NF}')/cassandra.ini \
    && phpenmod cassandra \
    && cd /usr/bin \
    && curl -L https://getcomposer.org/installer | php \
    && mv composer.phar composer && cd -

WORKDIR /app

CMD ["/bin/bash"]
