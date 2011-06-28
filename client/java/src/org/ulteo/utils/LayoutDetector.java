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
import java.io.IOException;
import java.io.InputStreamReader;
import java.lang.Runtime;
import java.lang.Process;
import java.util.Locale;


import org.ulteo.ovd.integrated.OSTools;

public class LayoutDetector {
	public static String get() {
		if (OSTools.isLinux()) {
			String res = LayoutDetector.getLinuxSpecific();
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
	
	public static final String linux_cmd = "setxkbmap -print -verbose 10";
	
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
}
