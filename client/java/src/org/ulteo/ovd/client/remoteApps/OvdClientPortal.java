/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

package org.ulteo.ovd.client.remoteApps;

import java.awt.event.ActionListener;
import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;

import net.propero.rdp.RdpConnection;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.client.portal.PortalFrame;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.News;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.RdpConnectionOvd;

public class OvdClientPortal extends OvdClientRemoteApps implements ComponentListener {
	private PortalFrame portal = null;
	private String username = null;
	private List<Application> appsList = null;
	private List<Application> appsListToEnable = null;
	private boolean autoPublish = false;
	private boolean hiddenAtStart = false;
	private boolean showBugReporter = false;
	
	public OvdClientPortal(SessionManagerCommunication smComm) {
		super(smComm);

		this.init();
	}

	public OvdClientPortal(SessionManagerCommunication smComm, String login_, boolean autoPublish, boolean showDesktopIcons_, boolean hiddenAtStart_, boolean showBugReporter_, Callback obj) {
		super(smComm, obj);
		this.username = login_;
		this.autoPublish = autoPublish;
		this.showDesktopIcons = showDesktopIcons_;
		this.hiddenAtStart = hiddenAtStart_;
		this.showBugReporter = showBugReporter_;
		
		this.init();
	}

	private void init() {
		String sm = this.smComm.getHost();
		this.system = (System.getProperty("os.name").startsWith("Windows")) ? new SystemWindows(sm) : new SystemLinux(sm);
		this.appsList = new ArrayList<Application>();

		this.spool = new Spool(this);
		this.spool.createIconsDir();
		this.spool.createShortcutDir();
		this.system.setShortcutArgumentInstance(this.spool.getInstanceName());
		this.spool.start();
		this.portal = new PortalFrame(this.username, this.showBugReporter);
		this.portal.addComponentListener(this);
		this.portal.getRunningApplicationPanel().setSpool(spool);
		this.publicated = this.autoPublish;
	}

	@Override
	protected void runSessionReady() {
		this.portal.initButtonPan(this);

		if (this.portal.getApplicationPanel().isScollerInited())
			this.portal.getApplicationPanel().addScroller();
		
		this.portal.setVisible(! this.hiddenAtStart);
	}

	@Override
	protected void runExit() {}
	
	@Override
	protected void runDisconnecting() {
		if (this.portal.getSystray() == null)
			return;
		
		ActionListener[] action = this.portal.getSystray().getActionListeners();
		for (ActionListener each : action)
			this.portal.getSystray().removeActionListener(each);
	}

	@Override
	protected void runSessionTerminated() {
		super.runSessionTerminated();
		
		this.portal.setVisible(false);
		if (this.portal.getSystray() != null)
			this.portal.getSystray().removeSysTray();
		this.portal.dispose();
	}

	@Override
	protected void customizeRemoteAppsConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this.portal.getRunningApplicationPanel());
		} catch (OvdException ex) {
			Logger.error(co.getServer()+": Failed to add ovd applications listener: "+ex);
		}
		this.obj.updateProgress(LoadingStatus.CLIENT_WAITING_SERVER, 0);

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
		super.ovdInited(o);
		
		for (RdpConnectionOvd rc : this.availableConnections) {
			if (rc.getOvdAppChannel() == o) {
				for (Application app : rc.getAppsList()) {
					if (this.autoPublish) {
						boolean associate = (rc.getFlags() & RdpConnectionOvd.MOUNTING_MODE_MASK) != 0;
						
						this.system.install(app, this.showDesktopIcons, associate);
					}

					if (! this.portal.isVisible()) {
						if (this.appsListToEnable == null)
							this.appsListToEnable = new ArrayList<Application>();
						this.appsListToEnable.add(app);
					}
					else
						this.portal.getApplicationPanel().toggleAppButton(app, true);
				}
			}
			if (this.obj != null ) {
				this.obj.sessionConnected();
			}
		}

		this.system.refresh();

		if (this.availableConnections.size() == this.connections.size())
			this.portal.enableIconsButton();
	}

	@Override
	public void ovdInstanceStopped(int instance_) {
		ApplicationInstance ai = this.portal.getRunningApplicationPanel().findApplicationInstanceByToken(instance_);
		if (ai.isLaunchedFromShortcut())
			this.spool.destroyInstance(instance_);
	}

	@Override
	protected void hide(RdpConnection co) {
		super.hide(co);
		
		for (Application app : ((RdpConnectionOvd)co).getAppsList()) {
			this.portal.getApplicationPanel().toggleAppButton(app, false);
			this.system.uninstall(app);
		}
	}

	public void componentShown(ComponentEvent ce) {
		if (ce.getComponent() == this.portal) {
			Collections.sort(this.appsList);
			this.portal.getApplicationPanel().initButtons(this.appsList);
		}

		if (this.appsListToEnable != null) {
			for (Application app : this.appsListToEnable)
				this.portal.getApplicationPanel().toggleAppButton(app, true);
		}
	}

	public void componentResized(ComponentEvent ce) {}
	public void componentMoved(ComponentEvent ce) {}
	public void componentHidden(ComponentEvent ce) {}
	
	public boolean isAutoPublish() {
		return this.autoPublish;
	}

	@Override
	public void updateNews(List<News> newsList) {
		if (newsList.size() == 0) {
			if (this.portal.containsNewsPanel()) {
				this.portal.removeNewsPanel();
			}
		}
		else {
			if (! this.portal.containsNewsPanel())
				this.portal.addNewsPanel();
			
			this.portal.getNewsPanel().updateNewsLinks(newsList);
		}
	}
}
