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

import com.ice.jni.registry.RegStringValue;
import com.ice.jni.registry.Registry;
import com.ice.jni.registry.RegistryException;
import com.ice.jni.registry.RegistryKey;
import java.awt.Dimension;
import java.util.Enumeration;
import org.ulteo.Logger;
import org.ulteo.ovd.client.desktop.DesktopFrame;

public class ProfileRegistry extends Profile {

	private static ProfileProperties extractPropertiesFromKey(RegistryKey key, ProfileProperties properties) {
		if (key == null || properties == null)
			return null;

		try {
			Enumeration<?> fieldsEnum = key.valueElements();
			while (fieldsEnum.hasMoreElements()) {
				String field = (String) fieldsEnum.nextElement();
				String value = key.getStringValue(field);

				if (field.equalsIgnoreCase(FIELD_LOGIN)) {
					properties.setLogin(value);
				}
				else if (field.equalsIgnoreCase(FIELD_LOCALCREDENTIALS)) {
					boolean useLocalCredentials = false;

					if (value.equalsIgnoreCase(VALUE_TRUE))
						useLocalCredentials = true;

					properties.setUseLocalCredentials(useLocalCredentials);
				}
				else if (field.equalsIgnoreCase(FIELD_HOST)) {
					properties.setHost(value);
				}
				else if (field.equalsIgnoreCase(FIELD_PORT)) {
					try {
						properties.setPort(Integer.parseInt(value));
					}
					catch (NumberFormatException err) {}
				}
				else if (field.equalsIgnoreCase(FIELD_MODE)) {
					int mode = ProfileProperties.MODE_AUTO;

					if (value.equalsIgnoreCase(VALUE_MODE_AUTO))
						mode = ProfileProperties.MODE_AUTO;
					else if (value.equalsIgnoreCase(VALUE_MODE_APPLICATIONS))
						mode = ProfileProperties.MODE_APPLICATIONS;
					else if (value.equalsIgnoreCase(VALUE_MODE_DESKTOP))
						mode = ProfileProperties.MODE_DESKTOP;

					properties.setSessionMode(mode);
				}
				else if (field.equalsIgnoreCase(FIELD_AUTOPUBLISH)) {
					boolean autoPublish = false;

					if (value.equalsIgnoreCase(VALUE_TRUE))
						autoPublish = true;

					properties.setAutoPublish(autoPublish);
				}
				else if (field.equalsIgnoreCase(FIELD_SHOW_PROGRESSBAR)) {
					boolean showProgressBar = false;

					if (value.equalsIgnoreCase(VALUE_TRUE))
						showProgressBar = true;

					properties.setShowProgressbar(showProgressBar);
				}
				else if (field.equalsIgnoreCase(FIELD_SCREENSIZE)) {
					int pos = value.indexOf("x");

					if (pos != -1 && value.lastIndexOf("x") == pos) {
						try {
							Dimension dim = new Dimension();
							dim.width = Integer.parseInt(value.substring(0, pos));
							dim.height = Integer.parseInt(value.substring(pos + 1, value.length()));

							properties.setScreenSize(dim);
						} catch (NumberFormatException ex) {
							Logger.error("Failed to parse screen size value: '"+value+"'");
						}
					}
					else if (value.equalsIgnoreCase(VALUE_MAXIMIZED)) {
						properties.setScreenSize(DesktopFrame.MAXIMISED);
					}
					else if (value.equalsIgnoreCase(VALUE_FULLSCREEN)) {
						properties.setScreenSize(DesktopFrame.FULLSCREEN);
					}
					else {
						Logger.error("Failed to parse screen size value: '"+value+"'");
					}
				}
				else if (field.equalsIgnoreCase(FIELD_LANG)) {
					properties.setLang(value);
				}
				else if (field.equalsIgnoreCase(FIELD_KEYMAP)) {
					properties.setKeymap(value);
				}
				else if (field.equalsIgnoreCase(FIELD_GUI_LOCKED)) {
					boolean isGUILocked = false;

					if (value.equalsIgnoreCase(VALUE_TRUE))
						isGUILocked = true;

					properties.setGUILocked(isGUILocked);
				}
				else if (field.equalsIgnoreCase(FIELD_SHOW_BUGREPORTER)) {
					properties.setBugReporterVisible(value.equalsIgnoreCase(VALUE_TRUE));
				}
				else if (field.equalsIgnoreCase(PROXY_TYPE)) {
					try {
						ProxyMode v = ProxyMode.valueOf(value.toLowerCase());
						properties.setProxyType(v);
					}
					catch (Exception e) {
						properties.setProxyType(Profile.ProxyMode.none);
					}
				}
				else if (field.equalsIgnoreCase(PROXY_HOST)) {
					properties.setProxyHost(value);
				}
				else if (field.equalsIgnoreCase(PROXY_PORT)) {
					properties.setProxyPort(value);
				}
				else if (field.equalsIgnoreCase(PROXY_USERNAME)) {
					properties.setProxyUsername(value);
				}
				else if (field.equalsIgnoreCase(PROXY_PASSWORD)) {
					properties.setProxyPassword(value);
				}				
			}
		} catch (RegistryException ex) {
			Logger.error("Getting profile preferencies from registry failed: "+ex.getMessage());
		}
		return properties;
	}

	public static ProfileProperties loadProfile() {
		ProfileProperties properties = new ProfileProperties();

		RegistryKey key = null;
		boolean keyFound = false;

		key = Registry.openSubkey(Registry.HKEY_LOCAL_MACHINE, "Software\\Ulteo\\OVD\\NativeClient", RegistryKey.ACCESS_READ);
		if (key != null) {
			properties = ProfileRegistry.extractPropertiesFromKey(key, properties);
			keyFound = true;
		}

		key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Ulteo\\OVD\\NativeClient", RegistryKey.ACCESS_READ);
		if (key != null) {
			properties = ProfileRegistry.extractPropertiesFromKey(key, properties);
			keyFound = true;
		}

		if (! keyFound)
			return null;

		return properties;
	}

	public static void saveProfile(ProfileProperties properties) {
		RegistryKey key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Ulteo\\OVD\\NativeClient", RegistryKey.ACCESS_WRITE);
		if (key == null) {
			Logger.debug("The key 'HKCU\\Software\\Ulteo\\OVD\\NativeClient' does not exist, will create it");

			key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software", RegistryKey.ACCESS_WRITE);
			try {
				key = key.createSubKey("Ulteo", "");
				key = key.createSubKey("OVD", "");
				key = key.createSubKey("NativeClient", "");
			} catch (RegistryException ex) {
				Logger.error("Failed to create registry key: "+ex.getMessage());
				return;
			}
		}

		try {
			String tmpStr = null;

			tmpStr = properties.getLogin();
			if (tmpStr != null)
				key.setValue(new RegStringValue(key, FIELD_LOGIN, tmpStr));

			tmpStr = properties.getHost();
			if (tmpStr != null)
				key.setValue(new RegStringValue(key, FIELD_HOST, tmpStr));

			tmpStr = properties.getLang();
			if (tmpStr != null)
				key.setValue(new RegStringValue(key, FIELD_LANG, tmpStr));

			tmpStr = properties.getKeymap();
			if (tmpStr != null)
				key.setValue(new RegStringValue(key, FIELD_KEYMAP, tmpStr));

			int sessionMode = properties.getSessionMode();
			if (sessionMode > -1) {
				if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
					tmpStr = VALUE_MODE_DESKTOP;
				else if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
					tmpStr = VALUE_MODE_APPLICATIONS;
				else
					tmpStr = VALUE_MODE_AUTO;
				key.setValue(new RegStringValue(key, FIELD_MODE, tmpStr));
			}

			Dimension screensize = properties.getScreenSize();
			if (screensize != null) {
				if (screensize.equals(DesktopFrame.FULLSCREEN)) {
					key.setValue(new RegStringValue(key, FIELD_SCREENSIZE, VALUE_FULLSCREEN));
				}
				else if (screensize.equals(DesktopFrame.MAXIMISED)) {
					key.setValue(new RegStringValue(key, FIELD_SCREENSIZE, VALUE_MAXIMIZED));
				}
				else {
					key.setValue(new RegStringValue(key, FIELD_SCREENSIZE, screensize.width+"x"+screensize.height));
				}
				screensize = null;
			}

			boolean tmpBool = properties.getAutoPublish();
			key.setValue(new RegStringValue(key, FIELD_AUTOPUBLISH, ""+tmpBool));

			tmpBool = properties.getUseLocalCredentials();
			key.setValue(new RegStringValue(key, FIELD_LOCALCREDENTIALS, ""+tmpBool));

			tmpBool = properties.getShowProgressbar();
			key.setValue(new RegStringValue(key, FIELD_SHOW_PROGRESSBAR, ""+tmpBool));
			
		} catch (RegistryException ex) {
			Logger.error("Setting profile preferencies in the registry failed: "+ex.getMessage());
		}
	}
}
