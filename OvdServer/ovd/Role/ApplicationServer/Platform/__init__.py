# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

Platform = None

if Platform is None:
	from ovd import util
	p = util.get_platform()
	
	if p == "linux":
		from Linux.ApplicationsDetection import ApplicationsDetection
		from Linux.ApplicationsStatic import ApplicationsStatic
		from Linux.DomainMicrosoft import DomainMicrosoft
		from Linux.DomainNovell import DomainNovell
		from Linux.DomainUlteo import DomainUlteo
		from Linux.Profile import Profile
		from Linux.Session import Session
		from Linux.TS import TS
		from Linux.User import User
	elif p == "windows":
		from Windows.ApplicationsDetection import ApplicationsDetection
		from Windows.ApplicationsStatic import ApplicationsStatic
		from Windows.DomainMicrosoft import DomainMicrosoft
		from Windows.DomainNovell import DomainNovell
		from Windows.DomainUlteo import DomainUlteo
		from Windows.Profile import Profile
		from Windows.Session import Session
		from Windows.TS import TS
		from Windows.User import User
	
	else:
		raise Exception("Not supported platform")
		
	class _Platform:
		pass
	Platform = _Platform()
	Platform.ApplicationsDetection = ApplicationsDetection
	Platform.ApplicationsStatic = ApplicationsStatic
	Platform.DomainMicrosoft = DomainMicrosoft
	Platform.DomainNovell = DomainNovell
	Platform.DomainUlteo = DomainUlteo
	Platform.Profile = Profile
	Platform.Session = Session
	Platform.TS = TS
	Platform.User = User
