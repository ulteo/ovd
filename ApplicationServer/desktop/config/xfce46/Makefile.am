# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

XFCE4DIR = $(sysconfdir)/ulteo/xdg/xfce4

xfce4paneldir = $(XFCE4DIR)/panel
xfce4panel_DATA = panels.xml tasklist-2.rc clock-3.rc launcher-4.rc

xfce4conf_DATA = xfce4-desktop.xml xfce4-panel.xml xfce4-session.xml xfwm4.xml xsettings.xml
xfce4confdir = $(XFCE4DIR)/xfconf/xfce-perchannel-xml

EXTRA_DIST = $(xfce4conf_DATA) $(xfce4panel_DATA)
