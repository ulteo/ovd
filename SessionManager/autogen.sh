#!/bin/sh -e

glib-gettextize --force --copy
intltoolize --copy --automake --force
aclocal
autoconf
automake --add-missing --gnu --copy

