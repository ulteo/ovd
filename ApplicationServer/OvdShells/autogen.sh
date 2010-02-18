#!/bin/bash

# build configure.in using svn revno
if [ -d .svn ]; then
    revision=$(LC_ALL=C svn info . | awk '/^Revision: / {printf "%05d\n", $2}')
    sed -e "s/@REVISION@/${revision}/g" < setup.py.in > setup.py
fi
