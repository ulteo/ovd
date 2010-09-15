/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

package org.ulteo.ovd.client.remoteApps;

import java.awt.SystemTray;
import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.util.HashMap;
import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientIntegrated extends OvdClientRemoteApps {

	protected static HashMap<String, String> toMap(String token) {
		HashMap<String, String> map = new HashMap<String, String>();

		map.put(SessionManagerCommunication.FIELD_TOKEN, token);

		return map;
	}

	private Spool spool = null;
	private SystemAbstract system = null;

	public OvdClientIntegrated(SessionManagerCommunication smComm) {
		super(smComm);
		this.init();
	}

	private void init() {
		this.spool = new Spool(this);
	}

	@Override
	protected void runInit() {
		this.spool.createIconsDir();
		if (OSTools.isWindows()) {
			this.system = new SystemWindows();
		}
		else if (OSTools.isLinux()) {
			this.system = new SystemLinux();
		}
		else {
			Logger.warn("This Operating System is not supported");
		}
		
		this.system.setShortcutArgumentInstance(this.spool.getInstanceName());
	}

	@Override
	protected void runSessionReady() {}

	@Override
	protected void runExit() {
		this.spool.start();
		this.spool.waitThreadEnd();
		this.exit(0);
	}

	@Override
	protected void runSessionTerminated() {
		this.spool.deleteTree();
		this.spool = null;
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {}

	@Override
	protected void uncustomizeRemoteAppsConnection(RdpConnectionOvd co) {}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd rc : this.availableConnections) {
			if (rc.getOvdAppChannel() != o)
				continue;

			for (Application app : rc.getAppsList()) {
				String shortcutName = this.system.install(app);
				if (shortcutName == null) {
					Logger.error("The "+app.getName()+" shortcut could not be created");
					continue;
				}
				this.copyShortcut(shortcutName);
			}
		}
	}

	private void copyShortcut(String shortcut) {
		if (shortcut == null)
			return;

		File f = new File(Constants.PATH_SHORTCUTS+Constants.FILE_SEPARATOR+shortcut);
		if (! f.exists()) {
			Logger.error("Cannot copy the '"+shortcut+"' shortcut: The file does not exist ("+f.getName()+")");
			return;
		}

		try {
			BufferedInputStream shortcutReader = new BufferedInputStream(new FileInputStream(f), 4096);
			File desktopShortcut = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+shortcut);
			File startMenuShortcut = new File(Constants.PATH_STARTMENU+Constants.FILE_SEPARATOR+shortcut);

			BufferedOutputStream desktopStream = new BufferedOutputStream(new FileOutputStream(desktopShortcut), 4096);
			BufferedOutputStream startMenuStream = new BufferedOutputStream(new FileOutputStream(startMenuShortcut), 4096);

			int currentChar;
			while ((currentChar = shortcutReader.read()) != -1) {
				desktopStream.write(currentChar);
				startMenuStream.write(currentChar);
			}

			desktopStream.close();
			startMenuStream.close();
			shortcutReader.close();
		} catch(Exception e) {
			Logger.error("An error occured during the shortcut '"+shortcut+"' copy: "+e.getMessage());
		}
	}

	@Override
	protected void display(RdpConnection co) {
		if (SystemTray.isSupported()) {
		}
	}

	@Override
	protected void hide(RdpConnection co) {}

	@Override
	public void ovdInstanceStopped(int instance_) {
		this.spool.destroyInstance(instance_);
	}
}
