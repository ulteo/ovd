/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2013
 * Author Julen LANGLOIS <julien@ulteo.com> 2013
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

class RecoverySeamlessDisplay extends TimerTask {
	private SeamlessFrame f;
	private RdpConnectionOvd co;
	
	public RecoverySeamlessDisplay(RdpConnectionOvd co) {
		this.co = co;
		
		Timer timer = new Timer();
		timer.schedule(this, 3 * 1000);
	}
	
	public void destroy() {
		this.cancel();
		
		if (f == null)
			return;
    	
		this.f.sw_destroy();
		this.f = null;
	}
	
	public void run() {
		RdesktopCanvas canvas = this.co.getCanvas();
		Rectangle res = new Rectangle(canvas.width, canvas.height);
		Input input = canvas.getInput();
		
		this.f = new SeamlessFrame(0, 0 , res, 0, this.co.getCommon());
		this.f.sw_setExtendedState(SeamlessWindow.STATE_FULLSCREEN);
		this.f.addMouseListener(input.getMouseAdapter());
	}
}

