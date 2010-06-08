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

import java.util.Locale;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpConnection;

import org.ulteo.ovd.printer.OVDPrinterManager;
import org.ulteo.rdp.seamless.SeamlessChannel;

public class RdpConnectionVDI extends RdpConnection {
	
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
	 *	- Clip channel
	 */
	public RdpConnectionVDI() throws VdiException {
		
		super(new Options(), new Common());

		this.opt.bitmap_compression = true;

		String language = System.getProperty("user.language");
		String country = System.getProperty("user.country");
		this.mapFile =  new Locale(language, country).toString().toLowerCase();
		this.mapFile = this.mapFile.replace('_', '-');

		try {
			this.initSeamlessChannel();
		} catch (RdesktopException e) {
			e.printStackTrace();
		}
	}
	
	protected void initSeamlessChannel() throws RdesktopException {
		this.opt.seamlessEnabled = true;
		if (this.seamChannel != null)
			return;

		this.seamChannel = new SeamlessChannel(this.opt, this.common);
		if (! this.addChannel(this.seamChannel))
			throw new RdesktopException("Unable to add seamless channel");
	}
	
	/**
	 * Register all secondary channels requested. They could be:
	 *	- sound channel
	 *	- rdpdr channel
	 * @throws RdesktopException 
	 * @throws VdiException 
	 */
	public void initSecondaryChannels() throws RdesktopException, VdiException {
		this.initClipChannel();
		
		if ((this.flags & MODE_MULTIMEDIA) != 0) {
			this.initSoundChannel();
		}
		if ((this.flags & MOUNT_PRINTERS) != 0) {
			OVDPrinterManager printerManager = new OVDPrinterManager();
			printerManager.searchAllPrinter();
			if (printerManager.hasPrinter()) {
				this.initRdpdrChannel();
				System.out.println("Rdpdr channel added");
				printerManager.registerAll(this.rdpdrChannel);
			}
			else
				throw new VdiException("Have to map local printers but no printer found ....");
		}
	}
}
