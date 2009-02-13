#!/bin/sh
# Copyright (C) 2006-2008 Ulteo SAS
# http://www.ulteo.com
# Author Gaël DUVAL <gduval@ulteo.com>
# Author Gauvain POCENTEK <gauvain@ulteo.com>
# Author Julien LANGLOIS <julien@ulteo.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation, version 2
# of the License.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

. functions.sh
. log.sh

log_INFO "UUMOUNT"
#NICK=$1
USER_HOME=/home/$NICK

log_DEBUG "Uumount nick:"$NICK

. modules_fs.sh || exit 1

set_fs

if ! get_status; then
    log_INFO "UUMOUNT:Info: $USER_HOME is not mounted"    
else
    do_umount
    if [ $? -ne 0 ]; then
	log_WARN "UUMOUNT: umount of ${USER_HOME} failed"
    fi
fi


userdel $USER_LOGIN
groupdel $USER_LOGIN

# Clean the menu
menu_clean $SPOOL_USERS/$SESSID'/xdg'

# HERE remove CUPS stuff
rm -rf /var/spool/cups2all/$USER_LOGIN

# wipeout userdir if not correctly removed
touch /tmp/wipeout/$NICK
