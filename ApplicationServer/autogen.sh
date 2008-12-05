#!/bin/sh -e

# build configure.in using svn revno
if [ -d .svn ]; then
    revision=$(LC_ALL=C svn info $0 | awk '/^Revision: / {printf "%05d\n", $2}')
    sed -e "s/@REVISION@/${revision}/g" \
        < "configure.in.in" > "configure.in"
fi

#glib-gettextize --force --copy
#intltoolize --copy --automake --force
aclocal
autoconf
automake --add-missing --gnu --copy

