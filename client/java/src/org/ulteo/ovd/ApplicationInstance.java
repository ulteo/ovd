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
	public static final int STOPPING = 0;
	public static final int STOPPED = 1;
	public static final int STARTING = 2;
	public static final int STARTED = 3;
	private static final int MIN_STATE = 0;
	private static final int MAX_STATE = 3;

	private Application app = null;
	private int token = -1;
	private int state = STOPPED;
	private boolean launchedFromShortcut = false;

	public ApplicationInstance(Application app_, int token_) {
		this.app = app_;
		this.token = token_;
	}

	public int getToken() {
		return this.token;
	}

	public int getState() {
		return this.state;
	}

	public void setState(int state_) {
		if ((state < MIN_STATE) || (state > MAX_STATE)) {
			this.state = STOPPED;
			return;
		}
		this.state = state_;
	}

	public boolean isLaunchedFromShortcut() {
		return this.launchedFromShortcut;
	}

	public void setLaunchedFromShortcut(boolean launchedFromShortcut_) {
		this.launchedFromShortcut = launchedFromShortcut_;
	}

	public void startApp() throws RdesktopException, IOException, CryptoException {
		this.app.getConnection().getOvdAppChannel().sendStartApp(this.token, this.app.getId());
		this.state = STARTING;
	}

	public void stopApp() throws RdesktopException, IOException, CryptoException {
		this.app.getConnection().getOvdAppChannel().sendStopApp(this.token);
		this.state = STOPPING;
	}

	@Override
	public String toString() {
		return this.app.getName();
	}
	
	public Application getApplication() {
		return this.app;
	}
}
