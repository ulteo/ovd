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

package org.ulteo.vdi;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;
import net.propero.rdp.rdp5.rdpdr.RdpdrChannel;
import net.propero.rdp.rdp5.rdpsnd.SoundChannel;
import org.ulteo.ovd.printer.OVDPrinterManager;
import org.ulteo.rdp.seamless.SeamlessChannel;

public class RdpConnectionVDI extends RdpConnection {
	public static final int DEFAULT_BPP = 24;

	public static final byte MODE_DESKTOP = 0x01;
	public static final byte MODE_APPLICATION = 0x02;
	public static final byte MODE_MULTIMEDIA = 0x04;
	public static final byte MOUNT_PRINTERS = 0x08;

	private byte flags = 0x00;

	/**
	 * Instanciate a new RdpConnectionVDI with default options:
	 *	- bitmap compression
	 *	- volatile bitmap caching
	 *	- persistent bitmap caching
	 *	- 24 bits
	 *	- Clip channel
	 */
	public RdpConnectionVDI(byte flags_) throws RdesktopException {
		super(new Options(), new Common());

		this.flags = flags_;

		if ((this.flags & MODE_DESKTOP) != 0 && (this.flags & MODE_APPLICATION) != 0)
			throw new RdesktopException("Unable to create connection: Desktop and Application modes can't work together");

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
			throw new RdesktopException("Unable to create connection: Neither desktop nor application mode specified");
		}
	}

	/**
	 * Register all secondary channels requested. They could be:
	 *	- sound channel
	 *	- rdpdr channel
	 * @throws RdesktopException
	 */
	public void initSecondaryChannels() throws RdesktopException {
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
	protected void initClipChannel() throws RdesktopException {
		ClipChannel clipChannel = new ClipChannel(this.common, this.opt);
		if (! this.addChannel(clipChannel))
			throw new RdesktopException("Unable to add clip channel");
		if (this.seamChannel != null)
			this.seamChannel.setClip(clipChannel);
	}

	/**
	 * Enable desktop mode
	 */
	private void setDesktopMode() {
		this.opt.seamlessEnabled = false;
	}

	/**
	 * Enable applications mode
	 *	- Add seamless channel
	 */
	private void setApplicationMode() throws RdesktopException {
		this.opt.command = "seamlessrdpshell";
		this.opt.seamlessEnabled = true;
		this.seamChannel = new SeamlessChannel(this.opt, this.common);
		if (! this.addChannel(this.seamChannel))
			throw new RdesktopException("Unable to add seamless channel");
	}

	/**
	 * Enable multimedia mode
	 *	- Add sound channel
	 */
	private void setMultimediaMode() throws RdesktopException {
		SoundChannel sndChannel = new SoundChannel(this.opt, this.common);
		if (! this.addChannel(sndChannel))
			throw new RdesktopException("Unable to add sound channel, continue anyway");
		System.out.println("Sound channel added");
	}

	/**
	 * Mount local printers
	 *	- Add rdpdr channel
	 *	- Use a PrinterManager instance in order to register all local printers
	 */
	private void mountLocalPrinters() throws RdesktopException {
		OVDPrinterManager printerManager = new OVDPrinterManager();
		printerManager.searchAllPrinter();
		if (printerManager.hasPrinter()) {
			RdpdrChannel rdpdrChannel = new RdpdrChannel(this.opt, this.common);
			printerManager.registerAll(rdpdrChannel);

			if (! this.addChannel(rdpdrChannel))
				throw new RdesktopException("Unable to add rdpdr channel, continue anyway");
			System.out.println("Rdpdr channel added");
		}
		else
			throw new RdesktopException("Have to map local printers but no printer found ....");
	}
}
