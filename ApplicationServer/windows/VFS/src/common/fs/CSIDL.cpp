/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

#include "CSIDL.h"
#include <Shlobj.h>
#include <common/Logger.h>



CSIDL::CSIDL() { }

CSIDL::~CSIDL() { }

int CSIDL::toID(const std::string& csidl) {
	if (csidl.compare("CSIDL_ADMINTOOLS") == 0)
		return CSIDL_ADMINTOOLS;

	if (csidl.compare("CSIDL_ALTSTARTUP") == 0)
		return CSIDL_ALTSTARTUP;

	if (csidl.compare("CSIDL_APPDATA") == 0)
		return CSIDL_APPDATA;

	if (csidl.compare("CSIDL_BITBUCKET") == 0)
		return CSIDL_BITBUCKET;

	if (csidl.compare("CSIDL_CDBURN_AREA") == 0)
		return CSIDL_CDBURN_AREA;

	if (csidl.compare("CSIDL_COMMON_ADMINTOOLS") == 0)
		return CSIDL_COMMON_ADMINTOOLS;

	if (csidl.compare("CSIDL_COMMON_ALTSTARTUP") == 0)
		return CSIDL_COMMON_ALTSTARTUP;

	if (csidl.compare("CSIDL_COMMON_APPDATA") == 0)
		return CSIDL_COMMON_APPDATA;

	if (csidl.compare("CSIDL_COMMON_DESKTOPDIRECTORY") == 0)
		return CSIDL_COMMON_DESKTOPDIRECTORY;

	if (csidl.compare("CSIDL_COMMON_DOCUMENTS") == 0)
		return CSIDL_COMMON_DOCUMENTS;

	if (csidl.compare("CSIDL_COMMON_FAVORITES") == 0)
		return CSIDL_COMMON_FAVORITES;

	if (csidl.compare("CSIDL_COMMON_MUSIC") == 0)
		return CSIDL_COMMON_MUSIC;

	if (csidl.compare("CSIDL_COMMON_OEM_LINKS") == 0)
		return CSIDL_COMMON_OEM_LINKS;

	if (csidl.compare("CSIDL_COMMON_PICTURES") == 0)
		return CSIDL_COMMON_PICTURES;

	if (csidl.compare("CSIDL_COMMON_PROGRAMS") == 0)
		return CSIDL_COMMON_PROGRAMS;

	if (csidl.compare("CSIDL_COMMON_STARTMENU") == 0)
		return CSIDL_COMMON_STARTMENU;

	if (csidl.compare("CSIDL_COMMON_STARTUP") == 0)
		return CSIDL_COMMON_STARTUP;

	if (csidl.compare("CSIDL_COMMON_TEMPLATES") == 0)
		return CSIDL_COMMON_TEMPLATES;

	if (csidl.compare("CSIDL_COMMON_VIDEO") == 0)
		return CSIDL_COMMON_VIDEO;

	if (csidl.compare("CSIDL_COMPUTERSNEARME") == 0)
		return CSIDL_COMPUTERSNEARME;

	if (csidl.compare("CSIDL_CONNECTIONS") == 0)
		return CSIDL_CONNECTIONS;

	if (csidl.compare("CSIDL_CONTROLS") == 0)
		return CSIDL_CONTROLS;

	if (csidl.compare("CSIDL_COOKIES") == 0)
		return CSIDL_COOKIES;

	if (csidl.compare("CSIDL_DESKTOP") == 0)
		return CSIDL_DESKTOP;

	if (csidl.compare("CSIDL_DESKTOPDIRECTORY") == 0)
		return CSIDL_DESKTOPDIRECTORY;

	if (csidl.compare("CSIDL_DRIVES") == 0)
		return CSIDL_DRIVES;

	if (csidl.compare("CSIDL_FAVORITES") == 0)
		return CSIDL_FAVORITES;

	if (csidl.compare("CSIDL_FONTS") == 0)
		return CSIDL_FONTS;

	if (csidl.compare("CSIDL_HISTORY") == 0)
		return CSIDL_HISTORY;

	if (csidl.compare("CSIDL_INTERNET") == 0)
		return CSIDL_INTERNET;

	if (csidl.compare("CSIDL_INTERNET_CACHE") == 0)
		return CSIDL_INTERNET_CACHE;

	if (csidl.compare("CSIDL_LOCAL_APPDATA") == 0)
		return CSIDL_LOCAL_APPDATA;

	if (csidl.compare("CSIDL_MYDOCUMENTS") == 0)
		return CSIDL_MYDOCUMENTS;

	if (csidl.compare("CSIDL_MYMUSIC") == 0)
		return CSIDL_MYMUSIC;

	if (csidl.compare("CSIDL_MYPICTURES") == 0)
		return CSIDL_MYPICTURES;

	if (csidl.compare("CSIDL_MYVIDEO") == 0)
		return CSIDL_MYVIDEO;

	if (csidl.compare("CSIDL_NETHOOD") == 0)
		return CSIDL_NETHOOD;

	if (csidl.compare("CSIDL_NETWORK") == 0)
		return CSIDL_NETWORK;

	if (csidl.compare("CSIDL_PERSONAL") == 0)
		return CSIDL_PERSONAL;

	if (csidl.compare("CSIDL_PRINTERS") == 0)
		return CSIDL_PRINTERS;

	if (csidl.compare("CSIDL_PRINTHOOD") == 0)
		return CSIDL_PRINTHOOD;

	if (csidl.compare("CSIDL_PROFILE") == 0)
		return CSIDL_PROFILE;

	if (csidl.compare("CSIDL_PROGRAM_FILES") == 0)
		return CSIDL_PROGRAM_FILES;

	if (csidl.compare("CSIDL_PROGRAMS") == 0)
		return CSIDL_PROGRAMS;

	if (csidl.compare("CSIDL_RECENT") == 0)
		return CSIDL_RECENT;

	if (csidl.compare("CSIDL_RESOURCES") == 0)
		return CSIDL_RESOURCES;

	if (csidl.compare("CSIDL_RESOURCES_LOCALIZED") == 0)
		return CSIDL_RESOURCES_LOCALIZED;

	if (csidl.compare("CSIDL_SENDTO") == 0)
		return CSIDL_SENDTO;

	if (csidl.compare("CSIDL_STARTMENU") == 0)
		return CSIDL_STARTMENU;

	if (csidl.compare("CSIDL_STARTUP") == 0)
		return CSIDL_STARTUP;

	log_error("Failed to transform %s", csidl);
	return -1;
}


bool CSIDL::getPath(const std::string& csidl, std::string& out) {
	char temp[MAX_PATH];
	int id = this->toID(csidl);

	if (id == -1)
		return false;

	if (!SHGetSpecialFolderPathA(NULL, temp, id, 0)) {
		log_error("Failed to get special folder path %s", csidl.c_str());
		return false;
	}

	out = temp;
	return true;
}



