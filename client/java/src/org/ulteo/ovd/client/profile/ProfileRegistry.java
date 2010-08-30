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

import com.ice.jni.registry.Registry;
import com.ice.jni.registry.RegistryException;
import com.ice.jni.registry.RegistryKey;
import java.io.IOException;
import java.util.Enumeration;
import org.ulteo.Logger;

public class ProfileRegistry extends Profile {

	public static ProfileProperties loadProfile() {
		ProfileProperties properties = null;
		RegistryKey confKey = Registry.openSubkey(Registry.HKEY_LOCAL_MACHINE, "Software\\Ulteo\\OVD\\NativeClient", RegistryKey.ACCESS_READ);

		if (confKey == null)
			return properties;

		properties = new ProfileProperties();

		try {
			Enumeration fieldsEnum = confKey.valueElements();
			while (fieldsEnum.hasMoreElements()) {
				String field = (String) fieldsEnum.nextElement();
				String value = confKey.getStringValue(field);

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
				else if (field.equalsIgnoreCase(FIELD_SCREENSIZE)) {
					if(value.equalsIgnoreCase(VALUE_800X600))
						properties.setScreenSize(ProfileProperties.SCREENSIZE_800X600);
					else if(value.equalsIgnoreCase(VALUE_1024X768))
						properties.setScreenSize(ProfileProperties.SCREENSIZE_1024X768);
					else if(value.equalsIgnoreCase(VALUE_1280X678))
						properties.setScreenSize(ProfileProperties.SCREENSIZE_1280X678);
					else if(value.equalsIgnoreCase(VALUE_MAXIMIZED))
						properties.setScreenSize(ProfileProperties.MAXIMIZED);
					else
						properties.setScreenSize(ProfileProperties.FULLSCREEN);
				}
				else if (field.equalsIgnoreCase(FIELD_LANG)) {
					properties.setLang(value);
				}
				else if (field.equalsIgnoreCase(FIELD_KEYMAP)) {
					properties.setKeymap(value);
				}
			}
		} catch (RegistryException ex) {
			Logger.error("Getting profile preferencies from registry failed: "+ex.getMessage());
		}


		return properties;
	}
}
