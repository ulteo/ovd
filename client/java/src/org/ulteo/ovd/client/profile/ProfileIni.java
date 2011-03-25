/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

import java.awt.Dimension;
import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import org.ini4j.Ini;
import org.ulteo.Logger;
import org.ulteo.ovd.client.desktop.DesktopFrame;
import org.ulteo.ovd.integrated.Constants;

public class ProfileIni extends Profile {
	private static final String INI_SECTION_USER = "user";
	private static final String INI_SECTION_SERVER = "server";
	private static final String INI_SECTION_SESSION = "session";
	private static final String INI_SECTION_PUBLICATION = "publication";
	private static final String INI_SECTION_SCREEN = "screen";
	private static final String INI_SECTION_GUI = "gui";
	private static final String INI_SECTION_PROXY = "proxy";
	
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

		ini.put(INI_SECTION_USER, FIELD_LOGIN, properties.getLogin());
		ini.put(INI_SECTION_USER, FIELD_LOCALCREDENTIALS, properties.getUseLocalCredentials()?VALUE_TRUE:VALUE_FALSE);

		ini.put(INI_SECTION_SERVER, FIELD_HOST, properties.getHost());
		ini.put(INI_SECTION_SERVER, FIELD_PORT, properties.getPort());
		
		String mode = VALUE_MODE_AUTO;
		if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
			mode = VALUE_MODE_DESKTOP;
		else if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
			mode = VALUE_MODE_APPLICATIONS;
		else
			mode = VALUE_MODE_AUTO;
		ini.put(INI_SECTION_SESSION, FIELD_MODE, mode);
		
		if (properties.getAutoPublish())
			ini.put(INI_SECTION_PUBLICATION, FIELD_AUTOPUBLISH, VALUE_TRUE);
		else
			ini.put(INI_SECTION_PUBLICATION, FIELD_AUTOPUBLISH, VALUE_FALSE);
		

		Dimension screensize = properties.getScreenSize();
		if (screensize != null) {
			if (screensize.equals(DesktopFrame.FULLSCREEN)) {
				ini.put(INI_SECTION_SCREEN, FIELD_SCREENSIZE, VALUE_FULLSCREEN);
			}
			else if (screensize.equals(DesktopFrame.MAXIMISED)) {
				ini.put(INI_SECTION_SCREEN, FIELD_SCREENSIZE, VALUE_MAXIMIZED);
			}
			else {
				ini.put(INI_SECTION_SCREEN, FIELD_SCREENSIZE, screensize.width+"x"+screensize.height);
			}
		}
		
		ini.put(INI_SECTION_SESSION, FIELD_LANG, properties.getLang());
		ini.put(INI_SECTION_SESSION, FIELD_KEYMAP, properties.getKeymap());
		
		ini.put(INI_SECTION_PROXY, PROXY_TYPE, properties.getProxyType().name());
		ini.put(INI_SECTION_PROXY, PROXY_HOST, properties.getProxyHost());
		ini.put(INI_SECTION_PROXY, PROXY_PORT, properties.getProxyPort());
		ini.put(INI_SECTION_PROXY, PROXY_USERNAME, properties.getProxyUsername());
		ini.put(INI_SECTION_PROXY, PROXY_PASSWORD, properties.getProxyPassword());
		
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
		
		value = ini.get(INI_SECTION_USER, FIELD_LOGIN);
		if (value != null)
			properties.setLogin(value);
		
		value = ini.get(INI_SECTION_USER, FIELD_LOCALCREDENTIALS);
		if (value != null) {
			properties.setUseLocalCredentials(value.equals(VALUE_TRUE));
		}

		value = ini.get(INI_SECTION_SERVER, FIELD_HOST);
		if (value != null)
			properties.setHost(value);
		
		value = ini.get(INI_SECTION_SERVER, FIELD_PORT);
		if (value != null) {
			try {
				properties.setPort(Integer.parseInt(value));
			}
			catch (NumberFormatException err) {}
		}
		
		value = ini.get(INI_SECTION_SESSION, FIELD_MODE);
		if (value != null) {
			int mode = ProfileProperties.MODE_AUTO;
			
			if (value.equals(VALUE_MODE_AUTO))
				mode = ProfileProperties.MODE_AUTO;
			else if (value.equals(VALUE_MODE_APPLICATIONS))
				mode = ProfileProperties.MODE_APPLICATIONS;
			else if (value.equals(VALUE_MODE_DESKTOP))
				mode = ProfileProperties.MODE_DESKTOP;

			properties.setSessionMode(mode);
		}
		
		value = ini.get(INI_SECTION_PUBLICATION, FIELD_AUTOPUBLISH);
		if (value != null) {
			if (value.equals(VALUE_TRUE))
				properties.setAutoPublish(true);
			else
				properties.setAutoPublish(false);
		}

		value = ini.get(INI_SECTION_SESSION, FIELD_SHOW_PROGRESSBAR);
		if (value != null) {
			if (value.equals(VALUE_TRUE))
				properties.setShowProgressbar(true);
			else
				properties.setShowProgressbar(false);
		}
		
		value = ini.get(INI_SECTION_SCREEN, FIELD_SCREENSIZE);
		if (value != null) {
			if (value.equalsIgnoreCase(VALUE_MAXIMIZED)) {
				properties.setScreenSize(DesktopFrame.MAXIMISED);
			}
			else if (value.equalsIgnoreCase(VALUE_FULLSCREEN)) {
				properties.setScreenSize(DesktopFrame.FULLSCREEN);
			}
			else {
				int pos = value.indexOf("x");
				if (pos != -1 && value.lastIndexOf("x") == pos) {
					Dimension dim = new Dimension();
					try {
						dim.width = Integer.parseInt(value.substring(0, pos));
						dim.height = Integer.parseInt(value.substring(pos + 1, value.length()));

						properties.setScreenSize(dim);
					} catch (NumberFormatException ex) {
						Logger.error("Failed to parse screen size value: '"+value+"'");
					}
				}
				else {
					Logger.error("Bad screen size value: '"+value+"'");
				}
			}
		}
		
		value = ini.get(INI_SECTION_SESSION, FIELD_LANG);
		if (value != null)
			properties.setLang(value);
		
		value = ini.get(INI_SECTION_SESSION, FIELD_KEYMAP);
		if (value != null)
			properties.setKeymap(value);

		value = ini.get(INI_SECTION_GUI, FIELD_GUI_LOCKED);
		if (value != null)
			properties.setGUILocked(value.equalsIgnoreCase(VALUE_TRUE));

		value = ini.get(INI_SECTION_GUI, FIELD_SHOW_BUGREPORTER);
		if (value != null)
			properties.setBugReporterVisible(value.equalsIgnoreCase(VALUE_TRUE));
		
		value = ini.get(INI_SECTION_PROXY, PROXY_TYPE);
		if (value != null) {
			try {
				ProxyMode v = ProxyMode.valueOf(value.toLowerCase());
				properties.setProxyType(v);
			}
			catch (Exception e) {
				Logger.error("Invalid proxy type: "+value);
			}
		}

		value = ini.get(INI_SECTION_PROXY, PROXY_HOST);
		if (value != null)
			properties.setProxyHost(value);

		value = ini.get(INI_SECTION_PROXY, PROXY_PORT);
		if (value != null)
			properties.setProxyPort(value);

		value = ini.get(INI_SECTION_PROXY, PROXY_USERNAME);
		if (value != null)
			properties.setProxyUsername(value);

		value = ini.get(INI_SECTION_PROXY, PROXY_PASSWORD);
		if (value != null)
			properties.setProxyPassword(value);

		return properties;
	}
}
