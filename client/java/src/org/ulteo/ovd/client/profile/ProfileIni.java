/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd.client.profile;

import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import org.ini4j.Ini;
import org.ulteo.ovd.integrated.Constants;

public class ProfileIni {
	private static final String INI_SECTION_USER = "user";
	private static final String INI_FIELD_LOGIN = "login";
	private static final String INI_FIELD_LOCALCREDENTIALS = "use-local-credentials";

	private static final String INI_SECTION_SERVER = "server";
	private static final String INI_FIELD_HOST = "host";

	private static final String INI_SECTION_SESSION = "sessionMode";
	private static final String INI_FIELD_MODE = "ovdSessionMode";
	private static final String INI_VALUE_MODE_APPLICATIONS = "applications";
	private static final String INI_VALUE_MODE_AUTO = "auto";
	private static final String INI_VALUE_MODE_DESKTOP = "desktop";

	private static final String INI_SECTION_PUBLICATION = "publication";
	private static final String INI_FIELD_AUTOPUBLISH = "auto-publish";
	private static final String INI_VALUE_TRUE = "true";
	private static final String INI_VALUE_FALSE = "false";

	private static final String INI_SECTION_SCREEN = "screen";
	private static final String INI_FIELD_SCREENSIZE = "size";
	private static final String INI_VALUE_800X600 = "800x600";
	private static final String INI_VALUE_1024X768 = "1024x768";
	private static final String INI_VALUE_1280X678 = "1280x678";
	private static final String INI_VALUE_MAXIMIZED = "maximized";
	private static final String INI_VALUE_FULLSCREEN = "fullscreen";

	private static final String PROFILE_EXT = ".conf";
	public static final String DEFAULT_PROFILE = "default";

	private File confDir = null;
	private File file = null;

	public ProfileIni() {
		this.confDir = new File(Constants.PATH_NATIVE_CLIENT_CONF);
	}

	public void setProfile(String profile, String path) {
		if (path == null) {
			if (profile == null) {
				profile = DEFAULT_PROFILE;
			}
			this.file = new File(Constants.PATH_NATIVE_CLIENT_CONF+Constants.FILE_SEPARATOR+profile+PROFILE_EXT);
		}
		else {
			this.file = new File(path+System.getProperty("file.separator")+profile);
		}
	}

	public List<String> listProfiles() {
		if ((! this.confDir.exists()) || (! this.confDir.isDirectory()))
			return null;

		List<String> profilesList = new ArrayList<String>();

		File[] files = this.confDir.listFiles();

		for (int i = 0; i < files.length; i++) {
			String name = files[i].getName();
			if (files[i].isFile() && name.endsWith(PROFILE_EXT)) {
				profilesList.add(name.substring(0, name.length() - PROFILE_EXT.length()));
			}
		}

		return profilesList;
	}

	public void saveProfile(ProfileProperties properties) throws IOException {
		if (this.file == null)
			return;
		
		if (! this.file.exists()) {
			this.confDir.mkdirs();
			this.file.createNewFile();
		}

		Ini ini = new Ini(this.file);

		ini.put(INI_SECTION_USER, INI_FIELD_LOGIN, properties.getLogin());
		ini.put(INI_SECTION_USER, INI_FIELD_LOCALCREDENTIALS, properties.getUseLocalCredentials()?INI_VALUE_TRUE:INI_VALUE_FALSE);

		ini.put(INI_SECTION_SERVER, INI_FIELD_HOST, properties.getHost());
		
		String mode = INI_VALUE_MODE_AUTO;
		if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
			mode = INI_VALUE_MODE_DESKTOP;
		else if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
			mode = INI_VALUE_MODE_APPLICATIONS;
		else
			mode = INI_VALUE_MODE_AUTO;
		ini.put(INI_SECTION_SESSION, INI_FIELD_MODE, mode);
		
		if (properties.getAutoPublish())
			ini.put(INI_SECTION_PUBLICATION, INI_FIELD_AUTOPUBLISH, INI_VALUE_TRUE);
		else
			ini.put(INI_SECTION_PUBLICATION, INI_FIELD_AUTOPUBLISH, INI_VALUE_FALSE);
		

		switch (properties.getScreenSize()) {
			case 0 :
				ini.put(INI_SECTION_SCREEN, INI_FIELD_SCREENSIZE, INI_VALUE_800X600);
				break;
			case 1 :
				ini.put(INI_SECTION_SCREEN, INI_FIELD_SCREENSIZE, INI_VALUE_1024X768);
				break;
			case 2 :
				ini.put(INI_SECTION_SCREEN, INI_FIELD_SCREENSIZE, INI_VALUE_1280X678);
				break;
			case 3 :
				ini.put(INI_SECTION_SCREEN, INI_FIELD_SCREENSIZE, INI_VALUE_MAXIMIZED);
				break;
			case 4 :
				ini.put(INI_SECTION_SCREEN, INI_FIELD_SCREENSIZE, INI_VALUE_FULLSCREEN);
				break;
		}
		ini.store();
	}

	public ProfileProperties loadProfile(String profile, String path) throws IOException {
		if (path == null) {
			if (! this.listProfiles().contains(profile))
				return null;
		}
		else {
			if (! new File(path+System.getProperty("file.separator")+profile).exists())
				return null;
		}
			
		this.setProfile(profile, path);

		ProfileProperties properties = new ProfileProperties();

		Ini ini = new Ini(this.file);
		String value = null;
		
		value = ini.get(INI_SECTION_USER, INI_FIELD_LOGIN);
		if (value != null)
			properties.setLogin(value);
		
		value = ini.get(INI_SECTION_USER, INI_FIELD_LOCALCREDENTIALS);
		if (value != null) {
			properties.setUseLocalCredentials(value.equals(INI_VALUE_TRUE));
		}

		value = ini.get(INI_SECTION_SERVER, INI_FIELD_HOST);
		if (value != null)
			properties.setHost(value);

		value = ini.get(INI_SECTION_SESSION, INI_FIELD_MODE);
		if (value != null) {
			int mode = ProfileProperties.MODE_AUTO;
			
			if (value.equals(INI_VALUE_MODE_AUTO))
				mode = ProfileProperties.MODE_AUTO;
			else if (value.equals(INI_VALUE_MODE_APPLICATIONS)) 
				mode = ProfileProperties.MODE_APPLICATIONS;
			else if (value.equals(INI_VALUE_MODE_DESKTOP))
				mode = ProfileProperties.MODE_DESKTOP;

			properties.setSessionMode(mode);
		}
		
		value = ini.get(INI_SECTION_PUBLICATION, INI_FIELD_AUTOPUBLISH);
		if (value != null) {
			if (value.equals(INI_VALUE_TRUE))
				properties.setAutoPublish(true);
			else
				properties.setAutoPublish(false);
		}
		
		value = ini.get(INI_SECTION_SCREEN, INI_FIELD_SCREENSIZE);
		if (value != null) {
			if(value.equals(INI_VALUE_800X600))
				properties.setScreenSize(ProfileProperties.SCREENSIZE_800X600);
			else if(value.equals(INI_VALUE_1024X768))
				properties.setScreenSize(ProfileProperties.SCREENSIZE_1024X768);
			else if(value.equals(INI_VALUE_1280X678))
				properties.setScreenSize(ProfileProperties.SCREENSIZE_1280X678);
			else if(value.equals(INI_VALUE_MAXIMIZED))
				properties.setScreenSize(ProfileProperties.MAXIMIZED);
			else
				properties.setScreenSize(ProfileProperties.FULLSCREEN);
		}


		return properties;
	}
}
