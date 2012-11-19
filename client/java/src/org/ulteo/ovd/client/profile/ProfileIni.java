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

import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import org.ini4j.Ini;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.Constants;

public class ProfileIni extends Profile {
	
	private static final String PROFILE_EXT = ".conf";
	public static final String DEFAULT_PROFILE = "default";

	private File confDir = null;
	private File file = null;

	public ProfileIni(String profile, String path) {
		this.confDir = new File(Constants.PATH_NATIVE_CLIENT_CONF);
		
		this.setProfile(profile, path);
	}

	private void setProfile(String profile, String path) {
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

	public static List<String> listProfiles() {
		File confFolder = new File(Constants.PATH_NATIVE_CLIENT_CONF);
		
		if ((! confFolder.exists()) || (! confFolder.isDirectory()))
			return null;

		List<String> profilesList = new ArrayList<String>();

		File[] files = confFolder.listFiles();

		for (int i = 0; i < files.length; i++) {
			String name = files[i].getName();
			if (files[i].isFile() && name.endsWith(PROFILE_EXT)) {
				profilesList.add(name.substring(0, name.length() - PROFILE_EXT.length()));
			}
		}

		return profilesList;
	}

	@Override
	public void saveProfileEntries(HashMap<String, List<Map.Entry<String, String>>> profileEntriesMap) {
		if (! this.file.exists()) {
			this.confDir.mkdirs();
			try {
				this.file.createNewFile();
			} catch (IOException ex) {
				Logger.error("Failed to create the ini file '"+this.file.getPath()+"': "+ex.getMessage());
				return;
			}
		}

		
		Ini ini;
		try {
			ini = new Ini(this.file);
		} catch (IOException ex) {
			Logger.error("Failed to read the ini file '"+this.file.getPath()+"': "+ex.getMessage());
			return;
		}
		
		for (Map.Entry<String, List<Map.Entry<String, String>>> section : profileEntriesMap.entrySet()) {
			String sectionName = section.getKey();
			List<Map.Entry<String, String>> entries = profileEntriesMap.get(sectionName);
			
			for (Map.Entry<String, String> entry : entries) {
				String key = entry.getKey();
				String value = entry.getValue();
			
				ini.put(sectionName, key, value);
			}
		}
		
		try {
			ini.store();
		} catch (IOException ex) {
			Logger.error("Failed to write into the ini file '"+this.file.getPath()+"': "+ex.getMessage());
		}
	}

	@Override
	protected boolean loadProfileEntries() {
		Ini ini = null;
		try {
			ini = new Ini(this.file);
		} catch (IOException ex) {
			Logger.error("Failed to open the ini file '"+this.file.getPath()+"': "+ex.getMessage());
			return false;
		}
		
		for (String sectionName : ini.keySet()) {
			for (Map.Entry<String, String> entry : ini.get(sectionName).entrySet()) {
				this.fillProfileMap(sectionName, entry.getKey(), entry.getValue());
			}
		}
		
		return true;
	}
}
