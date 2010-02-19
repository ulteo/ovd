#!/bin/sh -e

# build configure.in using svn revno
if [ -d .svn ]; then
    revision=$(LC_ALL=C svn info $0 | awk '/^Revision: / {printf "%05d\n", $2}')
else
    revision=0
fi
sed -e "s/@REVISION@/${revision}/g" < setup.py.in > setup.py
