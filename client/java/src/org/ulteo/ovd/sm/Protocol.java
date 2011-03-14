/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com>
 * Author Julien LANGLOIS <julien@ulteo.com>
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

package org.ulteo.ovd.sm;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

import org.ulteo.Logger;


public class Protocol {
	public static final String SESSION_MODE_PERSISTENT = "persistent";
	public static final String NAME_ATTR_MULTIMEDIA = "multimedia";
	public static final String NAME_ATTR_PRINTERS = "redirect_client_printers";
	public static final String NAME_ATTR_DRIVES = "redirect_client_drives";
	
	public static final String DRIVES_MODE_FULL = "full";
	public static final String DRIVES_MODE_PARTIAL = "partial";
	
	public static final String NAME_DESKTOP_ICONS = "desktop_icons";
	public static final String NAME_USER_EXPERIENCE = "enhance_user_experience";
	public static final String NAME_ATTR_RDP_BPP = "rdp_bpp";
	
	public static final String[] settingsNames = { 
						NAME_DESKTOP_ICONS,
						SESSION_MODE_PERSISTENT,
						NAME_USER_EXPERIENCE,
						NAME_ATTR_MULTIMEDIA,
						NAME_ATTR_PRINTERS,
						NAME_ATTR_DRIVES,
						NAME_ATTR_RDP_BPP,
	};

	public static boolean parseSessionSettings(Properties properties, String name, String value) {
		if (name.equalsIgnoreCase(NAME_DESKTOP_ICONS)) {
			try {
				int val = Integer.parseInt(value);
				properties.setDesktopIcons(val > 0);
			} catch (NumberFormatException ex) {
				Logger.error("Failed to parse value '"+value+"' (name: "+NAME_DESKTOP_ICONS+")");
			}
		}
		if (name.equalsIgnoreCase(SESSION_MODE_PERSISTENT)) {
			try {
				int val = Integer.parseInt(value);
				properties.setPersistent(val > 0);
			} catch (NumberFormatException ex) {
				Logger.error("Failed to parse value '"+value+"' (name: "+SESSION_MODE_PERSISTENT+")");
			}
		}
		if (name.equalsIgnoreCase(NAME_USER_EXPERIENCE)) {
			try {
				int val = Integer.parseInt(value);
				properties.setDesktopEffects(val > 0);
			} catch (NumberFormatException ex) {
				Logger.error("Failed to parse value '"+value+"' (name: "+NAME_USER_EXPERIENCE+")");
			}
		}
		if (name.equalsIgnoreCase(NAME_ATTR_RDP_BPP)) {
			try {
				int val = Integer.parseInt(value);
				properties.setRDPBpp(val);
			} catch (NumberFormatException ex) {
				Logger.error("Failed to parse value '"+value+"' (name: "+NAME_ATTR_RDP_BPP+")");
			}
		}
		if (name.equalsIgnoreCase(NAME_ATTR_MULTIMEDIA)) {
			try {
				int val = Integer.parseInt(value);
				properties.setMultimedia(val > 0);
			} catch (NumberFormatException ex) {
				Logger.error("Failed to parse value '"+value+"' (name: "+NAME_ATTR_MULTIMEDIA+")");
			}
		}
		
		if (name.equalsIgnoreCase(NAME_ATTR_PRINTERS)) {
			try {
				int val = Integer.parseInt(value);
				properties.setPrinters(val > 0);
			} catch (NumberFormatException ex) {
				Logger.error("Failed to parse value '"+value+"' (name: "+NAME_ATTR_PRINTERS+")");
			}
		}
		
		if (name.equalsIgnoreCase(NAME_ATTR_DRIVES)) {
			if (value.equalsIgnoreCase(Protocol.DRIVES_MODE_FULL))
				properties.setDrives(Properties.REDIRECT_DRIVES_FULL);
			else if (value.equalsIgnoreCase(Protocol.DRIVES_MODE_PARTIAL))
				properties.setDrives(Properties.REDIRECT_DRIVES_PARTIAL);
			else
				properties.setDrives(Properties.REDIRECT_DRIVES_NO);
		}
		
		return true;
	}
}
