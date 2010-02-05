/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

package org.ulteo.ovd;

import java.io.IOException;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;

public class ApplicationInstance {
	private Application app = null;
	private long pid = -1;
	private long token = -1;

	public ApplicationInstance(Application app_) {
		this.app = app_;
	}

	public long getPid() {
		return this.pid;
	}

	public void setPid(long pid_) {
		this.pid = pid_;
	}

	public long getToken() {
		return this.token;
	}

	public void setToken(long token_) {
		this.token = token_;
	}

	public void startApp() throws RdesktopException, IOException, CryptoException {
		if (this.token == -1) {
			System.err.println("You have to set the application token before execute startapp().");
		}

		this.app.getConnection().common.seamlessChannelInstance.send_startapp(this.token, this.app.getCmd(), null);
	}

	public void startApp(long token_) throws RdesktopException, IOException, CryptoException {
		this.token = token_;
		this.startApp();
	}
}
