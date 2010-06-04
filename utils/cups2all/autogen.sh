#!/bin/sh

# build configure.in using svn revno
if [ -d .svn ]; then
  revision=$(LC_ALL=C svn info . | awk '/^Revision: / {printf "%05d\n", $2}')
else
  revision=0
fi
sed -e "s/@REVISION@/${revision}/g" < configure.ac.in > configure.in

autoreconf -vfi && ./configure
