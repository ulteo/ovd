/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

import org.ulteo.ovd.integrated.RestrictedAccessException;
import java.io.IOException;
import java.security.InvalidParameterException;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.rdpdr.RdpdrDevice;
import org.ulteo.Logger;
import org.ulteo.ovd.disk.DiskManager;
import org.ulteo.ovd.integrated.Constants;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.integrated.SystemLinux;
import org.ulteo.ovd.integrated.RestrictedAccessException;
import java.io.File;
import org.ulteo.ovd.integrated.SystemWindows;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.rdpdr.DeviceListener;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;
import org.ulteo.rdp.rdpdr.OVDRdpdrDisk;
import org.ulteo.utils.I18n;

public class ApplicationInstance implements DeviceListener, OvdAppListener {
	public static final int STOPPING = 0;
	public static final int STOPPED = 1;
	public static final int STARTING = 2;
	public static final int STARTED = 3;
	private static final int MIN_STATE = 0;
	private static final int MAX_STATE = 3;

	private Application app = null;
	private String arg = null;
	private int token = -1;
	private String sharename = null;
	private String path = null;
	private OVDRdpdrDisk waitedDevice = null;
	private int state = STOPPED;
	private boolean launchedFromShortcut = false;

	public ApplicationInstance(Application app_, String arg_, int token_) {
		this.app = app_;
		this.arg = arg_;
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

	private boolean parseArg() {
		if (this.arg == null || this.waitedDevice == null)
			return false;

		int pos = this.arg.lastIndexOf(Constants.FILE_SEPARATOR);
		String local_path = this.arg.substring(0, pos);
		
		String share_local_path = this.waitedDevice.get_local_path();
		if (share_local_path.endsWith(Constants.FILE_SEPARATOR))
			share_local_path = share_local_path.substring(0, share_local_path.length() - 1);

		if (local_path.equals(share_local_path)) {
			this.path = this.arg.substring(pos + 1);
			this.sharename = this.waitedDevice.get_name();

			return true;
		}

		if (share_local_path.length() < local_path.length()) {
			this.sharename = this.waitedDevice.get_name();
			this.path = this.arg.substring(share_local_path.length() + 1, this.arg.length());

			return true;
		}

		return false;
	}

	public void startApp() throws RestrictedAccessException {
		OvdAppChannel ovdApp = this.app.getConnection().getOvdAppChannel();
		
		if (this.arg == null) {
			this.app.getConnection().getOvdAppChannel().sendStartApp(this.token, this.app.getId());
			this.state = STARTING;
			return; 
		}
		
		if (OSTools.isWindows()) {
			String ulteoID = SystemWindows.getKnownDrivesUUIDFromPath(this.arg);
			if (ulteoID != null) {
				String relativePath = this.arg.substring(3);
				relativePath = relativePath.replace(File.separator, "/");
				ovdApp.addOvdAppListener(this);
				ovdApp.sendStartApp(this.token, this.app.getId(), OvdAppChannel.DIR_TYPE_KNOWN_DRIVE, ulteoID, relativePath);
				this.state = STARTING;
				return;
			}
		}
		
		if (OSTools.isLinux()) {
			File args = new File(this.arg);
			String ulteoIDPath = SystemLinux.getKnownDrivesUUIDPathFromPath(args.getAbsolutePath());
			String ulteoID = SystemLinux.getKnownDrivesUUIDFromPath(ulteoIDPath);
			
			if (ulteoID != null) {
				String relativePath = args.getAbsolutePath().replace(new File(ulteoIDPath).getAbsolutePath(), "");
				if (relativePath.startsWith("/"))
					relativePath = relativePath.replaceFirst("/", "");

				ovdApp.addOvdAppListener(this);
				ovdApp.sendStartApp(this.token, this.app.getId(), OvdAppChannel.DIR_TYPE_KNOWN_DRIVE, ulteoID, relativePath);
				this.state = STARTING;
				return;
			}
		}

		OVDRdpdrChannel rdpdr = this.app.getConnection().getRdpdrChannel();
		try {
			this.waitedDevice = rdpdr.getDeviceFromFile(arg);
		} catch (InvalidParameterException ex) {
			Logger.error("Unable to get a drive for '"+this.arg+"': "+ex.getMessage());
			this.waitedDevice = null;
			return;
		}

		if (this.waitedDevice == null) {
			if (this.app.getConnection().getDiskManager().getMountingMode() != DiskManager.ALL_MOUNTING_ALLOWED) {
				throw new RestrictedAccessException(I18n._("You did not have the permission to redirect this local drive"));
			}

			rdpdr.addDeviceListener(this);
			this.waitedDevice = rdpdr.mountDeviceFromFile(arg);
			if (this.waitedDevice == null) {
				Logger.error("Unable to mount a drive for: '"+this.arg+"'");
				rdpdr.removeDeviceListener(this);
				return;
			}
		}

		if (! this.parseArg()) {
			Logger.error("Failed to parse arg: '"+this.arg+"'");
			this.waitedDevice = null;
			if (rdpdr.getDeviceListeners().contains(this))
				rdpdr.removeDeviceListener(this);
			return;
		}

		if (! this.waitedDevice.connected) {
			Logger.info("Waiting for device mount: "+this.waitedDevice.handle);
			return;
		}

		

		ovdApp.addOvdAppListener(this);
		ovdApp.sendStartApp(this.token, this.app.getId(), ovdApp.DIR_TYPE_RDP_DRIVE, this.sharename, this.path);
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

	public void deviceConnected(RdpdrDevice device) {
		if (device != this.waitedDevice)
			return;

		((OVDRdpdrDisk) device).setInternalUsage(true);

		Logger.info("Device "+device.name+"("+device.handle+") connected");

		OvdAppChannel ovdApp = this.app.getConnection().getOvdAppChannel();

		ovdApp.addOvdAppListener(this);
		ovdApp.sendStartApp(this.token, this.app.getId(), ovdApp.DIR_TYPE_RDP_DRIVE, this.sharename, this.path);
		this.state = STARTING;

		this.app.getConnection().getRdpdrChannel().removeDeviceListener(this);
	}

	public void deviceFailed(RdpdrDevice device) {
		if (device != this.waitedDevice)
			return;

		Logger.error("Failed to connect to device "+device.name+"("+device.handle+"). Cannot start application '"+this.app.getName()+"'");

		this.app.getConnection().getRdpdrChannel().removeDeviceListener(this);
	}

	public void ovdInited(OvdAppChannel o) {}
	public void ovdInstanceStarted(int instance_) {
		if (instance_ != this.token)
			return;
		
		if (this.waitedDevice != null)
			this.app.getConnection().getOvdAppChannel().addShareUsedByApp(this.waitedDevice, this.token);
	}

	public void ovdInstanceStopped(int instance_) {
		if (instance_ != this.token)
			return;
		
		OvdAppChannel ovdApp = this.app.getConnection().getOvdAppChannel();

		ovdApp.removeOvdAppListener(this);
		
		if (this.waitedDevice != null) {
			ovdApp.removeShareUsedByApp(this.waitedDevice, this.token);
			if (! ovdApp.isShareUsed(this.waitedDevice) && this.waitedDevice.getInternalUsage())
				this.app.getConnection().getRdpdrChannel().unmountDrive(this.waitedDevice.get_name(), this.waitedDevice.get_local_path());
		}
	}

	public void ovdInstanceError(int instance_) {
		if (instance_ != this.token)
			return;

		OvdAppChannel ovdApp = this.app.getConnection().getOvdAppChannel();

		ovdApp.removeOvdAppListener(this);

		ovdApp.removeShareUsedByApp(this.waitedDevice, this.token);
		if (! ovdApp.isShareUsed(this.waitedDevice))
			this.app.getConnection().getRdpdrChannel().unmountDrive(this.waitedDevice.get_name(), this.waitedDevice.get_local_path());
	}
}
