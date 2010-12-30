# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

hadir = $(datadir)/ulteo/sessionmanager/admin/ha
ha_DATA = configuration.php \
          logs.php \
          menu.inc.php \
          status.php \
          registration.php

imagedir = $(hadir)/media/image
image_DATA = media/image/ha_down.png \
          media/image/resource.gif \
          media/image/menu_main.png \
          media/image/ha_online.png \
          media/image/loader.gif \
          media/image/ha_standby.png \
          media/image/cross_error_cib.png

styledir = $(hadir)/media/style
style_DATA = media/style/media-ha.css

classesdir = $(hadir)/classes
classes_DATA = classes/GRNode.class.php \
          classes/Abstract_node.class.php \
          classes/PRNode.class.php \
          classes/CLNode.class.php \
          classes/CibNode.class.php \
          classes/Cib.class.php \
          classes/MSNode.class.php \
          classes/ShellExec.class.php

EXTRA_DIST = $(ha_DATA) $(image_DATA) $(style_DATA) $(classes_DATA)
