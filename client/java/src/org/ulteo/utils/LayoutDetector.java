/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2011 
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

import java.awt.im.InputContext;
import java.io.BufferedReader;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.lang.Runtime;
import java.lang.Process;
import java.util.Locale;
import java.util.Properties;


import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;

public class LayoutDetector {
	private final static String keymapLocation = "/resources/keymaps/listKeymap";
	private static final String windows_cmd = "reg query " +"\"HKCU\\Keyboard Layout\\Preload\"  /v " + "1";
	private static final String linux_cmd = "setxkbmap -print -verbose 10";
	
	
	public static String getLayoutString(String code) {
		InputStream keymapStream = LayoutDetector.class.getResourceAsStream(keymapLocation);
		
		Properties proper = new Properties();
		try {
			proper.load(keymapStream);
		} catch (FileNotFoundException e) {
			Logger.warn("Unable to find the resource["+LayoutDetector.keymapLocation+"]: "+e.getMessage());
		} catch (IOException e) {
			Logger.warn("Unable to load ["+LayoutDetector.keymapLocation+"]: "+e.getMessage());
		}
		return proper.getProperty(code);
	}
	
	
	public static String get() {
		if (OSTools.isLinux()) {
			String res = LayoutDetector.getLinuxSpecific();
			if (res != null)
				return res;
		}

		if (OSTools.isWindows()) {
			String res = LayoutDetector.getWindowsSpecific();
			if (res != null)
				return res;
		}
		
		Locale l = InputContext.getInstance().getLocale();
		if (l == null)
			return null;
		
		if (l.getLanguage().equalsIgnoreCase(l.getCountry()))
			return l.getLanguage().toLowerCase();
		
		return (l.getLanguage()+"-"+l.getCountry()).toLowerCase();
	}
	
	
	public static String getLinuxSpecific() {
		String result = null;
		
		try {
			Process p = Runtime.getRuntime().exec(LayoutDetector.linux_cmd);
			p.waitFor();
			if (p.exitValue() == 0) {
				BufferedReader input = new BufferedReader(new InputStreamReader(p.getInputStream()));
				String line;
				
				while ( (line= input.readLine()) != null) {
					if (line.startsWith("layout:")) {
						String rr[] = line.split(" +");
						if (rr.length > 1) {
							result = rr[1];
							break;
						}
					}
				}
				input.close();
			}
		}
		catch (java.io.IOException err) {
			org.ulteo.Logger.warn("LayoutDetector: IOException "+err.getMessage());
		}
		catch(java.lang.InterruptedException err) {
			org.ulteo.Logger.warn("LayoutDetector: InterruptedException "+err.getMessage());
		}
		
		return result;
	}
	
	public static String getWindowsSpecific() {
		Process process = null;
		String line = null;
		String layout = null;

		try {
			process = Runtime.getRuntime().exec(LayoutDetector.windows_cmd);
			if (process == null)
				return null;
			
			process.waitFor();
			if (process.exitValue() != 0)
				return null;
			
			BufferedReader input = new BufferedReader(new InputStreamReader(process.getInputStream()));
			while ( (line = input.readLine()) != null) {
				line = line.toLowerCase();
				if (line.contains("reg_sz")) {
					layout = line.split("reg_sz")[1];
					layout = layout.trim();
					while (layout.charAt(0) == '0')
						layout = layout.replaceFirst("0", "");

					return LayoutDetector.getLayoutString(layout);
				}
			}
		}
		catch (IOException e) {
			Logger.warn("Unable to detect layout: "+e.getMessage());
		} catch (InterruptedException e) {
			Logger.warn("Unable to detect layout Operation stopped: "+e.getMessage());
		}
		return null;
	}
}
