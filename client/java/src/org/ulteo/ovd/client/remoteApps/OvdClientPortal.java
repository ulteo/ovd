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
import org.apache.log4j.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.authInterface.AuthFrame;
import org.ulteo.ovd.client.authInterface.LoginListener;
import org.ulteo.ovd.client.portal.Menu;
import org.ulteo.ovd.client.portal.PortalFrame;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.ovd.integrated.shorcut.WindowsShortcut;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientPortal extends OvdClientRemoteApps implements ComponentListener {
	private PortalFrame portal = null;
	private boolean publicated = false;
	private SystemAbstract system = null;
	private Spool spool = null;
	private Thread spoolThread = null;
	private List<Application> appsList = null;
	private boolean autoPublish;
	
	public OvdClientPortal(String fqdn_, boolean use_https_, String login_, String password_) {
		super(fqdn_, use_https_, login_, password_);

		this.init();
	}

	public OvdClientPortal(String fqdn_, boolean use_https_, String login_, String password_, AuthFrame frame_, LoginListener logList_, boolean autoPublish) {
		super(fqdn_, use_https_, login_, password_, frame_, logList_);
		this.autoPublish = autoPublish;
		
		this.init();
	}

	private void init() {
		this.system = (System.getProperty("os.name").startsWith("Windows")) ? new SystemWindows() : new SystemLinux();
		this.logger = Logger.getLogger(OvdClientPortal.class);
		this.appsList = new ArrayList<Application>();
		this.spool = new Spool(this);
		this.spool.createIconsDir();
		this.spool.createShortcutDir();
		this.system.setShortcutArgumentInstance(this.spool.getInstanceName());
		this.spoolThread = new Thread(this.spool);
		this.spoolThread.start();
		this.portal = new PortalFrame();
		this.portal.addComponentListener(this);
		this.portal.getMain().getCenter().getCurrent().setSpool(spool);
		this.unpublish();
	}

	@Override
	protected void runInit() {}

	@Override
	protected void runSessionReady(String sessionId) {
		this.portal.initButtonPan(this);
		
		if (this.portal.getMain().getCenter().getMenu().isScollerInited())
			this.portal.getMain().getCenter().getMenu().addScroller();

		this.portal.setVisible(true);
	}

	@Override
	protected void runExit() {}

	@Override
	protected void runSessionTerminated(String sessionId) {
		this.portal.setVisible(false);
		this.portal.dispose();
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this.portal.getMain().getCenter().getCurrent());
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
			co.removeOvdAppListener(this.portal.getMain().getCenter().getCurrent());
		} catch (OvdException e) {
			e.printStackTrace();
		}
	}

	@Override
	public void ovdInited(OvdAppChannel o) {
		for (RdpConnectionOvd rc : this.availableConnections) {
			if (rc.getOvdAppChannel() == o) {
				Menu menu = this.portal.getMain().getCenter().getMenu();
				for (Application app : rc.getAppsList()) {
					this.logger.info("Installing application \""+app.getName()+"\"");
					this.system.install(app);
					menu.install(app);
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
	protected void quitProperly(int i) {
		this.spool.deleteTree();
		this.spoolThread.interrupt();
		while (this.spoolThread.isAlive()) {
			try {
				Thread.sleep(10);
			} catch (InterruptedException ex) {}
		}
		this.spool = null;
	}

	@Override
	protected void display(RdpConnection co) {}

	@Override
	protected void hide(RdpConnection co) {
		Menu menu = this.portal.getMain().getCenter().getMenu();

		for (Application app : ((RdpConnectionOvd)co).getAppsList()) {
			menu.uninstall(app);
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

		if (new File(Constants.clientShortcutsPath+Constants.separator+shortcutName).exists())
			this.copyShortcut(shortcutName);
		else
			this.logger.error("Unable to copy "+shortcutName+": The shortcut does not exist.");
	}

	public void publish() {
		File shortcut = new File(Constants.clientShortcutsPath);
		String[] shortcutList = shortcut.list();
		if (shortcutList != null) {
			for (String each : shortcutList) {
				copyShortcut(each);
			}
		}
		
		this.publicated = true;
	}

	public void unpublish() {
		File shortcut = new File(Constants.clientShortcutsPath);
		String[] shortcutList = shortcut.list();
		for (String each : shortcutList) {
			File desktopShortcut = new File(Constants.desktopPath+Constants.separator+each);
			if (desktopShortcut.exists()) {
				desktopShortcut.delete();
			}
			
			File startMenuShortcut = new File(Constants.startmenuPath+Constants.separator+each);
			if (startMenuShortcut.exists()) {
				startMenuShortcut.delete();
			}
		}
		
		this.publicated = false;
	}

	public void copyShortcut(String shortcut) {
		try {
			BufferedInputStream shortcutReader = new BufferedInputStream(new FileInputStream(Constants.clientShortcutsPath+Constants.separator+shortcut), 4096);
			File desktopShortcut = new File(Constants.desktopPath+Constants.separator+shortcut);
			File startMenuShortcut = new File(Constants.startmenuPath+Constants.separator+shortcut);
			
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
			this.portal.getMain().getCenter().getMenu().initButtons(this.appsList);
		}
	}

	public void componentResized(ComponentEvent ce) {}
	public void componentMoved(ComponentEvent ce) {}
	public void componentHidden(ComponentEvent ce) {}
	
	public boolean isAutoPublish() {
		return this.autoPublish;
	}
}
