#! /bin/sh -e

CURDIR=$(dirname $(readlink -f $0))/..
NAME=ulteo-ovd-native-client-standalone
VERSION=99.99
if [ -z "$OVD_VERSION" -a -d .svn ]; then
    export OVD_VERSION=$VERSION.svn$(svnversion -c $SVN_DIR | cut -d':' -f2)
else
    export OVD_VERSION=$VERSION
fi
DESTDIR=$CURDIR/dist/$NAME-$OVD_VERSION

cd $CURDIR
rm -rf $DESTDIR
mkdir -p $DESTDIR

install -m 744 $CURDIR/scripts/install_NC_standalone.sh $DESTDIR/install
echo $OVD_VERSION > $DESTDIR/VERSION

python autogen
ant ovdNativeClient.install -Ddestdir=$DESTDIR -Dprefix=/usr -Dlanguages=true

LAUNCHERDIR=$CURDIR/../OVDIntegratedLauncher/
python $LAUNCHERDIR/autogen
make -C $LAUNCHERDIR install DESTDIR=$DESTDIR

LIBDIR=$DESTDIR/usr/lib
mkdir -p $LIBDIR
cp -r $CURDIR/required_libraries/libXClientArea $LIBDIR

tar czf $DESTDIR.tar.gz -C $CURDIR/dist $(basename $DESTDIR)
rm -rf $DESTDIR
