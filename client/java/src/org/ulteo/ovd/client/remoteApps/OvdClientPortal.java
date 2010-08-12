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

import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import net.propero.rdp.RdpConnection;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.portal.PortalFrame;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.ovd.integrated.shorcut.WindowsShortcut;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientPortal extends OvdClientRemoteApps implements ComponentListener {
	private PortalFrame portal = null;
	private String username = null;
	private boolean publicated = false;
	private SystemAbstract system = null;
	private Spool spool = null;
	private Thread spoolThread = null;
	private List<Application> appsList = null;
	private boolean autoPublish = false;
	
	public OvdClientPortal(SessionManagerCommunication smComm) {
		super(smComm);

		this.init();
	}

	public OvdClientPortal(SessionManagerCommunication smComm, String login_, boolean autoPublish, Callback obj) {
		super(smComm, obj);
		this.username = login_;
		this.autoPublish = autoPublish;
		
		this.init();
	}

	private void init() {
		this.system = (System.getProperty("os.name").startsWith("Windows")) ? new SystemWindows() : new SystemLinux();
		this.appsList = new ArrayList<Application>();

		this.spool = new Spool(this);
		this.spool.createIconsDir();
		this.spool.createShortcutDir();
		this.system.setShortcutArgumentInstance(this.spool.getInstanceName());
		this.spoolThread = new Thread(this.spool);
		this.spoolThread.start();
		this.portal = new PortalFrame(this.username);
		this.portal.addComponentListener(this);
		this.portal.getRunningApplicationPanel().setSpool(spool);
		this.unpublish();
	}

	@Override
	protected void runInit() {}

	@Override
	protected void runSessionReady() {
		this.portal.initButtonPan(this);

		if (this.portal.getApplicationPanel().isScollerInited())
			this.portal.getApplicationPanel().addScroller();
		
		this.portal.setVisible(true);
	}

	@Override
	protected void runExit() {}

	@Override
	protected void runSessionTerminated() {
		this.spoolThread.interrupt();
		while (this.spoolThread.isAlive()) {
			try {
				Thread.sleep(10);
			} catch (InterruptedException ex) {}
		}
		this.spool.deleteTree();
		this.spool = null;
		
		this.portal.setVisible(false);
		this.portal.dispose();
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this.portal.getRunningApplicationPanel());
		} catch (OvdException ex) {
			this.logger.error(ex);
		}

		for (Application app : co.getAppsList()) {
			this.appsList.add(app);
		}
	}

	@Override
	protected void uncustomizeRemoteAppsConnection(RdpConnectionOvd co) {
		try {
			co.removeOvdAppListener(this.portal.getRunningApplicationPanel());
		} catch (OvdException e) {
			e.printStackTrace();
		}
	}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd rc : this.availableConnections) {
			if (rc.getOvdAppChannel() == o) {
				for (Application app : rc.getAppsList()) {
					this.logger.info("Installing application \""+app.getName()+"\"");
					this.system.install(app);
					this.portal.getApplicationPanel().toggleAppButton(app, true);
					if (this.autoPublish)
						this.publish(app);
				}
			}
		}
	}

	@Override
	public void ovdInstanceStopped(int instance_) {
		this.spool.destroyInstance(instance_);
	}

	@Override
	protected void display(RdpConnection co) {}

	@Override
	protected void hide(RdpConnection co) {

		for (Application app : ((RdpConnectionOvd)co).getAppsList()) {
			this.portal.getApplicationPanel().toggleAppButton(app, false);
			this.system.uninstall(app);
		}
	}

	public boolean togglePublications() {
		if (publicated) {
			this.unpublish();
		}
		else {
			this.publish();
		}
		return publicated;
	}

	public void publish(Application app) {
		String shortcutName = WindowsShortcut.replaceForbiddenChars(app.getName()).concat(".lnk");

		if (new File(Constants.PATH_SHORTCUTS+Constants.FILE_SEPARATOR+shortcutName).exists())
			this.copyShortcut(shortcutName);
		else
			this.logger.error("Unable to copy "+shortcutName+": The shortcut does not exist.");
	}

	public void publish() {
		File shortcut = new File(Constants.PATH_SHORTCUTS);
		String[] shortcutList = shortcut.list();
		if (shortcutList != null) {
			for (String each : shortcutList) {
				copyShortcut(each);
			}
		}
		
		this.publicated = true;
	}

	public void unpublish() {
		File shortcut = new File(Constants.PATH_SHORTCUTS);
		String[] shortcutList = shortcut.list();
		for (String each : shortcutList) {
			File desktopShortcut = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+each);
			if (desktopShortcut.exists()) {
				desktopShortcut.delete();
			}
			
			File startMenuShortcut = new File(Constants.PATH_STARTMENU+Constants.FILE_SEPARATOR+each);
			if (startMenuShortcut.exists()) {
				startMenuShortcut.delete();
			}
		}
		
		this.publicated = false;
	}

	public void copyShortcut(String shortcut) {
		try {
			BufferedInputStream shortcutReader = new BufferedInputStream(new FileInputStream(Constants.PATH_SHORTCUTS+Constants.FILE_SEPARATOR+shortcut), 4096);
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
			e.printStackTrace();
		}
	}

	public SystemAbstract getSystem() {
		return this.system;
	}

	public void componentShown(ComponentEvent ce) {
		if (ce.getComponent() == this.portal) {
			Collections.sort(this.appsList);
			this.portal.getApplicationPanel().initButtons(this.appsList);
		}
	}

	public void componentResized(ComponentEvent ce) {}
	public void componentMoved(ComponentEvent ce) {}
	public void componentHidden(ComponentEvent ce) {}
	
	public boolean isAutoPublish() {
		return this.autoPublish;
	}
}
