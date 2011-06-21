/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
 * Author Thomas MOUTON <thomas@ulteo.com> 2010-2011
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
import org.ulteo.ovd.client.authInterface.LoadingStatus;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.sm.Callback;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.ServerAccess;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.ovd.sm.SessionManagerException;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.RdpConnectionOvd;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import javax.swing.ImageIcon;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.jni.WorkArea;
import org.ulteo.ovd.integrated.Spool;
import org.ulteo.ovd.integrated.SystemAbstract;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.SystemWindows;

public abstract class OvdClientRemoteApps extends OvdClient implements OvdAppListener {

	protected Spool spool = null;
	protected SystemAbstract system = null;

	private int numberOfApplication = 0;
	private int ApplicationIncrement = 0;
	private int ApplicationIndex = 0;

	private int flags = 0;
	private Rectangle screensize = null;
	private int bpp = RdpConnectionOvd.DEFAULT_BPP;

	private boolean debugSeamless = false;
	protected boolean publicated = false;
	protected boolean showDesktopIcons = false;
	
	public OvdClientRemoteApps(SessionManagerCommunication smComm) {
		this(smComm, null);
	}

	public OvdClientRemoteApps(SessionManagerCommunication smComm, Callback obj) {
		super(smComm, obj, false);
		
		String sm = this.smComm.getHost();
		if (OSTools.isWindows()) {
			this.system = new SystemWindows(sm);
		} else if (OSTools.isLinux()) {
			this.system = new SystemLinux(sm);
		} else {
			Logger.warn("This Operating System is not supported");
		}
		
		this.spool = new Spool(this);
		this.spool.createIconsDir();
		this.spool.createShortcutDir();
		this.system.setShortcutArgumentInstance(this.spool.getInstanceName());
		this.spool.start();
	}

	@Override
	public String getInstance() {
		if (this.spool == null)
			return null;

		return this.spool.getInstanceName();
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

		for (Application app : co.getAppsList()) {
			if (this.system.create(app) == null)
				org.ulteo.Logger.error("The "+app.getName()+" shortcut could not be created");

			int subStatus = this.ApplicationIndex * this.ApplicationIncrement;
			this.obj.updateProgress(LoadingStatus.CLIENT_INSTALL_APPLICATION, subStatus);
			this.ApplicationIndex++;
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
		if (this.spool != null) {
			this.spool.stop();
			this.spool.deleteTree();
			this.spool = null;
		}

		for (RdpConnectionOvd co : this.connections) {
			for (Application app : co.getAppsList())
				this.system.clean(app);
		}
		this.system.refresh();
	}

	public void ovdInited(OvdAppChannel channel) {
		try {
			this.publish(find(channel));
			this.system.refresh();
		} catch (NullPointerException e) {
			Logger.warn(String.format("channel %s not found", channel.name()));
		}
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

		if (this.keymap != null)
			rc.setKeymap(this.keymap);
		
		if (this.inputMethod != null)
			rc.setInputMethod(this.inputMethod);

		// application icon processing
		HashMap<Integer, ImageIcon> appsIcons = new HashMap<Integer, ImageIcon>();
		for (org.ulteo.ovd.sm.Application appItem : server.applications) {
			if (this.isCancelled)
				return null;

			int subStatus = this.ApplicationIndex * this.ApplicationIncrement;
			this.obj.updateProgress(LoadingStatus.SM_GET_APPLICATION, subStatus);

			int appId = appItem.getId();
			ImageIcon appIcon = this.system.getAppIcon(appId);
			if (appIcon == null) {
				appIcon = this.smComm.askForIcon(appItem);
				if (appIcon != null)
					appsIcons.put(appId, appIcon);
			}

			rc.addApp(new Application(rc, appId, appItem.getName(), appItem.getMimes(), appIcon));
			this.ApplicationIndex++;
		}
		int updatedIcons = this.system.updateAppsIconsCache(appsIcons);
		if (updatedIcons > 0)
			Logger.info("Applications cache updated: "+updatedIcons+" icons");

		// mime-type icon processing
		HashSet<String> mimesTypes = new HashSet<String>();
		HashMap<String, ImageIcon> mimeTypesIcons = new HashMap<String, ImageIcon>();
		for (org.ulteo.ovd.sm.Application appItem : server.applications) {
			for (String mimeType : appItem.getMimes()) {
				if (! mimesTypes.add(mimeType) || (this.system.getMimeTypeIcon(mimeType) != null))
					continue;

				ImageIcon icon = this.smComm.askForMimeTypeIcon(mimeType);
				if (icon != null)
					mimeTypesIcons.put(mimeType, icon);
			}
		}
		updatedIcons = this.system.updateMimeTypesIconsCache(mimeTypesIcons);
		if (updatedIcons > 0)
			Logger.info("Mime-types cache updated: "+updatedIcons+" icons");

		// Ensure that width is multiple of 4
		// Prevent artifact on screen with a with resolution
		// not divisible by 4
		rc.setGraphic((int) this.screensize.width & ~3, (int) this.screensize.height, this.bpp);
		rc.setGraphicOffset(this.screensize.x, this.screensize.y);

		this.connections.add(rc);

		return rc;
	}

	protected void _createRDPConnections() {
		if (this.smComm == null) {
			Logger.error("[Programmer error] OvdclientRemoteApps.createRDPConnections() can be used only if 'smComm' variable is not null");
			return;
		}
		
		Properties properties = this.smComm.getResponseProperties();

		this.configureRDP(properties);
		
		List<ServerAccess> serversList = this.smComm.getServers();
		this.numberOfApplication = 0;

		for (ServerAccess server : serversList)
			this.numberOfApplication += server.applications.size();

		this.ApplicationIncrement = 100 / numberOfApplication;
		this.ApplicationIndex = 0;

		for (ServerAccess server : serversList) {
			if (this.isCancelled)
				return;

			if (this.createRDPConnection(server) == null)
				continue;
		}
		this.obj.updateProgress(LoadingStatus.SM_GET_APPLICATION, 100);
		this.ApplicationIndex = 0;
	}

	protected boolean _checkRDPConnections() {
		String session_status = null;
		try {
			session_status = this.smComm.askForSessionStatus();
		} catch (SessionManagerException ex) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: "+ex.getMessage()+". Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}
		if (session_status == null) {
			Logger.error("checkRDPConnections -- Failed to get session status from session manager: Internal error. Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}

		if (!(session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_INITED) || session_status.equalsIgnoreCase(SessionManagerCommunication.SESSION_STATUS_ACTIVE))) {
			Logger.info("checkRDPConnections -- Your session has ended. Will exit.");
			for (RdpConnectionOvd co : this.performedConnections) {
				this.hide(co);
			}
			return false;
		}

		boolean retry = false;

		int nbApps = 0;
		int nbAppsAvailable = 0;

		for (RdpConnectionOvd co : this.performedConnections) {
			int nbAppsByServer = co.getAppsList().size();
			nbApps += nbAppsByServer;

			RdpConnection.State state = co.getState();

			if (state == RdpConnection.State.CONNECTED) {
				nbAppsAvailable += nbAppsByServer;
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
	
	@Override
	protected void display(RdpConnection co) {}
	
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
		
		if (rc.getOvdAppChannel().isReady()) {
			boolean associate = (rc.getFlags() & RdpConnectionOvd.MOUNTING_MODE_MASK) != 0;
			for (Application app : rc.getAppsList()) {
				this.system.install(app, this.showDesktopIcons, associate);
			}
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
	}
}
