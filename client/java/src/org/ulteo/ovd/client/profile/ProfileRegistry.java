/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2012
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
import java.util.Enumeration;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;
import org.ulteo.Logger;

public class ProfileRegistry extends Profile {

	private boolean extractPropertiesFromKey(RegistryKey key) {
		if (key == null)
			return false;

		int numberSubkeys;
		try {
			numberSubkeys = key.getNumberSubkeys();
		} catch (RegistryException ex) {
			Logger.error("Failed to list registry subkeys of '"+key.getFullName()+"': "+ex.getMessage());
			return false;
		}
		
		int cpt = 0;
		while (cpt < numberSubkeys) {
			String sectionName;
			try {
				sectionName = key.regEnumKey(cpt++);
			} catch (RegistryException ex) {
				Logger.error("Failed to load section "+cpt+": "+ex.getMessage());
				continue;
			}
			RegistryKey subkey = Registry.openSubkey(key, sectionName, RegistryKey.ACCESS_READ);
			
			Enumeration<?> fieldsEnum;
			try {
				fieldsEnum = subkey.valueElements();
			} catch (RegistryException ex) {
				Logger.error("Failed to list fields of section '"+sectionName+"': "+ex.getMessage());
				continue;
			}
			while (fieldsEnum.hasMoreElements()) {
				String field = (String) fieldsEnum.nextElement();
				String value;
				try {
					value = subkey.getStringValue(field);
				} catch (RegistryException ex) {
					Logger.error("Failed to get value of field '"+field+"': "+ex.getMessage());
					continue;
				}

				this.fillProfileMap(sectionName, field, value);
			}
		}
		
		return true;
	}

	private RegistryKey initProfileKey() {
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
				return null;
			}
		}

		return key;
	}

	@Override
	protected boolean loadProfileEntries() {
		RegistryKey key = null;
		boolean res = false;

		key = Registry.openSubkey(Registry.HKEY_LOCAL_MACHINE, "Software\\Ulteo\\OVD\\NativeClient", RegistryKey.ACCESS_READ);
		if (key != null) {
			res = this.extractPropertiesFromKey(key);
		}

		key = Registry.openSubkey(Registry.HKEY_CURRENT_USER, "Software\\Ulteo\\OVD\\NativeClient", RegistryKey.ACCESS_READ);
		if (key != null) {
			res = this.extractPropertiesFromKey(key);
		}
		
		return res;
	}

	@Override
	protected void saveProfileEntries(HashMap<String, List<Entry<String, String>>> profileEntriesMap) {
		RegistryKey key = this.initProfileKey();
		
		for (Map.Entry<String, List<Map.Entry<String, String>>> section : profileEntriesMap.entrySet()) {
			String sectionName = section.getKey();
			List<Map.Entry<String, String>> entries = section.getValue();
			
			RegistryKey subkey = Registry.openSubkey(key, sectionName, RegistryKey.ACCESS_WRITE);
			if (subkey == null) {
				try {
					subkey = key.createSubKey(sectionName, "");
				} catch (RegistryException ex) {
					Logger.error("Failed to create '"+sectionName+"' subkey: "+ex.getMessage());
					continue;
				}
			}
			
			for (Map.Entry<String, String> entry : entries) {
				String name = entry.getKey();
				String value = entry.getValue();
				
				if (value == null)
					continue;
				
				try {
					subkey.setValue(new RegStringValue(subkey, name, value));
				} catch (RegistryException ex) {
					Logger.error("Failed to set property '"+name+"': "+ex.getMessage());
					continue;
				}
			}
		}
	}
}
