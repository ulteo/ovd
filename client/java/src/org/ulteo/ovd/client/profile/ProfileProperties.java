/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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

import java.awt.Dimension;

public class ProfileProperties {
	public static final int MODE_AUTO = 0;
	public static final int MODE_DESKTOP = 1;
	public static final int MODE_APPLICATIONS = 2;

	private String login = System.getProperty("user.name");
	private String password = null;
	private String host = null;
	private int port = 0;
	private int sessionMode = -1;
	private boolean autoPublish = false;
	private boolean useLocalCredentials = false;
	private Dimension screensize = null;
	private String lang = null;
	private String keymap = null;
	private String inputMethod = null;
	private boolean showProgressbar = true;
	private boolean isGUILocked = false;
	private boolean isBugReporterVisible = false;
	private boolean usePacketCompression = false;
	private int diskBandwidthLimit = 10000;   // In byte
	private boolean useBandwithLimitation = false;
	private boolean useDiskBandwithLimitation = false;
	private int socketTimeout = 200;            // In millisecond
	private boolean useOffscreenCache = false;
	private boolean usePersistantCache = false;
	private String persistentCachePath = "";
	private int persistentCacheMaxCells = 0;	

	
	public ProfileProperties() {}

	public ProfileProperties(String login_, String host_, int port_, int sessionMode_, boolean autoPublish_, boolean useLocalCredentials_, Dimension screensize_, String lang, String keymap, String inputMethod) {
		this.login = login_;
		this.host = host_;
		this.port = port_;
		this.sessionMode = sessionMode_;
		this.autoPublish = autoPublish_;
		this.useLocalCredentials = useLocalCredentials_;
		this.screensize = screensize_;
		this.lang = lang;
		this.keymap = keymap;
		this.inputMethod = inputMethod;
	}

	public String getLogin() {
		return this.login;
	}

	public void setLogin(String login_) {
		this.login = login_;
	}

	public String getPassword() {
		return this.password;
	}

	public void setPassword(String password_) {
		this.password = password_;
	}

	public String getHost() {
		return this.host;
	}

	public void setHost(String host_) {
		this.host = host_;
	}

	public int getPort() {
		return this.port;
	}

	public void setPort(int port_) {
		this.port = port_;
	}
	
	public int getSessionMode() {
		return this.sessionMode;
	}

	public void setSessionMode(int sessionMode_) {
		this.sessionMode = sessionMode_;
	}

	public boolean getAutoPublish() {
		return this.autoPublish;
	}

	public void setUseLocalCredentials(boolean useLocalCredentials_) {
		this.useLocalCredentials = useLocalCredentials_;
	}
	
	public boolean getUseLocalCredentials() {
		return this.useLocalCredentials;
	}

	public void setAutoPublish(boolean autoPublish_) {
		this.autoPublish = autoPublish_;
	}

	public Dimension getScreenSize() {
		return this.screensize;
	}

	public void setScreenSize(Dimension screenSize_) {
		this.screensize = screenSize_;
	}
	
	public String getLang() {
		return this.lang;
	}
	
	public void setLang(String lang) {
		this.lang = lang;
	}
	
	public String getKeymap() {
		return this.keymap;
	}
	
	public void setKeymap(String keymap) {
		this.keymap = keymap;
	}
	
	public String getInputMethod() {
		return this.inputMethod;
	}
	
	public void setInputMethod(String inputMethod) {
		this.inputMethod = inputMethod;
	}

	public boolean getShowProgressbar() {
		return this.showProgressbar;
	}

	public void setShowProgressbar(boolean showProgressbar_) {
		this.showProgressbar = showProgressbar_;
	}

	public void setGUILocked(boolean guiLocked_) {
		this.isGUILocked = guiLocked_;
	}

	public boolean isGUILocked() {
		return this.isGUILocked;
	}

	public void setBugReporterVisible(boolean visible) {
		this.isBugReporterVisible = visible;
	}

	public boolean isBugReporterVisible() {
		return this.isBugReporterVisible;
	}

	public void setUsePacketCompression(boolean usePacketCompression) {
		this.usePacketCompression = usePacketCompression;
	}

	public boolean isUsePacketCompression() {
		return usePacketCompression;
	}

	public void setUsePersistantCache(boolean usePersistantCache) {
		this.usePersistantCache = usePersistantCache;
	}

	public boolean isUsePersistantCache() {
		return usePersistantCache;
	}

	public void setPersistentCachePath(String persistentCachePath) {
		this.persistentCachePath = persistentCachePath;
	}

	public String getPersistentCachePath() {
		return persistentCachePath;
	}

	public void setPersistentCacheMaxCells(int persistentCacheMaxCells) {
		this.persistentCacheMaxCells = persistentCacheMaxCells;
	}

	public int getPersistentCacheMaxCells() {
		return persistentCacheMaxCells;
	}

	public void setDiskBandwidthLimit(int diskBandwidthLimit) {
		this.diskBandwidthLimit = diskBandwidthLimit;
	}

	public int getDiskBandwidthLimit() {
		return diskBandwidthLimit;
	}

	public void setUseBandwithLimitation(boolean supportBandwithLimitation) {
		this.useBandwithLimitation = supportBandwithLimitation;
	}

	public boolean isUseBandwithLimitation() {
		return useBandwithLimitation;
	}

	public void setUseDiskBandwithLimitation(boolean supportDiskBandwithLimitation) {
		this.useDiskBandwithLimitation = supportDiskBandwithLimitation;
	}

	public boolean isUseDiskBandwithLimitation() {
		return useDiskBandwithLimitation;
	}

	public void setSocketTimeout(int socketTimeout) {
		this.socketTimeout = socketTimeout;
	}

	public int getSocketTimeout() {
		return socketTimeout;
	}

	public void setUseOffscreenCache(boolean useOffscreenCache) {
		this.useOffscreenCache = useOffscreenCache;
	}

	public boolean isUseOffscreenCache() {
		return useOffscreenCache;
	}
}
