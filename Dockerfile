FROM sstok/park-manager-docker-and-compose:xdebug

RUN apk add --update vim

COPY . /var/www/
WORKDIR /var/www
