/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

package org.ulteo.ovd.client;

import java.awt.Dimension;
import java.io.File;
import java.io.IOException;
import java.util.List;

import org.ulteo.ovd.client.profile.ProfileIni;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.profile.ProfileRegistry;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.SessionManagerCommunication;

import net.propero.rdp.RdpConnection;

public class Options {

	public static final int FLAG_USERNAME = 0x00000001;
	public static final int FLAG_PASSWORD = 0x00000002;
	public static final int FLAG_SERVER = 0x00000004;
	public static final int FLAG_PORT = 0x00000600;
	public static final int FLAG_KEYMAP = 0x00000008;
	public static final int FLAG_LANGUAGE = 0x00000010;
	public static final int FLAG_GEOMETRY = 0x00000020;
	public static final int FLAG_SESSION_MODE = 0x00000040;
	public static final int FLAG_NTLM = 0x00000080;
	public static final int FLAG_SHOW_PROGRESS_BAR = 0x00000100;
	public static final int FLAG_AUTO_INTEGRATION = 0x00000200;
	public static final int FLAG_AUTO_START = 0x00000400;
	public static final int FLAG_REMEMBER_ME = 0x00004000;
	public static final int FLAG_GUI_LOCKED = 0x00008000;
	public static final int FLAG_SHOW_BURGREPORTER = 0x00006000;
	
	public int mask = 0x00000000;
	
	public String profile = null;
	public String username = null;
	public String password = null;
	public String host = null;
	public int port = SessionManagerCommunication.DEFAULT_PORT;
	public String keymap = null;
	public String lang = null;
	public Dimension geometry = new Dimension(RdpConnection.DEFAULT_WIDTH, RdpConnection.DEFAULT_HEIGHT);
	public int sessionMode = -1;
	public boolean nltm = false;
	public boolean showProgressBar = true;
	public boolean autopublish = false;
	public boolean autostart = false;
	public boolean debugSeamless = false;
	public boolean guiLocked = false;
	public boolean isBugReporterVisible = true;

	
	public Options() {
	}


	public boolean getIniProfile(String path) {
		ProfileIni ini = new ProfileIni();

		if (path == null) {
			List<String> profiles = ini.listProfiles();

			if (profiles == null)
				return false;

			profile = ProfileIni.DEFAULT_PROFILE;

			if (! profiles.contains(profile))
				return false;
		}
		else {
			File file = new File(path);
			profile = file.getName();
			path = file.getParent();
		}

		ProfileProperties properties = null;
		try {
			properties = ini.loadProfile(profile, path);
		} catch (IOException ex) {
			System.err.println("Unable to load \""+profile+"\" profile: "+ex.getMessage());
			return false;
		}
		
		this.parseProperties(properties);

		this.mask |= Options.FLAG_REMEMBER_ME;

		return true;
	}

	
	public boolean getRegistryProfile() {
		ProfileProperties properties = ProfileRegistry.loadProfile();
		if (properties == null)
			return false;

		this.parseProperties(properties);

		this.mask |= Options.FLAG_REMEMBER_ME;

		return true;
	}

	
	private void parseProperties(ProfileProperties properties) {
		if (properties == null)
			return;

		if ((this.mask & Options.FLAG_SESSION_MODE) == 0) {
			this.sessionMode =  Properties.MODE_ANY;
			if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
				this.sessionMode = Properties.MODE_REMOTEAPPS;
			else if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
				this.sessionMode = Properties.MODE_DESKTOP;
		}

		if ((this.mask & Options.FLAG_USERNAME) == 0) {
			String username = properties.getLogin();
			if (username != null) {
				this.username = username;
				this.mask |= Options.FLAG_USERNAME;
			}
		}
		if ((this.mask & Options.FLAG_SERVER) == 0) {
			String host = properties.getHost();
			if (host != null) {
				this.host = host;
				this.mask |= Options.FLAG_SERVER;
			}
		}
		if ((this.mask & Options.FLAG_PORT) == 0) {
			int port = properties.getPort();
			if (port == 0)
				port = SessionManagerCommunication.DEFAULT_PORT;
			this.port = port;
			this.mask |= Options.FLAG_PORT;
		}
		if ((this.mask & Options.FLAG_NTLM) == 0) {
			this.nltm = properties.getUseLocalCredentials();
			this.mask |= Options.FLAG_NTLM;
		}
		if ((this.mask & Options.FLAG_AUTO_INTEGRATION) == 0) {
			boolean auto_integration = properties.getAutoPublish();
			if (! (auto_integration && this.sessionMode == Properties.MODE_DESKTOP)) {
				this.autopublish = auto_integration;
				this.mask |= Options.FLAG_AUTO_INTEGRATION;
			}
		}
		if ((this.mask & Options.FLAG_LANGUAGE) == 0) {
			String language = properties.getLang();
			if (language != null) {
				lang = language;
				this.mask |= Options.FLAG_LANGUAGE;
			}
		}
		if ((this.mask & Options.FLAG_KEYMAP) == 0) {
			String keymap = properties.getKeymap();
			if (keymap != null) {
				this.keymap = keymap;
				this.mask |= Options.FLAG_KEYMAP;
			}
		}
		if ((this.mask & Options.FLAG_SHOW_PROGRESS_BAR) == 0) {
			this.showProgressBar = properties.getShowProgressbar();
			this.mask |= Options.FLAG_SHOW_PROGRESS_BAR;
		}
		if ((this.mask & Options.FLAG_GEOMETRY) == 0) {
			Dimension geometry = properties.getScreenSize();
			if (! (geometry != null && this.sessionMode == Properties.MODE_REMOTEAPPS)) {
				this.geometry = geometry;
				this.mask |= Options.FLAG_GEOMETRY;
			}
		}
		if ((this.mask & Options.FLAG_GUI_LOCKED) == 0) {
			this.guiLocked = properties.isGUILocked();
			this.mask |= Options.FLAG_GUI_LOCKED;
		}
		if ((this.mask & Options.FLAG_SHOW_BURGREPORTER) == 0) {
			this.isBugReporterVisible = properties.isBugReporterVisible();
			this.mask |= Options.FLAG_SHOW_BURGREPORTER;
		}
	}
}
