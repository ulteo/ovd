#!/bin/bash

cd /develop/SessionManager
OVD_VERSION=`cat VERSION | head -n 1` ./autogen
./configure --sysconfdir=/etc --localstatedir=/var
make
make install


mysql -u root -psdflkj -e 'create database ovd'
