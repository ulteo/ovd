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

XFCE4DIR = $(sysconfdir)/xdg/xfce4

xfce4conf_DATA = Xcursor.xrdb  Xft.xrdb
xfce4confdir = $(XFCE4DIR)

xfce4kioskconf_DATA = kioskrc kiosk_restriction.conf
xfce4kioskconfdir = $(XFCE4DIR)/kiosk

xfce4mcsconf_DATA = desktop.xml gtk.xml workspaces.xml xfwm4_keys.xml xfwm4.xml
xfce4mcsconfdir = $(XFCE4DIR)/mcs_settings

xfce4panelconf_DATA = actions-4.rc clock-3.rc tasklist-2.rc panels.xml
xfce4panelconfdir = $(XFCE4DIR)/panel

EXTRA_DIST = \
	$(xfce4conf_DATA) \
	$(xfce4kioskconf_DATA) \
	$(xfce4mcsconf_DATA) \
	$(xfce4panelconf_DATA)
