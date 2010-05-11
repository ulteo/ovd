/*
 * Copyright (C) 2010 Ulteo SAS
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

package org.ulteo.rdp;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;
import net.propero.rdp.rdp5.rdpdr.PrinterManager;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpsnd.SoundChannel;
import org.ulteo.ovd.OvdException;
import org.ulteo.rdp.seamless.SeamlessChannel;

public class RdpConnectionOvd extends RdpConnection {
	public static final int DEFAULT_BPP = 24;

	public static final byte MODE_DESKTOP = 0x01;
	public static final byte MODE_APPLICATION = 0x02;
	public static final byte MODE_MULTIMEDIA = 0x04;
	public static final byte MOUNT_PRINTERS = 0x08;

	private byte flags = 0x00;
	private OvdAppChannel ovdAppChannel = null;
	private boolean ovdAppChannelInitialized = false;
	private Thread connectionThread = null;

	/**
	 * Set the host to connect on default port
	 * @param address
	 *	The RDP server address
	 */
	public void setServer(String address) {
		this.setServer(address, RDP_PORT);
	}

	/**
	 * Set the host and the port to connect
	 * @param host
	 *	The RDP server address
	 * @param port
	 *	The port to use
	 */
	public void setServer(String host, int port) {
		this.opt.hostname = host;
		this.opt.port = port;
	}

	/**
	 * Set credentials
	 * @param username
	 * @param password
	 */
	public void setCredentials(String username, String password) {
		this.opt.username = username;
		this.opt.password = password;
	}

	/**
	 * Set informations about display
	 * The default bpp is 24 bits
	 * @param width
	 * @param height
	 */
	public void setGraphic(int width, int height) {
		this.setGraphic(width, height, DEFAULT_BPP);
	}

	/**
	 * Set informations about display
	 * @param width
	 * @param height
	 * @param bpp
	 */
	public void setGraphic(int width, int height, int bpp) {
		this.opt.width = width;
		this.opt.height = height;
		this.opt.set_bpp(bpp);
	}

	/**
	 * Return the current OvdAppChannel instance
	 * @return OvdAppChannel instance
	 */
	public OvdAppChannel getOvdAppChannel() {
		return this.ovdAppChannel;
	}

	/**
	 * Instanciate a new RdpConnectionOvd with default options:
	 *	- bitmap compression
	 *	- volatile bitmap caching
	 *	- persistent bitmap caching
	 *	- 24 bits
	 *	- Clip channel
	 */
	public RdpConnectionOvd(byte flags_) throws OvdException {
		super(new Options(), new Common());

		this.flags = flags_;

		if ((this.flags & MODE_DESKTOP) != 0 && (this.flags & MODE_APPLICATION) != 0)
			throw new OvdException("Unable to create connection: Desktop and Application modes can't work together");

		this.opt.bitmap_compression = true;
		this.setVolatileCaching(true);
		this.setPersistentCaching(false);
		this.opt.width = 0;
		this.opt.height = 0;
		this.opt.set_bpp(DEFAULT_BPP);

		if ((this.flags & MODE_DESKTOP) != 0) {
			this.setDesktopMode();
		}
		else if ((this.flags & MODE_APPLICATION) != 0) {
			this.setApplicationMode();
		}
		else {
			throw new OvdException("Unable to create connection: Neither desktop nor application mode specified");
		}
	}

	/**
	 * Register all secondary channels requested. They could be:
	 *	- sound channel
	 *	- rdpdr channel
	 * @throws OvdException
	 */
	public void initSecondaryChannels() throws OvdException {
		this.initClipChannel();
		
		if ((this.flags & MODE_MULTIMEDIA) != 0) {
			this.setMultimediaMode();
		}
		if ((this.flags & MOUNT_PRINTERS) != 0) {
			this.mountLocalPrinters();
		}
	}

	/**
	 * Add clip channel
	 */
	private void initClipChannel() throws OvdException {
		ClipChannel clipChannel = new ClipChannel(this.common, this.opt);
		if (! this.addChannel(clipChannel))
			throw new OvdException("Unable to add clip channel");
		if (this.seamChannel != null)
			this.seamChannel.setClip(clipChannel);
	}

	/**
	 * Enable OVD desktop mode
	 */
	private void setDesktopMode() {
		this.opt.seamlessEnabled = false;
	}

	/**
	 * Enable OVD applications mode
	 *	- Add seamless channel
	 *	- Add OvdApp channel
	 */
	private void setApplicationMode() throws OvdException {
		this.opt.seamlessEnabled = true;
		this.seamChannel = new SeamlessChannel(this.opt, this.common);
		if (! this.addChannel(this.seamChannel))
			throw new OvdException("Unable to add seamless channel");

		this.ovdAppChannel = new OvdAppChannel(this.opt, this.common);
		if (! this.addChannel(this.ovdAppChannel))
			throw new OvdException("Unable to add ovdapp channel");
	}

	/**
	 * Enable OVD multimedia mode
	 *	- Add sound channel
	 */
	private void setMultimediaMode() throws OvdException {
		SoundChannel sndChannel = new SoundChannel(this.opt, this.common);
		if (! this.addChannel(sndChannel))
			throw new OvdException("Unable to add sound channel, continue anyway");
		System.out.println("Sound channel added");
	}

	/**
	 * Mount local printers
	 *	- Add rdpdr channel
	 *	- Use a PrinterManager instance in order to register all local printers
	 */
	private void mountLocalPrinters() throws OvdException {
		PrinterManager printerManager = new PrinterManager();
		printerManager.searchAllPrinter();
		if (printerManager.hasPrinter()) {
			RdpdrChannel rdpdrChannel = new RdpdrChannel(this.opt, this.common);
			printerManager.registerAll(rdpdrChannel);

			if (! this.addChannel(rdpdrChannel))
				throw new OvdException("Unable to add rdpdr channel, continue anyway");
			System.out.println("Rdpdr channel added");
		}
		else
			throw new OvdException("Have to map local printers but no printer found ....");
	}

	/**
	 * Enable/disable volatile bitmap caching
	 * @param volatileCaching
	 */
	public void setVolatileCaching(boolean volatileCaching) {
		if ((! volatileCaching) && this.opt.persistent_bitmap_caching)
			this.setPersistentCaching(false);
		this.opt.bitmap_caching = volatileCaching;
	}

	/**
	 * Enable/disable persistent bitmap caching
	 * @param persistentCaching
	 */
	public void setPersistentCaching(boolean persistentCaching) {
		if (persistentCaching && (! this.opt.bitmap_caching))
			this.setVolatileCaching(true);
		this.opt.persistent_bitmap_caching = persistentCaching;
	}

	/**
	 * Not implemented yet
	 * Specify the path where the persistent bitmap cache is
	 * @param persistentCachingPath
	 */
	public void setPersistentCachingPath(String persistentCachingPath) {
		throw new UnsupportedOperationException();
	}

	/**
	 * Not implemented yet
	 * Specify the maximum size of persistent bitmap cache
	 * @param persistentCachingMaxSize
	 */
	public void setPersistentCachingMaxSize(int persistentCachingMaxSize) {
		throw new UnsupportedOperationException();
	}

	/**
	 * Specify the path where keymaps are
	 * @param keymapPath
	 */
	public void setKeymapPath(String keymapPath_) {
		this.keyMapPath = keymapPath_;
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

	/**
	 * Launch a RdpConnection thread
	 */
	public void connect() throws OvdException {
		this.connectionThread = new Thread(this);
		this.connectionThread.start();
	}

	/**
	 * Interrupt the thread launched by the connect() method
	 */
	public void interruptConnection() throws OvdException {
		if (this.connectionThread == null)
			throw new OvdException("Unable to interrupt the connection: The connection thread is not started");

		if (this.connectionThread.isAlive())
			this.connectionThread.interrupt();
	}

	public boolean isOvdAppChannelInitialized() {
		return this.ovdAppChannelInitialized;
	}

	public void setOvdAppChannelInitialized(boolean isInitialized) {
		this.ovdAppChannelInitialized = isInitialized;
	}
}
