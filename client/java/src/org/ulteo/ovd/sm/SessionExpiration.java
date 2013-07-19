/*
 * Copyright (C) 2013 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2013
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
package org.ulteo.ovd.sm;

import java.util.Timer;



public class SessionExpiration {
	private final int POPUP_TIME = 10;  // time when the popup is displayed in minute
	private static SessionExpiration instance = null;
	private Timer expirationTimer = null;
	private int lastExpirationTime = -1;
	private boolean displayed = false;
	private boolean activated = true;
	
	
	private SessionExpiration() {
		this.reset();
	}
	
	
	public static SessionExpiration getInstance() {
		if (instance == null)
			instance = new SessionExpiration();
		
		return instance;
	}
	
	
	public void reset() {
		this.abort();
		this.displayed = false;
	}
	
	
	public void disable() {
		this.activated = false;
	}
	
	public void activate() {
		this.activated = true;
	}
	
	
	public void abort() {
		if (! this.activated)
			return;
		
		if (this.expirationTimer == null)
			return;
		
		this.expirationTimer.cancel();
		this.expirationTimer.purge();
		this.expirationTimer = null;
		this.lastExpirationTime = -1;
	}
	
	
	public void arm(int millis) {
		if (! this.activated)
			return;
		
		this.expirationTimer = new Timer();
		this.expirationTimer.schedule(new SessionExpirationPopup(this), millis);
	}
	
	
	public boolean isSet() {
		return this.expirationTimer == null;
	}
	
	
	public void setDisplayed() {
		this.displayed = true;
	}
	
	
	public int getExpirationTime() {
		return this.lastExpirationTime;
	}
	
	
	public void setExpiration(int minute) {
		if ((! this.activated) || this.displayed)
			return;
		
		if (minute == -1) {
			if (this.expirationTimer != null)
				this.abort();
			
			return;
		}
		
		int popup_time = Math.abs(minute - POPUP_TIME) * 1000 * 60;
		if (minute - POPUP_TIME < 0) {
			this.arm(1000);
		}
		
		if ((minute > 0) && (this.expirationTimer == null)) {
			this.arm(popup_time);
		}
		
		if (this.lastExpirationTime == -1)
			this.lastExpirationTime = minute;
		
		// Check if there is great change between last expiration and current
		if (Math.abs(minute - this.lastExpirationTime) > 10) {
			// We reset the timer
			this.abort();
			this.arm(popup_time);
		}
		
		this.lastExpirationTime = minute;
	}
}
