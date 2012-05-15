/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Laurent CLOUET <laurent@ulteo.com> 2011
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010-2011
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

import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.SessionManagerCommunication;

import net.propero.rdp.RdpConnection;

public class Options {

	public static final int FLAG_PROFILE_INI       = 0x00000001;
	public static final int FLAG_PROFILE_REG       = 0x00000002;
	public static final int FLAG_USERNAME          = 0x00000004;
	public static final int FLAG_PASSWORD          = 0x00000008;
	public static final int FLAG_SERVER            = 0x00000010;
	public static final int FLAG_PORT              = 0x00000020;
	public static final int FLAG_KEYMAP            = 0x00000040;
	public static final int FLAG_LANGUAGE          = 0x00000080;
	public static final int FLAG_GEOMETRY          = 0x00000100;
	public static final int FLAG_SESSION_MODE      = 0x00000200;
	public static final int FLAG_NTLM              = 0x00000400;
	public static final int FLAG_SHOW_PROGRESS_BAR = 0x00000800;
	public static final int FLAG_AUTO_INTEGRATION  = 0x00001000;
	public static final int FLAG_AUTO_START        = 0x00002000;
	public static final int FLAG_REMEMBER_ME       = 0x00004000;
	public static final int FLAG_GUI_LOCKED        = 0x00008000;
	public static final int FLAG_SHOW_BURGREPORTER = 0x00010000;
	public static final int FLAG_INPUT_METHOD      = 0x00020000;
	public static final int FLAG_SAVE_PASSWORD     = 0x00040000;
	
	private int mask = 0x00000000;
	
	public String profile = null;
	public String username = null;
	public String password = null;
	public String host = null;
	public int port = SessionManagerCommunication.DEFAULT_PORT;
	public String keymap = null;
	public String lang = null;
	public String inputMethod = null;
	public Dimension geometry = new Dimension(RdpConnection.DEFAULT_WIDTH, RdpConnection.DEFAULT_HEIGHT);
	public int sessionMode = -1;
	public boolean nltm = false;
	public boolean showProgressBar = true;
	public boolean autopublish = false;
	public boolean autostart = false;
	public boolean debugSeamless = false;
	public boolean guiLocked = false;
	public boolean isBugReporterVisible = false;
	public boolean savePassword = false;
	public boolean usePacketCompression = false;
	public int diskBandwidthLimit = 10000;   // In byte
	public boolean useBandwithLimitation = false;
	public boolean useDiskBandwithLimitation = false;
	public int socketTimeout = 200;            // In millisecond
	public boolean useOffscreenCache = false;
	public boolean usePersistantCache = false;
	public String persistentCachePath = "";
	public int persistentCacheMaxCells = 0;
	
	public Options() {
	}

	public void setFlag (int flag) {
		this.mask |= flag;
	}
	
	public boolean getFlag (int flag) {
		return (this.mask & flag) != 0;
	}

	public void parseProperties(ProfileProperties properties) {
		if (properties == null)
			return;

		if (!this.getFlag(Options.FLAG_SESSION_MODE)) {
			this.sessionMode =  Properties.MODE_ANY;
			if (properties.getSessionMode() == ProfileProperties.MODE_APPLICATIONS)
				this.sessionMode = Properties.MODE_REMOTEAPPS;
			else if (properties.getSessionMode() == ProfileProperties.MODE_DESKTOP)
				this.sessionMode = Properties.MODE_DESKTOP;
		}
		if (!this.getFlag(Options.FLAG_NTLM)) {
			this.nltm = properties.getUseLocalCredentials();
			this.setFlag(Options.FLAG_NTLM);
		}
		
		if (! this.nltm) {
			if (! this.getFlag(Options.FLAG_USERNAME)) {
				String username = properties.getLogin();
				if (username != null) {
					this.username = username;
					this.setFlag(Options.FLAG_USERNAME);
				}
			}
			if (! this.getFlag(Options.FLAG_PASSWORD)) {
				String password = properties.getPassword();
				if (password != null) {
					this.password = password;
					this.setFlag(Options.FLAG_PASSWORD);
				}
			}
		}
		if (!this.getFlag(Options.FLAG_SERVER)) {
			String host = properties.getHost();
			if (host != null) {
				this.host = host;
				this.setFlag(Options.FLAG_SERVER);
			}
		}
		if (!this.getFlag(Options.FLAG_PORT)) {
			int port = properties.getPort();
			if (port == 0)
				port = SessionManagerCommunication.DEFAULT_PORT;
			this.port = port;
			this.setFlag(Options.FLAG_PORT);
		}
		if (!this.getFlag(Options.FLAG_AUTO_INTEGRATION)) {
			boolean auto_integration = properties.getAutoPublish();
			if (! (auto_integration && this.sessionMode == Properties.MODE_DESKTOP)) {
				this.autopublish = auto_integration;
				this.setFlag(Options.FLAG_AUTO_INTEGRATION);
			}
		}
		if (!this.getFlag(Options.FLAG_LANGUAGE)) {
			String language = properties.getLang();
			if (language != null) {
				lang = language;
				this.setFlag(Options.FLAG_LANGUAGE);
			}
		}
		if (!this.getFlag(Options.FLAG_KEYMAP)) {
			String keymap = properties.getKeymap();
			if (keymap != null) {
				this.keymap = keymap;
				this.setFlag(Options.FLAG_KEYMAP);
			}
		}
		if (!this.getFlag(Options.FLAG_INPUT_METHOD)) {
			String inputMethod = properties.getInputMethod();
			if (inputMethod != null) {
				this.inputMethod = inputMethod;
				this.setFlag(Options.FLAG_INPUT_METHOD);
			}
		}
		if (!this.getFlag(Options.FLAG_SHOW_PROGRESS_BAR)) {
			this.showProgressBar = properties.getShowProgressbar();
			this.setFlag(Options.FLAG_SHOW_PROGRESS_BAR);
		}
		if (!this.getFlag(Options.FLAG_GEOMETRY)) {
			Dimension geometry = properties.getScreenSize();
			if (! (geometry != null && this.sessionMode == Properties.MODE_REMOTEAPPS)) {
				this.geometry = geometry;
				this.setFlag(Options.FLAG_GEOMETRY);
			}
		}
		if (!this.getFlag(Options.FLAG_GUI_LOCKED)) {
			this.guiLocked = properties.isGUILocked();
			this.setFlag(Options.FLAG_GUI_LOCKED);
		}
		if (!this.getFlag(Options.FLAG_SHOW_BURGREPORTER)) {
			this.isBugReporterVisible = properties.isBugReporterVisible();
			this.setFlag(Options.FLAG_SHOW_BURGREPORTER);
		}
		if (properties.isUsePacketCompression()) {
			this.usePacketCompression = true;
		}
		if (properties.isUseBandwithLimitation()) {
			this.useBandwithLimitation = true;
			if (properties.getSocketTimeout() > 0)
				this.socketTimeout = properties.getSocketTimeout();
			
			if (properties.isUseDiskBandwithLimitation()) {
				this.useDiskBandwithLimitation = true;
				if (properties.getDiskBandwidthLimit() > 0)
					this.diskBandwidthLimit = properties.getDiskBandwidthLimit(); 
			}
		}
		if (properties.isUseOffscreenCache()) {
			this.useOffscreenCache = true;
		}
		if (properties.isUsePersistantCache()) {
			this.usePersistantCache = true;
			
			this.persistentCachePath = properties.getPersistentCachePath();
			this.persistentCacheMaxCells = properties.getPersistentCacheMaxCells();
		}
		
	}
}
