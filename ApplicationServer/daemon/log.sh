# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
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

log_ERROR() {
    return
}

log_WARN() {
    return
}

log_DEBUG() {
    return
}

log_INFO() {
    return
}

log_push_basic() {
    echo $(date +"%F-%T") $@ >> $LOG_FILE
}

LOG_D=$(dirname $0)/log.d
for j in $LOG_FLAGS; do
    [ -e $LOG_D/$j.sh ] && . $LOG_D/$j.sh
done

