/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
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

package org.ulteo.rdp;

import java.util.ArrayList;
import java.util.Locale;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.OvdException;
import org.ulteo.ovd.disk.LinuxDiskManager;
import org.ulteo.ovd.disk.WindowsDiskManager;
import org.ulteo.ovd.disk.DiskManager;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDPrinterManager;
import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;
import org.ulteo.rdp.seamless.SeamlessChannel;
import java.net.InetAddress;
import java.net.UnknownHostException;
import org.ulteo.Logger;
import org.ulteo.rdp.TCPSSLSocketFactory;
import org.ulteo.utils.LayoutDetector;

public class RdpConnectionOvd extends RdpConnection {

	public static final int MODE_DESKTOP =		0x00000001;
	public static final int MODE_APPLICATION =	0x00000002;
	public static final int MODE_MULTIMEDIA =	0x00000004;
	public static final int MOUNT_PRINTERS =	0x00000008;

	/* Flags value between 0x00000010 and 0x000000f0 are reserved for drives modes mounting*/
	public static final int MOUNTING_MODE_FULL =	0x00000010;
	public static final int MOUNTING_MODE_PARTIAL =	0x00000020;
	public static final int MOUNTING_MODE_MASK =	0x000000F0;

	/* Flags value between 0x10000000 and 0xf0000000 are reserved for debug*/
	public static final int DEBUG_SEAMLESS =	0x10000000;
	public static final int DEBUG_MASK =		0xf0000000;

	private int flags = 0x00;
	private ArrayList<Application> appsList = null;
	private OvdAppChannel ovdAppChannel = null;
	private static DiskManager diskManager = null;
	private static OVDPrinterManager printerManager = null;
	
	/**
	 * Instanciate a new RdpConnectionOvd with default options:
	 *	- bitmap compression
	 *	- volatile bitmap caching
	 *	- persistent bitmap caching
	 *	- 24 bits
	 *	- Clip channel
	 */
	public RdpConnectionOvd(int flags_) throws OvdException, RdesktopException {
		super(new Options(), new Common());

		this.flags = flags_;

		if ((this.flags & MODE_DESKTOP) != 0 && (this.flags & MODE_APPLICATION) != 0)
			throw new OvdException("Unable to create connection: Desktop and Application modes can't work together");

		if ((this.flags & DEBUG_MASK) == DEBUG_SEAMLESS)
			this.opt.debug_seamless = true;

		this.opt.bitmap_compression = true;
		this.setVolatileCaching(true);
		this.setPersistentCaching(false);

		if ((this.flags & MODE_DESKTOP) != 0) {
			this.setDesktopMode();
		}
		else if ((this.flags & MODE_APPLICATION) != 0) {
			this.setApplicationMode();
		}
		else {
			throw new OvdException("Unable to create connection: Neither desktop nor application mode specified");
		}
		
		this.appsList = new ArrayList<Application>();
		this.mapFile = LayoutDetector.get();
		this.detectKeymap();
	}
	
	@Override
	public String toString() {
		if (this.opt.socketFactory != null && this.opt.socketFactory instanceof TCPSSLSocketFactory) {
			TCPSSLSocketFactory s = (TCPSSLSocketFactory)this.opt.socketFactory;
			return s.getHost()+":"+s.getPort();
		}
		
		return super.toString();
	}

	/**
	 * Return the connection flags
	 * @return int flags
	 */
	public int getFlags() {
		return this.flags;
	}

	/**
	 * Register all secondary channels requested. They could be:
	 *	- sound channel
	 *	- rdpdr channel
	 * @throws OvdException
	 */
	public void initSecondaryChannels() throws OvdException, RdesktopException {
		this.initClipChannel();

		if ((this.flags & MODE_MULTIMEDIA) != 0) {
			this.setMultimediaMode();
		}
		if ((this.flags & MOUNTING_MODE_MASK) != 0) {
			this.mountLocalDrive();
		}
		if ((this.flags & MOUNT_PRINTERS) != 0) {
			this.mountLocalPrinters();
		}
	}

	@Override
	protected void initSeamlessChannel() throws RdesktopException {
		this.opt.seamlessEnabled = true;
		this.seamChannel = new SeamlessChannel(this.opt, this.common);
		if (! this.addChannel(this.seamChannel))
			throw new RdesktopException("Unable to add seamless channel");
	}

	protected void initOvdAppChannel() throws OvdException {
		this.ovdAppChannel = new OvdAppChannel(this.opt, this.common);
		if (! this.addChannel(this.ovdAppChannel))
			throw new OvdException("Unable to add ovdapp channel");
	}

	/**
	 * Enable OVD desktop mode
	 */
	private void setDesktopMode() {
		this.opt.seamlessEnabled = false;
	}

	public void setSeamlessEnabled(boolean value) {
		this.opt.seamlessEnabled = value;
	}

	/**
	 * Enable OVD applications mode
	 *	- Init seamless channel
	 *	- Add OvdApp channel
	 */
	private void setApplicationMode() throws OvdException, RdesktopException {
		this.initSeamlessChannel();

		this.initOvdAppChannel();
	}

	/**
	 * Enable OVD multimedia mode
	 *	- Add sound channel
	 */
	private void setMultimediaMode() throws OvdException, RdesktopException {
		this.initSoundChannel();
		System.out.println("Sound channel added");
	}

	/**
	 * Init rdpdr channel
	 *	- Add device redirection channel
	 */
	@Override
	protected void initRdpdrChannel() throws RdesktopException {
		if (this.rdpdrChannel != null)
			return;
		this.rdpdrChannel = new OVDRdpdrChannel(this.opt, this.common);
		if (! this.addChannel(this.rdpdrChannel))
			throw new RdesktopException("Unable to add rdpdr channel");
	}
	
	/**
	 * process the disconnected step
	 *	- stop the disk timer task
	 */
	@Override
	protected void fireDisconnected() {
		super.fireDisconnected();

		if (RdpConnectionOvd.diskManager != null) {
			RdpConnectionOvd.diskManager.stop();
			RdpConnectionOvd.diskManager = null;
		}
		
		if (RdpConnectionOvd.printerManager != null) {
			RdpConnectionOvd.printerManager.stop();
			RdpConnectionOvd.printerManager = null;
		}
		
	}
	
	/**
	 * Mount local printers
	 *	- Add rdpdr channel
	 *	- Use a PrinterManager instance in order to register all local printers
	 */
	private void mountLocalPrinters() throws OvdException, RdesktopException {
		this.initRdpdrChannel();
		if (RdpConnectionOvd.printerManager == null) {
			RdpConnectionOvd.printerManager = new OVDPrinterManager();
			RdpConnectionOvd.printerManager.launch();
		}
		RdpConnectionOvd.printerManager.register_connection(this.rdpdrChannel);
	}

	/**
	 * Mount local drive
	 *	- Add rdpdr channel
	 *	- Use a diskmanager instance in order to register all local disks
	 */
	private void mountLocalDrive() throws OvdException, RdesktopException {
		this.initRdpdrChannel();

		boolean mountingMode = DiskManager.MOUNTING_RESTRICTED;
		int mountingModeFlag = this.flags & MOUNTING_MODE_MASK;
		switch (mountingModeFlag) {
			case MOUNTING_MODE_FULL:
				mountingMode = DiskManager.ALL_MOUNTING_ALLOWED;
				break;
			case MOUNTING_MODE_PARTIAL:
				break;
			default:
				Logger.error("mountLocalDrives: Unknown mounting mode flag "+String.format("0x%08x", mountingModeFlag));
				return;
		}

		if (OSTools.isWindows()) {
			diskManager = new WindowsDiskManager((OVDRdpdrChannel)rdpdrChannel, mountingMode);
		}
		else {
			diskManager = new LinuxDiskManager((OVDRdpdrChannel)rdpdrChannel, mountingMode);
		}
		diskManager.launch();		
	}
	
	@Override
	public void setPersistentCaching(boolean persistentCaching) {
		super.setPersistentCaching(persistentCaching);

		String separator = System.getProperty("file.separator");
		String cacheDir = System.getProperty("user.home")+separator+
			((System.getProperty("os.name").startsWith("Windows")) ? "Application Data"+separator : ".")+
			"ulteo"+separator+"ovd"+separator+"cache"+separator;
		this.setPersistentCachingPath(cacheDir);
	}

	public void setUseBandWidthLimitation(boolean value) {
		this.opt.useBandwithLimitation = value;
	}

	public void setSocketTimeout(int timetout) {
		this.opt.socketTimeout = timetout;
	}
	
	protected void detectKeymap() {
		String language = System.getProperty("user.language");
		String country = System.getProperty("user.country");

		this.mapFile =  new Locale(language, country).toString().toLowerCase();
		this.mapFile = this.mapFile.replace('_', '-');
	}

	@Override
	public void stop() {
		super.stop();

		if (RdpConnectionOvd.diskManager != null) {
			RdpConnectionOvd.diskManager.stop();
			RdpConnectionOvd.diskManager = null;
		}
		if (RdpConnectionOvd.printerManager != null) {
			RdpConnectionOvd.printerManager.stop();
			RdpConnectionOvd.printerManager = null;
		}
	}

	public void addApp(Application app_) {
		this.appsList.add(app_);
	}

	public ArrayList<Application> getAppsList() {
		return this.appsList;
	}

	/**
	 * Return the current OvdAppChannel instance
	 * @return OvdAppChannel instance
	 */
	public OvdAppChannel getOvdAppChannel() {
		return this.ovdAppChannel;
	}

	/**
	 * Return the current OVDRdpdrChannel instance
	 * @return OVDRdpdrChannel instance
	 */
	public OVDRdpdrChannel getRdpdrChannel() {
		return (OVDRdpdrChannel) this.rdpdrChannel;
	}

	/**
	 * Return the current DiskManager instance
	 * @return DiskManager instance
	 */
	public DiskManager getDiskManager() {
		return this.diskManager;
	}

	public void sendLogoff() throws OvdException {
		if (this.ovdAppChannel == null)
			throw new OvdException("Unable to send logoff: OvdAppChannel does not exist");
		if (! this.ovdAppChannel.isReady())
			throw new OvdException("Unable to send logoff: OvdAppChannel is not initialized");

		this.ovdAppChannel.sendLogoff();
	}

	/**
	 * Register an OvdAppListener
	 * @param listener
	 * @throws OvdException
	 */
	public void addOvdAppListener(OvdAppListener listener) throws OvdException {
		if (this.ovdAppChannel == null)
			throw new OvdException("Could not add an OvdAppListener: OvdAppChannel does not exist");
		this.ovdAppChannel.addOvdAppListener(listener);
	}

	/**
	 * Unregister an OvdAppListener
	 * @param listener
	 * @throws OvdException
	 */
	public void removeOvdAppListener(OvdAppListener listener) throws OvdException {
		if (this.ovdAppChannel == null)
			throw new OvdException("Could not remove an OvdAppListener: OvdAppChannel does not exist");
		this.ovdAppChannel.removeOvdAppListener(listener);
	}

	public void useSSLWrapper(String host, int port) throws OvdException, UnknownHostException {
		InetAddress hostv = null;

		try {
			hostv = InetAddress.getByName(host);
		} catch(Exception e) {
			throw new OvdException("Could not convert String fqdn to InetAdress host : " + e.getMessage());
		}

		this.opt.port = port;

		try {
			this.opt.socketFactory = new TCPSSLSocketFactory(hostv, port);
		} catch (Exception e2) {
			throw new OvdException("Could not create TCPSSLSocketFactory : " + e2.getMessage());
		}
	}
	public void setUseOffscreenCache(boolean value) {
		this.opt.supportOffscreen = value;
	}
	
}
