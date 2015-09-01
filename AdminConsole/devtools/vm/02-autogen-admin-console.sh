#!/bin/bash

cd /develop/AdminConsole
OVD_VERSION=`cat VERSION | head -n 1` ./autogen
./configure --sysconfdir=/etc --localstatedir=/var
make
make install
