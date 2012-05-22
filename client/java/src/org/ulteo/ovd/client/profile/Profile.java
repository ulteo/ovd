/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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

import java.io.IOException;
import java.security.GeneralSecurityException;

import org.ulteo.Logger;
import org.ulteo.crypto.AES;
import org.ulteo.crypto.SymmetricCryptography;
import sun.misc.BASE64Decoder;
import sun.misc.BASE64Encoder;

public abstract class Profile {
	public enum ProxyMode {none, auto, custom};
	
	protected static final String FIELD_LOGIN = "login";
	protected static final String FIELD_PASSWORD = "password";
	protected static final String FIELD_LOCALCREDENTIALS = "use-local-credentials";

	protected static final String FIELD_HOST = "host";
	protected static final String FIELD_PORT = "port";

	protected static final String FIELD_MODE = "mode";
	protected static final String VALUE_MODE_APPLICATIONS = "applications";
	protected static final String VALUE_MODE_AUTO = "auto";
	protected static final String VALUE_MODE_DESKTOP = "desktop";
	protected static final String FIELD_LANG = "language";
	protected static final String FIELD_KEYMAP = "keymap";

	protected static final String FIELD_AUTOPUBLISH = "auto-publish";
	protected static final String VALUE_TRUE = "true";
	protected static final String VALUE_FALSE = "false";

	protected static final String FIELD_SHOW_PROGRESSBAR = "show-progressbar";

	protected static final String FIELD_SCREENSIZE = "size";
	protected static final String VALUE_800X600 = "800x600";
	protected static final String VALUE_1024X768 = "1024x768";
	protected static final String VALUE_1280X678 = "1280x678";
	protected static final String VALUE_MAXIMIZED = "maximized";
	protected static final String VALUE_FULLSCREEN = "fullscreen";

	protected static final String FIELD_GUI_LOCKED = "locked-gui";
	protected static final String FIELD_SHOW_BUGREPORTER = "show-bugreporter";
	
	protected static final String PROXY_TYPE = "type";
	protected static final String PROXY_HOST = "host";
	protected static final String PROXY_PORT = "port";
	protected static final String PROXY_USERNAME = "username";
	protected static final String PROXY_PASSWORD = "password";
	
	protected static final String FIELD_INPUT_METHOD = "input-method";
	protected static final String VALUE_SCANCODE_INPUT_METHOD = "scancode";
	protected static final String VALUE_UNICODE_INPUT_METHOD = "unicode";
	
	protected static final String FIELD_RDP_PACKET_COMPRESSION = "usePacketCompression";
	protected static final String FIELD_RDP_USE_OFFSCREEN_CACHE = "useOffscreenCache";
	protected static final String FIELD_RDP_PERSISTENT_CACHE = "usePersistentCache";
	protected static final String FIELD_PERSISTENT_CACHE_PATH = "cachePath";
	protected static final String FIELD_PERSISTENT_CACHE_MAX_CELLS = "cacheMaxCell";
	
	protected static final String FIELD_RDP_USE_TLS = "useTLS";
	protected static final String FIELD_RDP_SOCKET_TIMEOUT = "socketTimeout";
	protected static final String FIELD_RDP_USE_BANDWIDTH_LIMITATION = "useBandwidthLimitation";
	protected static final String FIELD_LIMITATION_USE_DISK_LIMIT = "useDiskBandwidthLimitation";
	protected static final String FIELD_LIMITATION_DISK_LIMIT = "diskBandwidthLimit";
	
	protected static final String FIELD_RDP_USE_KEEPALIVE = "useKeepAlive";
	protected static final String FIELD_RDP_KEEPALIVE_INTERVAL = "keepAliveInterval";
	
	private static final SymmetricCryptography crypto = new AES(AES.default_key);

	public Profile() {}
	
	public static String cryptPassword(String password) {
		String encryptedPassword = null;
		try {
			byte[] data = Profile.crypto.encrypt(password.getBytes());
			encryptedPassword = new BASE64Encoder().encode(data);
		} catch (GeneralSecurityException e) {
			Logger.error("An error occured while crypting password: " + e.getMessage());
		}
		
		return encryptedPassword;
	}
	
	public static String decryptPassword(String hash) {
		String password = null;
		try {
			byte[] data = Profile.crypto.decrypt(new BASE64Decoder().decodeBuffer(hash));
			password = new String(data);
		} catch (Exception e) {
			Logger.error("An error occured while decrypting password:" + e.getMessage());
		}
		return password;
	}
}
