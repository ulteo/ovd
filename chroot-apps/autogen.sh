#!/bin/sh -e

#
# Please install the Ulteo xfce4-dev-tools package to run this script
#
xdt-autogen $@

# hack for certain intltoolize version that doesn't create these files
touch intltool-extract.in intltool-merge.in intltool-update.in
