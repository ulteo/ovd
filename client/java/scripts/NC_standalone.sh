#! /bin/sh
set -e

CURDIR=$(dirname $(readlink -f $0))/..
NAME=ulteo-ovd-native-client-standalone
VERSION=99.99
TEMPDIR=$CURDIR/temp

if [ -z "$OVD_VERSION" -a -d .svn ]; then
    export OVD_VERSION=$VERSION.svn$(svnversion -c $SVN_DIR | cut -d':' -f2)
else
    export OVD_VERSION=$VERSION
fi
DESTDIR=$CURDIR/dist/$NAME-$OVD_VERSION

cd $CURDIR
rm -rf $DESTDIR
mkdir -p $DESTDIR

rm -rf $TEMPDIR
mkdir -p $TEMPDIR

install -m 744 $CURDIR/scripts/install_NC_standalone.sh $DESTDIR/install
echo $OVD_VERSION > $DESTDIR/VERSION

python autogen
ant ovdNativeClient.install -Ddestdir=$TEMPDIR -Dprefix=/usr -Dlanguages=true

install -m 744 $CURDIR/scripts/OVDNativeClient_standalone $DESTDIR/OVDNativeClient
cp $TEMPDIR/usr/share/java/OVDNativeClient.jar $DESTDIR

LAUNCHERDIR=$CURDIR/../OVDIntegratedLauncher/
python $LAUNCHERDIR/autogen
make -C $LAUNCHERDIR install DESTDIR=$TEMPDIR
cp $TEMPDIR/usr/bin/UlteoOVDIntegratedLauncher $DESTDIR

LIBDIR=$DESTDIR/lib
mkdir -p $LIBDIR
cp -r $CURDIR/required_libraries/libXClientArea/* $LIBDIR

tar czf $DESTDIR.tar.gz -C $CURDIR/dist $(basename $DESTDIR)
rm -rf $DESTDIR
rm -rf $TEMPDIR
