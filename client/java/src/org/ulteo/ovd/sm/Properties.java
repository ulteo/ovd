/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

import org.ulteo.rdp.RdpConnectionOvd;

public class Properties {
	public static final int MODE_ANY = 0;
	public static final int MODE_DESKTOP = 1;
	public static final int MODE_REMOTEAPPS = 2;

	public static final int REDIRECT_DRIVES_NO = 0;
	public static final int REDIRECT_DRIVES_PARTIAL = 1;
	public static final int REDIRECT_DRIVES_FULL = 2;
	
	private int mode = 0;
	private String lang = null;
	private String username = null;
	private String timeZone = null;
	private int duration = 0;
	
	private boolean multimedia = false;
	private boolean printers = false;
	private boolean persistent = false;
	private int drives = REDIRECT_DRIVES_NO;

	private boolean desktop_icons = false;
	private boolean desktop_effects = false;
	
	private int rdp_bpp = RdpConnectionOvd.DEFAULT_BPP;
	
	public Properties(int mode) {
		this.mode = mode;
	}
	
	public int getMode() {
		return this.mode;
	}
	
	public void setMode(int mode) {
		this.mode = mode;
	}
	
	public String getLang() {
		return lang;
	}
	
	public void setLang(String lang) {
		this.lang = lang;
	}
	
	public String getTimeZone() {
		return this.timeZone;
	}
	
	public void setTimeZone(String timeZone) {
		this.timeZone = timeZone;
	}

	public String getUsername() {
		return this.username;
	}

	public void setUsername(String username_) {
		this.username = username_;
	}
	
	public boolean isMultimedia() {
		return multimedia;
	}
	
	public void setMultimedia(boolean multimedia) {
		this.multimedia = multimedia;
	}
	
	public boolean isPrinters() {
		return printers;
	}
	
	public void setPrinters(boolean printers) {
		this.printers = printers;
	}

	public int isDrives() {
		return this.drives;
	}

	public void setDrives(int drives) {
		this.drives = drives;
	}
	
	public int getDuration() {
		return this.duration;
	}
	
	public void setPersistent( boolean value) {
		this.persistent = value; 
	}
	
	public boolean isPersistent() {
		return this.persistent;
	}

	public void setDuration(int duration_) {
		this.duration = duration_;
	}

	public boolean isDesktopIcons() {
		return this.desktop_icons;
	}

	public void setDesktopIcons(boolean desktop_icons_) {
		this.desktop_icons = desktop_icons_;
	}

	public boolean isDesktopEffectsEnabled() {
		return this.desktop_effects;
	}

	public void setDesktopEffects(boolean desktop_effects_) {
		this.desktop_effects = desktop_effects_;
	}
	
	public int getRDPBpp() {
		return this.rdp_bpp;
	}

	public void setRDPBpp(int rdp_bpp_) {
		this.rdp_bpp = rdp_bpp_;
	}
}
