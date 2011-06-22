/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechavalier <david@ulteo.com> 2011
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

package org.ulteo.utils;

import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.Enumeration;
import java.util.Hashtable;



public class IniFile {
	private Hashtable<String, Hashtable<String, String>> sections;
	
	public IniFile() {
		sections = new Hashtable<String, Hashtable<String, String>>();
	}

	public Hashtable<String, Hashtable<String, String>> getSections() {
		return sections;
	}
	
	public Hashtable<String, String> getSection(String section) {
		return sections.get(section.toLowerCase());
	}
	
	public String getKeyValue(String section, String key) {
		try {
			return (String) getSection(section).get(key.toLowerCase());
		} catch (NullPointerException e) {
			return null;
		}
	}
	
	public int getKeyIntValue(String section, String key) {
		return getKeyIntValue(section, key, 0);
	}
	
	public int getKeyIntValue(String section, String key, int defaultValue) {
		String value = getKeyValue(section, key.toLowerCase());
		if (value == null) {
			return defaultValue;
		}
		else {
			try {
				return Integer.parseInt(value);
			} catch (NumberFormatException e) {
				return 0;
			}
		}
	}

	public String[][] getKeysAndValues(String aSection) {
		Hashtable<String, String> section = getSection(aSection);
		if (section == null) {
			return null;
		}
		String[][] results = new String[section.size()][2];
		int i = 0;
		for (Enumeration<String> f = section.keys(), g = section.elements(); f.hasMoreElements(); i++) {
			results[i][0] = (String) f.nextElement();
			results[i][1] = (String) g.nextElement();
		}
		return results;
	}
	
	public void load(String filename) throws FileNotFoundException {
		load(new FileInputStream(filename));
	}


	public void load(InputStream in) {
		try {
			BufferedReader input = new BufferedReader(new InputStreamReader(in));
			String read;
			Hashtable<String, String> section = null;
			String section_name;
			while ((read = input.readLine()) != null) {
				if (read.startsWith(";") || read.startsWith("#")) {
					continue;
				}
				else if (read.startsWith("[")) {
					section_name = read.substring(1, read.indexOf("]")).toLowerCase();
					section = sections.get(section_name);
					if (section == null) {
						section = new Hashtable<String, String>();
						sections.put(section_name, section);
					}
				}
				else if (read.indexOf("=") != -1 && section != null) {
					int comment_index;
					String key = read.substring(0, read.indexOf("=")).trim().toLowerCase();
					String value = read.substring(read.indexOf("=") + 1).trim();
					comment_index = value.indexOf("#");
					if (comment_index != -1)
						value = value.substring(0, comment_index).trim();
					
					section.put(key, value);
				}
			}
		} catch (Exception e) {
			e.printStackTrace();
		}
	}
}

