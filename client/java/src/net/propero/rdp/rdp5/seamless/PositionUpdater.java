/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package net.propero.rdp.rdp5.seamless;

import java.awt.Component;
import java.util.Timer;
import java.util.TimerTask;
import java.util.concurrent.ConcurrentHashMap;

public class PositionUpdater extends TimerTask {
	private static final ConcurrentHashMap<SeamlessWindow, Timer> positionsUpdates = new ConcurrentHashMap<SeamlessWindow, Timer>();

	private SeamlessChannel seamchan = null;
	private SeamlessWindow sw = null;

	public PositionUpdater(SeamlessChannel channel_, SeamlessWindow sw_) {
		this.seamchan = channel_;
		this.sw = sw_;
	}

	public void update() {
		if (this.sw == null)
			return;

		Timer timer = new Timer();
		synchronized(PositionUpdater.positionsUpdates) {
			if (PositionUpdater.positionsUpdates.containsKey(this.sw)) {
				Timer old_updater = PositionUpdater.positionsUpdates.get(this.sw);
				if (old_updater != null)
					old_updater.cancel();
			}
			PositionUpdater.positionsUpdates.put(this.sw, timer);
		}
		timer.schedule(this, 100);
	}

	@Override
	public void run() {
		if (this.sw == null)
			return;

		synchronized(PositionUpdater.positionsUpdates) {
			PositionUpdater.positionsUpdates.remove(this.sw);
		}

		if (this.seamchan == null)
			return;

		this.seamchan.updatePosition(this.sw);
	}
}
