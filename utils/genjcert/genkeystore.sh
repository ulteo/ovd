#!/bin/sh

PASSWD=$(pwgen -1)
DNAME="cn=Gauvain Pocentek, ou=Ulteo, o=Ulteo, c=FR"
ALIAS="ulteo"
KEYSTORE=keystore
VALIDITY=180

usage () {
    echo "Options :"
    echo " -p PASSWD"
    echo " -d DNAME"
    echo " -a ALIAS"
    echo " -k KEYSTORE"
    echo " -v VALISITY"
    echo " -h"
}

while getopts "p:d:a:k:v:h" options; do
    case "$options" in
        p)
            PASSWD=$OPTARG;;
        d)
            DNAME=$OPTARG;;
        a)
            ALIAS=$OPTARG;;
        k)
            KEYSTORE=$OPTARG;;
        v)
            VALIDITY=$OPTARG;;
        h)
            usage
            exit 0
            ;;
        *)
            usage
            exit 1
            ;;
    esac
done

# gen the keystore
keytool -genkeypair -dname "$DNAME" -alias "$ALIAS" \
    -keypass $PASSWD -storepass $PASSWD \
    -keystore $KEYSTORE -validity $VALIDITY

keytool -selfcert -alias "$ALIAS" -keystore $KEYSTORE \
    -keypass $PASSWD -storepass $PASSWD

echo $PASSWD
exit 0

