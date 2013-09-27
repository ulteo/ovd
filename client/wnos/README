= Wyse ThinOS client broker =

== Installation ==

 Prerequisites: 
   * have a running Ulteo farm installed
   * have a running PHP server to push those files

 1. replace the value of OPTION_SM_HOST in the file config.inc.php according to your own architecture
 2. edit wnos.ini to set the right url for VDIBroker to the wnos.php script
 3. install the apache.conf
 4. put wnos.ini in an ftp server with anonymous access to /wnos/wnos.ini
 5. put some desktop.jpg and header.jpg bitmaps to /wnos/bitmap on the ftp server
 6. configure the DHCP server to publish the ip address of the ftp server in dhcpd.conf : option option-161 xx.xx.xx.xx
 7. put wnos.php and functions.inc.php files to /usr/share/ulteo/wnos/
 8. put config.inc.php to /etc/ulteo/wnos/config.inc.php
 9. install ulteo-ovd-l10n to benefit localizations
