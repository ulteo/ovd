/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2012
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

package org.ulteo.ovd.client;

import java.awt.Rectangle;

import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;

import org.ulteo.Logger;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.RdpConnectionOvd;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import javax.swing.ImageIcon;
import org.ulteo.ovd.integrated.DesktopIntegrationListener;
import org.ulteo.ovd.integrated.DesktopIntegrator;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.jni.WorkArea;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;

public abstract class OvdClientRemoteApps extends OvdClient implements OvdAppListener, DesktopIntegrationListener {

	protected Spool spool = null;
	protected SystemAbstract system = null;

	protected int ApplicationIndex = 0;

	private int flags = 0;
	private Rectangle screensize = null;
	private int bpp = RdpConnectionOvd.DEFAULT_BPP;

	private boolean debugSeamless = false;
	protected boolean publicated = false;
	protected boolean showDesktopIcons = false;
	protected boolean performDesktopIntegration = true;
	protected DesktopIntegrator desktopIntegrator = null;

	
	public OvdClientRemoteApps(SessionManagerCommunication smComm) {
		super(smComm, false);
		
		String sm = this.smComm.getHost();
		if (OSTools.isWindows()) {
			this.system = new SystemWindows(sm);
		} else if (OSTools.isLinux()) {
			this.system = new SystemLinux(sm);
		} else {
			Logger.warn("This Operating System is not supported");
		}
		
		this.spool = new Spool(this);
		this.system.setShortcutArgumentInstance(this.spool.getID());
		this.spool.start();
	}

	public Spool getSpool() {
		return this.spool;
	}

	public final void setSeamlessDebugEnabled(boolean enabled) {
		this.debugSeamless = enabled;
	}

	@Override
	protected void customizeConnection(RdpConnectionOvd co) {
		try {
			co.addOvdAppListener(this);
		} catch (OvdException ex) {
			Logger.error(co.getServer()+": Failed to add ovd applications listener: "+ex);
		}
		
		if (this.performDesktopIntegration) {
			this.desktopIntegrator = new DesktopIntegrator(this.system, new ArrayList<RdpConnectionOvd>(this.connections), this.smComm);
			this.desktopIntegrator.addDesktopIntegrationListener(this);
			this.desktopIntegrator.start();
		}
		
		co.setShell("OvdRemoteApps");
	}

	@Override
	public void disconnected(RdpConnection co) {
		try {
			((RdpConnectionOvd)co).removeOvdAppListener(this);
		} catch (OvdException ex) {
			Logger.error(co.getServer()+": Failed to remove ovd applications listener: "+ex);
		}
		super.disconnected(co);
	}
	
	@Override
	protected void runSessionTerminated() {
		this.spool.terminate();

		if (this.system instanceof SystemLinux)
			((SystemLinux) this.system).clearSystemMenu();

		for (RdpConnectionOvd co : this.connections) {
			for (Application app : co.getAppsList())
				this.system.clean(app);
		}
		this.system.refresh();
	}


	// OvdAppListener's listeners

	public void ovdInited(OvdAppChannel channel) {
		RdpConnectionOvd co = find(channel);
		if (co == null || this.desktopIntegrator == null || ! this.desktopIntegrator.isDesktopIntegrationDone(co))
			return;

		try {
			this.publish(co);
		} catch (NullPointerException e) {}
	}

	@Override
	public void ovdInstanceStarted(int instance_) {}
	
	@Override
	public void ovdInstanceStopped(int instance_) {}
	
	@Override
	public void ovdInstanceError(int instance_) {}
	
	
	protected void configureRDP(Properties properties) {
		this.screensize = WorkArea.getWorkAreaSize();

		this.flags = 0x00;
		this.flags |= RdpConnectionOvd.MODE_APPLICATION;

		if (properties.isMultimedia())
			this.flags |= RdpConnectionOvd.MODE_MULTIMEDIA;

		if (properties.isPrinters())
			this.flags |= RdpConnectionOvd.MOUNT_PRINTERS;

		if (properties.isDrives() == Properties.REDIRECT_DRIVES_FULL)
			this.flags |= RdpConnectionOvd.MOUNTING_MODE_FULL;
		else if (properties.isDrives() == Properties.REDIRECT_DRIVES_PARTIAL)
			this.flags |= RdpConnectionOvd.MOUNTING_MODE_PARTIAL;
		
		if (this.debugSeamless)
			this.flags |= RdpConnectionOvd.DEBUG_SEAMLESS;
		
		this.bpp = properties.getRDPBpp();
	}
	
	/**
	 * get an {@link ImageIcon} icon from a specific {@link Application}. It searches
	 * firstly in the disk cache, then if not found, ask it to the Session Manager.
	 * If found, the icon is added to the {@link Application}.
	 * @param app the {@link Application} to get the {@link ImageIcon}
	 * @return Return the {@link ImageIcon} icon found, null instead
	 */
	protected ImageIcon getAppIcon(Application app) {
		++this.ApplicationIndex;
		ImageIcon icon = this.system.getAppIcon(app.getId());
		if (icon == null)
			icon = this.smComm.askForIcon(new org.ulteo.ovd.sm.Application(app.getId(), app.getName()));
		app.setIcon(icon);
		return icon;
	}
	
	/**
	 * get all icons available for a {@link RdpConnectionOvd}.
	 * @param rc the {@link RdpConnectionOvd} to retreive all possible icons
	 */
	public void processIconCache(RdpConnectionOvd rc) {
		HashMap<Integer, ImageIcon> appsIcons = new HashMap<Integer, ImageIcon>();
		for (Application app : rc.getAppsList()) {
			if (this.isCancelled)
				break;

			if (getAppIcon(app) != null)
				appsIcons.put(app.getId(), app.getIcon());
		}
		int updatedIcons = this.system.updateAppsIconsCache(appsIcons);
		if (updatedIcons > 0)
			Logger.info("Applications cache updated: "+updatedIcons+" icons");
	}
	
	public RdpConnectionOvd createRDPConnection(ServerAccess server) {
		if (server == null)
			return null;

		if (this.screensize == null) {
			Logger.error("Failed to initialize RDP connection: RDP configuration is not set");
			return null;
		}

		RdpConnectionOvd rc = null;

		try {
			rc = new RdpConnectionOvd(this.flags);
		} catch (RdesktopException ex) {
			Logger.error("Unable to create RdpConnectionOvd object: "+ex.getMessage());
			return null;
		}

		try {
			rc.initSecondaryChannels();
		} catch (RdesktopException ex) {
			Logger.error("Unable to init channels of RdpConnectionOvd object: "+ex.getMessage());
		}

		rc.enableGatewayMode(server);
		rc.setServer(server.getHost());
		rc.setCredentials(server.getLogin(), server.getPassword());
		this.configure(rc);

		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution
		// not divisible by 4
		rc.setGraphic((int) this.screensize.width & ~3, (int) this.screensize.height, this.bpp);
		rc.setGraphicOffset(this.screensize.x, this.screensize.y);
		
		for (org.ulteo.ovd.sm.Application appItem : server.applications)
			rc.addApp(new Application(rc, appItem, null));
		
		if (rc.getAppsList().isEmpty()) {
			Logger.warn("Do not create RdpConnectionOvd object because there is no available applications on server "+server.getHost());
			return null;
		}
		
		this.connections.add(rc);

		return rc;
	}

	protected void _createRDPConnections(List<ServerAccess> serversList) {
		this.ApplicationIndex = 0;

		for (ServerAccess server : serversList) {
			if (this.isCancelled)
				return;
			
			RdpConnectionOvd rc = this.createRDPConnection(server);
			if (rc != null)
				this.processIconCache(rc);
		}
	}

	protected boolean _checkRDPConnections() {
		String session_status = this.smComm.askForSessionStatus();
		if (session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_UNKNOWN)) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}
		else if (!(session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE))) {
			Logger.info("checkRDPConnections -- Your session has ended. Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}

		boolean retry = false;

		int nbApps = 0;
		int nbAppsAvailable = 0;

		List<RdpConnectionOvd> toRemove = new ArrayList<RdpConnectionOvd>();
		for (RdpConnectionOvd co : this.performedConnections) {
			int nbAppsByServer = co.getAppsList().size();
			nbApps += nbAppsByServer;

			RdpConnection.State state = co.getState();

			if (state == RdpConnection.State.CONNECTED) {
				nbAppsAvailable += nbAppsByServer;
				toRemove.add(co);
				continue;
			}

			if (state != RdpConnection.State.FAILED) {
				Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad connection state("+state+"). Will continue normal process.");
				continue;
			}

			int tryNumber = co.getTryNumber();
			if (tryNumber < 1) {
				Logger.debug("checkRDPConnections "+co.getServer()+" -- Bad try number("+tryNumber+"). Will continue normal process.");
				continue;
			}

			if (tryNumber > 1) {
				Logger.error("checkRDPConnections "+co.getServer()+" -- Several try to connect failed.");
				this.hide(co);
				continue;
			}

			Logger.warn("checkRDPConnections "+co.getServer()+" -- Connection failed. Will try to reconnect.");
			this.performedConnections.remove(co);
			co.connect();
			retry = true;
		}

		for (RdpConnectionOvd co: toRemove)
			this.performedConnections.remove(co);
		
		if (retry)
			return true;

		float percent = ((float) (100 * nbAppsAvailable)) / nbApps;
		if (percent < 50) {
			Logger.error("Less than 50 percent of applications are available("+percent+"%). Will exit.");
			return false;
		}
		Logger.warn("More than 50 percent of applications are available("+percent+"%). Will continue.");

		return true;
	}
	
	public void setPerformDesktopIntegration(boolean value) {
		this.performDesktopIntegration = value;
	}

	@Override
	public void shortcutGenerationIsDone(RdpConnectionOvd co) {
		if (co.getOvdAppChannel().isReady())
			this.publish(co);
	}
	
	/**
	 * toggle all publish/unpublish applications from all {@link RdpConnectionOvd} in the 
	 * current {@link OvdClientRemoteApps}
	 * @return indicate the new publications state
	 */
	public boolean togglePublications() {
		if (this.publicated) {
			for (RdpConnectionOvd rc : this.getAvailableConnections())
				this.unpublish(rc);
			this.publicated = false;
		} else {
			for (RdpConnectionOvd rc : this.getAvailableConnections())
				this.publish(rc);
			this.publicated = true;
		}
		return this.publicated;
	}

	/**
	 * publish all application from a specified {@link RdpConnectionOvd}
	 * @param {@link RdpConnectionOvd}
	 */
	protected void publish(RdpConnectionOvd rc) {
		if (rc == null)
			throw new NullPointerException("RdpConnectionOvd parameter cannot be null");

		if (this.system instanceof SystemLinux)
			((SystemLinux) this.system).installSystemMenu();
		
		if (rc.getOvdAppChannel().isReady()) {
			boolean associate = (rc.getFlags() & RdpConnectionOvd.MOUNTING_MODE_MASK) != 0;
			for (Application app : rc.getAppsList()) {
				this.system.install(app, this.showDesktopIcons, associate);
			}

			this.system.refresh();
		}
	}
	
	/**
	 * unpublish all application from a specified {@link RdpConnectionOvd}
	 * @param {@link RdpConnectionOvd}
	 */
	protected void unpublish(RdpConnectionOvd rc) {
		if (rc == null)
			throw new NullPointerException("RdpConnectionOvd parameter cannot be null");
		
		for (Application app : rc.getAppsList()) {
			this.system.uninstall(app);
		}

		this.system.refresh();
	}
}
