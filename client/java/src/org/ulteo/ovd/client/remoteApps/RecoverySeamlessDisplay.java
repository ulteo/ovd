/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
 * Author Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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

import java.awt.Rectangle;
import java.util.Timer;
import java.util.TimerTask;

import net.propero.rdp.Input;
import net.propero.rdp.RdesktopCanvas;
import net.propero.rdp.rdp5.seamless.SeamlessWindow;

import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.rdp.seamless.SeamlessFrame;

import net.propero.rdp.RdpListener;
import net.propero.rdp.RdpConnection;

import org.ulteo.rdp.OvdAppListener;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.ovd.ApplicationInstance;
import org.ulteo.ovd.OvdException;

public class RecoverySeamlessDisplay extends TimerTask implements RdpListener, OvdAppListener {
	private SeamlessFrame f;
	private RdpConnectionOvd co;

	public RecoverySeamlessDisplay(RdpConnectionOvd co) {
		this.co = co;

		/* RdpConnection listener */
		co.addRdpListener(this);

		/* OvdApp channel listener */
		try {
			co.addOvdAppListener(this);
		} catch(OvdException e) {
			this.destroy();
		}
	}

	public void run() {
		RdesktopCanvas canvas = this.co.getCanvas();
		Rectangle res = new Rectangle(canvas.width, canvas.height);
		Input input = canvas.getInput();

		this.f = new SeamlessFrame(0, 0 , res, 0, this.co.getCommon());
		this.f.sw_setMyPosition(0, 0, canvas.width, canvas.height);
		this.f.sw_setExtendedState(SeamlessWindow.STATE_FULLSCREEN);
		this.f.setVisible(true);
		this.f.addMouseListener(input.getMouseAdapter());
	}

	public void destroy() {
		this.co.removeRdpListener(this);

		try {
			co.removeOvdAppListener(this);
		} catch(OvdException e) {}

		this.cancel();

		if (f != null) {
			this.f.sw_destroy();
			this.f = null;
		}
	}

	/* RdpListener interface */

	public void connected(RdpConnection co) {
		Timer timer = new Timer();
		timer.schedule(this, 3 * 1000);
	}

	public void disconnected(RdpConnection co) {
		this.destroy();
	}

	public void failed(RdpConnection co, String msg) {
		this.destroy();
	}

	public void connecting(RdpConnection co) {}
	public void seamlessEnabled(RdpConnection co) {}

	/* OvdAppListener interface */

	public void ovdInited(OvdAppChannel o) {
		this.destroy();
	}

	public void ovdInstanceStarted(OvdAppChannel channel_, ApplicationInstance appInst_) {}
	public void ovdInstanceStopped(ApplicationInstance appInst_) {}
	public void ovdInstanceError(ApplicationInstance appInst_) {}
}

