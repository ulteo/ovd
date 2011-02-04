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

import org.ulteo.rdp.rdpdr.OVDRdpdrChannel;
import org.ulteo.rdp.seamless.SeamlessChannel;


public class RdpConnectionVDI extends RdpConnection {

	private DiskManager diskManager;

	public RdpConnectionVDI() throws VdiException {

		super(new Options(), new Common());

		String language = System.getProperty("user.language");
		String country = System.getProperty("user.country");
		this.mapFile =  new Locale(language, country).toString().toLowerCase();
		this.mapFile = this.mapFile.replace('_', '-');
		this.opt.bitmap_compression = true;

		try {
			this.initSeamlessChannel();
			this.initSoundChannel();
			this.initRdpdrChannel();
			this.initClipChannel();
			
			diskManager = new DiskManager(this.rdpdrChannel);
			diskManager.launch();
		} catch (RdesktopException e) {
			e.printStackTrace();
		}
	}

	protected void initSeamlessChannel() throws RdesktopException {
		this.opt.seamlessEnabled = true;
		if (this.seamChannel != null)
			return;

		this.seamChannel = new SeamlessChannel(this.opt, this.common);
		if (this.addChannel(this.seamChannel))
			this.seamChannel.addSeamListener(this);
		else
			throw new RdesktopException("Unable to add seamless channel");
	}

	protected void initRdpdrChannel() throws RdesktopException {
		if (this.rdpdrChannel != null)
			return;
		this.rdpdrChannel = new OVDRdpdrChannel(this.opt, this.common);
		if (! this.addChannel(this.rdpdrChannel))
			throw new RdesktopException("Unable to add rdpdr channel");
	}

	protected void fireDisconnected() {
		super.fireDisconnected();
		diskManager.stop();
	}
	
}
