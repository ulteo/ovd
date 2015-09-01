#!/usr/bin/env bash

debconf-set-selections <<< 'mysql-server mysql-server/root_password password sdflkj'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password sdflkj'

apt-get install -y apache2 build-essential git autoconf automake php5 php5-mysql mysql-server php5-curl
