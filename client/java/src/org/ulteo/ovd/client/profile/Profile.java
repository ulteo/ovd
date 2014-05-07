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

import java.awt.Dimension;
import java.io.IOException;
import java.security.GeneralSecurityException;
import java.util.AbstractMap;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.ulteo.Logger;
import org.ulteo.crypto.AES;
import org.ulteo.crypto.SymmetricCryptography;
import org.ulteo.ovd.client.desktop.DesktopFrame;
import sun.misc.BASE64Decoder;
import sun.misc.BASE64Encoder;

public abstract class Profile {
	public enum ProxyMode {none, auto, custom};
	
	// [user]
	private static final String SECTION_USER = "user";
	private static final String FIELD_LOGIN = "login";
	private static final String FIELD_PASSWORD = "password";
	private static final String FIELD_LOCALCREDENTIALS = "use-local-credentials";

	// [server]
	private static final String SECTION_SERVER = "server";
	private static final String FIELD_HOST = "host";
	private static final String FIELD_PORT = "port";

	// [session]
	private static final String SECTION_SESSION = "session";
	private static final String FIELD_MODE = "mode";
	protected static final String VALUE_MODE_APPLICATIONS = "applications";
	protected static final String VALUE_MODE_AUTO = "auto";
	protected static final String VALUE_MODE_DESKTOP = "desktop";
	
	private static final String FIELD_LANG = "language";
	private static final String FIELD_KEYMAP = "keymap";
	private static final String FIELD_INPUT_METHOD = "input-method";
	private static final String VALUE_SCANCODE_INPUT_METHOD = "scancode";
	private static final String VALUE_UNICODE_INPUT_METHOD = "unicode";
	public static final String VALUE_UNICODE_LOCAL_IME = "unicode_local_ime";
	private static final String FIELD_SHOW_PROGRESSBAR = "show-progressbar";

	// [publication]
	private static final String SECTION_PUBLICATION = "publication";
	private static final String FIELD_AUTOPUBLISH = "auto-publish";

	// [screen]
	private static final String SECTION_SCREEN = "screen";
	private static final String FIELD_SCREENSIZE = "size";
	private static final String VALUE_MAXIMIZED = "maximized";
	private static final String VALUE_FULLSCREEN = "fullscreen";

	// [gui]
	private static final String SECTION_GUI = "gui";
	private static final String FIELD_GUI_LOCKED = "locked-gui";
	private static final String FIELD_SHOW_BUGREPORTER = "show-bugreporter";
	
	// [proxy]
	private static final String SECTION_PROXY = "proxy";
	private static final String PROXY_TYPE = "type";
	private static final String PROXY_HOST = "host";
	private static final String PROXY_PORT = "port";
	private static final String PROXY_USERNAME = "username";
	private static final String PROXY_PASSWORD = "password";
	
	// [rdp]
	private static final String SECTION_RDP = "rdp";
	private static final String FIELD_RDP_PACKET_COMPRESSION = "usePacketCompression";
	private static final String FIELD_RDP_USE_OFFSCREEN_CACHE = "useOffscreenCache";
	private static final String FIELD_RDP_USE_FRAME_MARKER = "useFrameMarker";
	private static final String FIELD_RDP_USE_TLS = "useTLS";
	private static final String FIELD_RDP_SOCKET_TIMEOUT = "socketTimeout";
	private static final String FIELD_RDP_USE_KEEPALIVE = "useKeepAlive";
	private static final String FIELD_RDP_KEEPALIVE_INTERVAL = "keepAliveInterval";
	private static final String FIELD_RDP_NETWORK_CONNECTION_TYPE = "networkConnectionType";
	
	// [persistentCache]
	private static final String SECTION_PERSISTENT_CACHE = "persistentCache";
	private static final String FIELD_RDP_PERSISTENT_CACHE = "usePersistentCache";
	private static final String FIELD_PERSISTENT_CACHE_PATH = "cachePath";
	private static final String FIELD_PERSISTENT_CACHE_MAX_CELLS = "cacheMaxCell";
	
	// [limitation]
	private static final String SECTION_LIMITATION = "limitation";
	private static final String FIELD_RDP_USE_BANDWIDTH_LIMITATION = "useBandwidthLimitation";
	private static final String FIELD_LIMITATION_USE_DISK_LIMIT = "useDiskBandwidthLimitation";
	private static final String FIELD_LIMITATION_DISK_LIMIT = "diskBandwidthLimit";
	
	private static final SymmetricCryptography crypto = new AES(AES.default_key);
	
	private HashMap<String, List<Map.Entry<String, String>>> profileMap = null;
	
	public Profile() {
		this.profileMap = new HashMap<String, List<Map.Entry<String, String>>>();
	}
	
	private static Map.Entry<String, String> createEntry(String key, String value) {
		return new AbstractMap.SimpleEntry<String, String>(key, value);
	}
	
	public static String cryptPassword(String password) {
		String encryptedPassword = null;
		try {
			byte[] data = Profile.crypto.encrypt(password.getBytes());
			encryptedPassword = new BASE64Encoder().encode(data);
		} catch (GeneralSecurityException e) {
			Logger.error("An error occurred while crypting password: " + e.getMessage());
		}
		
		return encryptedPassword;
	}
	
	public static String decryptPassword(String hash) {
		String password = null;
		try {
			byte[] data = Profile.crypto.decrypt(new BASE64Decoder().decodeBuffer(hash));
			password = new String(data);
		} catch (Exception e) {
			Logger.error("An error occurred while decrypting password:" + e.getMessage());
		}
		return password;
	}

	protected void fillProfileMap(String section, String key, String value) {
		if (section == null || key == null || value == null) {
			Logger.error("Failed to load property '"+key+"' in section '"+section+"'");
			return;
		}

		List<Map.Entry<String, String>> pairsList = this.profileMap.get(section);
		if (pairsList == null) {
			pairsList = new ArrayList<Map.Entry<String, String>>();
			this.profileMap.put(section, pairsList);
		}

		pairsList.add(createEntry(key, value));
	}
	
	protected abstract boolean loadProfileEntries();
	
	public ProfileProperties loadProfile() {
		if (! this.loadProfileEntries()) {
			Logger.error("Failed to load profile");
			return null;
		}
		
		ProfileProperties properties = new ProfileProperties();
		
		for (Map.Entry<String, List<Map.Entry<String, String>>> section : this.profileMap.entrySet()) {
			String sectionName = section.getKey();
			List<Map.Entry<String, String>> entries = section.getValue();
			
			for (Map.Entry<String, String> entry : entries) {
				String key = entry.getKey();
				String value = entry.getValue();

				try {
					if (sectionName.equalsIgnoreCase(SECTION_RDP)) {
						this.parseRDPSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_GUI)) {
						this.parseGUISectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_LIMITATION)) {
						this.parseLimitationSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_PERSISTENT_CACHE)) {
						this.parsePersistentCacheSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_PROXY)) {
						this.parseProxySectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_PUBLICATION)) {
						this.parsePublicationSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_SCREEN)) {
						this.parseScreenSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_SERVER)) {
						this.parseServerSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_SESSION)) {
						this.parseSessionSectionEntry(key, value, properties);
					}
					else if (sectionName.equalsIgnoreCase(SECTION_USER)) {
						this.parseUserSectionEntry(key, value, properties);
					}
					else {
						Logger.warn("[Profile.loadProfile] Unknown section: '"+sectionName+"'");
						break;
					}
				} catch (IllegalArgumentException ex) {
					Logger.error("Bad property [section="+sectionName+",key="+key+",value="+value+"]: "+ex.getMessage());
				}
			}
		}
		
		return properties;
	}
	
	private void parseRDPSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_RDP_PACKET_COMPRESSION)) {
			properties.setUsePacketCompression(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_PERSISTENT_CACHE)) {
			properties.setUsePersistantCache(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_USE_OFFSCREEN_CACHE)) {
			properties.setUseOffscreenCache(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_USE_FRAME_MARKER)) {
			properties.setUseFrameMarker(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_USE_BANDWIDTH_LIMITATION)) {
			properties.setUseBandwithLimitation(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_SOCKET_TIMEOUT)) {
			int timeout = getIntFromString(value);
			if (timeout >= 0)
				properties.setSocketTimeout(timeout);
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_USE_KEEPALIVE)) {
			properties.setUseKeepAlive(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_KEEPALIVE_INTERVAL)) {
			int keepAliveInterval = getIntFromString(value);
			if (keepAliveInterval >= 0)
				properties.setKeepAliveInterval(keepAliveInterval);
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_USE_TLS)) {
			properties.setUseTLS(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_RDP_NETWORK_CONNECTION_TYPE)) {
			int networkType = getIntFromString(value);
			if (networkType >= 0)
				properties.setNetworkConnectionType(networkType);
		}
	}
	
	private void parseGUISectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_GUI_LOCKED)) {
			properties.setGUILocked(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_SHOW_BUGREPORTER)) {
			properties.setBugReporterVisible(getBooleanFromString(value));
		}
	}
	
	private void parseLimitationSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_LIMITATION_USE_DISK_LIMIT)) {
			properties.setUseDiskBandwithLimitation(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_LIMITATION_DISK_LIMIT)) {
			int limit = getIntFromString(value);
			if (limit >= 0)
				properties.setDiskBandwidthLimit(limit);
		}
	}
	
	private void parsePersistentCacheSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_PERSISTENT_CACHE_MAX_CELLS)) {
			int max = getIntFromString(value);
			if (max >= 0)
				properties.setPersistentCacheMaxCells(max);
		}
		else if (key.equalsIgnoreCase(FIELD_PERSISTENT_CACHE_PATH)) {
			properties.setPersistentCachePath(value);
		}
	}
	
	private void parseProxySectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(PROXY_HOST)) {
			properties.setProxyHost(value);
		}
		else if (key.equalsIgnoreCase(PROXY_PORT)) {
			properties.setProxyPort(value);
		}
		else if (key.equalsIgnoreCase(PROXY_TYPE)) {
			try {
				ProxyMode v = ProxyMode.valueOf(value.toLowerCase());
				properties.setProxyType(v);
			}
			catch (IllegalArgumentException ex) {
				throw new IllegalArgumentException("Invalid proxy type ('"+value+"'): "+ex.getMessage());
			}
		}
		else if (key.equalsIgnoreCase(PROXY_USERNAME)) {
			properties.setProxyUsername(value);
		}
		else if (key.equalsIgnoreCase(PROXY_PASSWORD)) {
			properties.setProxyPassword(value);
		}
	}
	
	private void parsePublicationSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_AUTOPUBLISH)) {
			properties.setAutoPublish(getBooleanFromString(value));
		}
	}
	
	private void parseScreenSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_SCREENSIZE)) {
			if (value.equalsIgnoreCase(VALUE_MAXIMIZED)) {
				properties.setScreenSize(DesktopFrame.MAXIMISED);
			}
			else if (value.equalsIgnoreCase(VALUE_FULLSCREEN)) {
				properties.setScreenSize(DesktopFrame.FULLSCREEN);
			}
			else {
				int pos = value.indexOf("x");
				if (pos != -1 && value.lastIndexOf("x") == pos) {
					Dimension dim = new Dimension();
					dim.width = getIntFromString(value.substring(0, pos));
					dim.height = getIntFromString(value.substring(pos + 1, value.length()));

					properties.setScreenSize(dim);
				}
				else {
					throw new IllegalArgumentException("Bad screen size value: '"+value+"'");
				}
			}
		}
	}
	
	private void parseServerSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_HOST)) {
			properties.setHost(value);
		}
		else if (key.equalsIgnoreCase(FIELD_PORT)) {
			int port = getIntFromString(value);
			if (port >= 0)
				properties.setPort(port);
		}
	}
	
	private void parseSessionSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_MODE)) {
			int mode = ProfileProperties.MODE_AUTO;
			if (value.toLowerCase().equals(VALUE_MODE_DESKTOP))
				mode = ProfileProperties.MODE_DESKTOP;
			else if (value.toLowerCase().equals(VALUE_MODE_APPLICATIONS))
				mode = ProfileProperties.MODE_APPLICATIONS;
			
			properties.setSessionMode(mode);
		}
		else if (key.equalsIgnoreCase(FIELD_SHOW_PROGRESSBAR)) {
			properties.setShowProgressbar(getBooleanFromString(value));
		}
		else if (key.equalsIgnoreCase(FIELD_LANG)) {
			properties.setLang(value);
		}
		else if (key.equalsIgnoreCase(FIELD_KEYMAP)) {
			properties.setKeymap(value);
		}
		else if (key.equalsIgnoreCase(FIELD_INPUT_METHOD)) {
			properties.setInputMethod(value);
		}
	}
	
	private void parseUserSectionEntry(String key, String value, ProfileProperties properties) {
		if (key.equalsIgnoreCase(FIELD_LOGIN)) {
			properties.setLogin(value);
		}
		else if (key.equalsIgnoreCase(FIELD_PASSWORD)) {
			properties.setLogin(value);
		}
		else if (key.equalsIgnoreCase(FIELD_LOCALCREDENTIALS)) {
			properties.setUseLocalCredentials(getBooleanFromString(value));
		}
	}
	
	private static boolean getBooleanFromString(String str) {
		return (str != null) && (str.equalsIgnoreCase("true") || str.equals("1"));
	}
	
	private static int getIntFromString(String str) {
		int n = -1;
		try {
			n = Integer.parseInt(str);
		}
		catch (NumberFormatException e) {
			throw new IllegalArgumentException("Unable to convert value '" + str + "' to an integer: "+e.getMessage());
		}
		
		return n;
	}
	
	private static String getStringFromScreenSize(Dimension screensize) {
		if (screensize.equals(DesktopFrame.FULLSCREEN)) {
				return VALUE_FULLSCREEN;
		}
		else if (screensize.equals(DesktopFrame.MAXIMISED)) {
			return VALUE_MAXIMIZED;
		}
		return screensize.width + "x" + screensize.height;
	}
	
	private static String getStringFromSessionMode(int mode) {
		switch (mode) {
			case ProfileProperties.MODE_DESKTOP:
				return VALUE_MODE_DESKTOP;
			case ProfileProperties.MODE_APPLICATIONS:
				return VALUE_MODE_APPLICATIONS;
		}
		return VALUE_MODE_AUTO;
	}
	
	protected abstract void saveProfileEntries(HashMap<String, List<Map.Entry<String, String>>> profileEntriesMap);
	
	public void saveProfile(ProfileProperties properties) {
		// [user]
		List<Map.Entry<String, String>> entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_LOCALCREDENTIALS, ""+properties.getUseLocalCredentials()));
		entriesList.add(createEntry(FIELD_LOGIN, properties.getLogin()));
		if (properties.getPassword() != null) {
			entriesList.add(createEntry(FIELD_PASSWORD, properties.getPassword()));
		}
		
		this.profileMap.put(SECTION_USER, entriesList);

		// [server]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_HOST, properties.getHost()));
		entriesList.add(createEntry(FIELD_PORT, ""+properties.getPort()));
		
		this.profileMap.put(SECTION_SERVER, entriesList);

		// [session]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_MODE, getStringFromSessionMode(properties.getSessionMode())));
		entriesList.add(createEntry(FIELD_LANG, properties.getLang()));
		entriesList.add(createEntry(FIELD_KEYMAP, properties.getKeymap()));
		if (properties.getInputMethod() != null)
			entriesList.add(createEntry(FIELD_INPUT_METHOD, properties.getInputMethod()));
		entriesList.add(createEntry(FIELD_SHOW_PROGRESSBAR, ""+properties.getShowProgressbar()));
		
		this.profileMap.put(SECTION_SESSION, entriesList);

		// [publication]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_AUTOPUBLISH, ""+properties.getAutoPublish()));
		
		this.profileMap.put(SECTION_PUBLICATION, entriesList);

		// [screen]
		Dimension screensize = properties.getScreenSize();
		if (screensize != null) {
			entriesList = new ArrayList<Map.Entry<String, String>>();
			entriesList.add(createEntry(FIELD_SCREENSIZE, getStringFromScreenSize(screensize)));

			this.profileMap.put(SECTION_SCREEN, entriesList);
		}

		// [gui]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_GUI_LOCKED, ""+properties.isGUILocked()));
		entriesList.add(createEntry(FIELD_SHOW_PROGRESSBAR, ""+properties.getShowProgressbar()));
		
		this.profileMap.put(SECTION_GUI, entriesList);

		// [proxy]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(PROXY_TYPE, properties.getProxyType().name()));
		entriesList.add(createEntry(PROXY_HOST, properties.getProxyHost()));
		entriesList.add(createEntry(PROXY_PORT, properties.getProxyPort()));
		entriesList.add(createEntry(PROXY_USERNAME, properties.getProxyUsername()));
		entriesList.add(createEntry(PROXY_PASSWORD, properties.getProxyPassword()));
		
		this.profileMap.put(SECTION_PROXY, entriesList);

		// [rdp]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_RDP_PACKET_COMPRESSION, ""+properties.isUsePacketCompression()));
		entriesList.add(createEntry(FIELD_RDP_USE_OFFSCREEN_CACHE, ""+properties.isUseOffscreenCache()));
		entriesList.add(createEntry(FIELD_RDP_USE_FRAME_MARKER, ""+properties.isUseFrameMarker()));
		entriesList.add(createEntry(FIELD_RDP_USE_TLS, ""+properties.isUseTLS()));
		entriesList.add(createEntry(FIELD_RDP_SOCKET_TIMEOUT, ""+properties.getSocketTimeout()));
		entriesList.add(createEntry(FIELD_RDP_USE_KEEPALIVE, ""+properties.isUseKeepAlive()));
		entriesList.add(createEntry(FIELD_RDP_KEEPALIVE_INTERVAL, ""+properties.getKeepAliveInterval()));
		entriesList.add(createEntry(FIELD_RDP_NETWORK_CONNECTION_TYPE, ""+properties.getNetworkConnectionType()));
		
		this.profileMap.put(SECTION_RDP, entriesList);

		// [persistentCache]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_RDP_PERSISTENT_CACHE, ""+properties.isUsePersistantCache()));
		entriesList.add(createEntry(FIELD_PERSISTENT_CACHE_PATH, properties.getPersistentCachePath()));
		entriesList.add(createEntry(FIELD_PERSISTENT_CACHE_MAX_CELLS, ""+properties.getPersistentCacheMaxCells()));
		
		this.profileMap.put(SECTION_PERSISTENT_CACHE, entriesList);

		// [limitation]
		entriesList = new ArrayList<Map.Entry<String, String>>();
		entriesList.add(createEntry(FIELD_RDP_USE_BANDWIDTH_LIMITATION, ""+properties.isUseBandwithLimitation()));
		entriesList.add(createEntry(FIELD_LIMITATION_USE_DISK_LIMIT, ""+properties.isUseDiskBandwithLimitation()));
		entriesList.add(createEntry(FIELD_LIMITATION_DISK_LIMIT, ""+properties.getDiskBandwidthLimit()));
		
		this.profileMap.put(SECTION_LIMITATION, entriesList);
		
		this.saveProfileEntries(this.profileMap);
	}
}
