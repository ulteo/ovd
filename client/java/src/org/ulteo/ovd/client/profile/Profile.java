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

import java.io.IOException;
import java.security.GeneralSecurityException;

import org.ulteo.Logger;
import org.ulteo.crypto.AES;
import org.ulteo.crypto.SymmetricCryptography;
import sun.misc.BASE64Decoder;
import sun.misc.BASE64Encoder;

public abstract class Profile {
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
	protected static final String FIELD_INPUT_METHOD = "input-method";
	protected static final String VALUE_SCANCODE_INPUT_METHOD = "scancode";
	protected static final String VALUE_UNICODE_INPUT_METHOD = "unicode";
	
	protected static final String FIELD_RDP_PACKET_COMPRESSION = "usePacketCompression";
	protected static final String FIELD_RDP_USE_OFFSCREEN_CACHE = "useOffscreenCache";
	protected static final String FIELD_RDP_PERSISTENT_CACHE = "usePersistentCache";
	protected static final String FIELD_PERSISTENT_CACHE_PATH = "cachePath";
	protected static final String FIELD_PERSISTENT_CACHE_MAX_CELLS = "cacheMaxCell";
	
	protected static final String FIELD_RDP_SOCKET_TIMEOUT = "socketTimeout";
	protected static final String FIELD_RDP_USE_BANDWIDTH_LIMITATION = "useBandwidthLimitation";
	protected static final String FIELD_LIMITATION_USE_DISK_LIMIT = "useDiskBandwidthLimitation";
	protected static final String FIELD_LIMITATION_DISK_LIMIT = "diskBandwidthLimit";
	
	private SymmetricCryptography crypto = null;

	public Profile() {
		this.crypto = new AES(AES.default_key);
	}
	
	protected abstract void storePassword(String password) throws IOException;
	
	public final void savePassword(String password) throws IOException {
		if (password == null)
			return;

		try {
			byte[] data = this.crypto.encrypt(password.getBytes());
			String encryptedPassword = new BASE64Encoder().encode(data);
			this.storePassword(encryptedPassword);
		} catch (GeneralSecurityException e) {
			Logger.error("saving password failed: " + e.getMessage());
		}
	}

	protected abstract String loadPassword() throws IOException;
	
	protected final String getPassword() throws IOException {
		String hash = this.loadPassword();
		if (hash == null)
			return null;

		String password = null;
		try {
			byte[] data = this.crypto.decrypt(new BASE64Decoder().decodeBuffer(hash));
			password = new String(data);
		} catch (GeneralSecurityException e) {
			Logger.error("getting password failed:" + e.getMessage());
		}
		return password;
	}
}
