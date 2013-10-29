/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
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

package org.ulteo.ovd.client;

/**
 * interface used for all class that needed to use the 'perform' action in the OvdClient
 * class
 */
public interface OvdClientPerformer extends Runnable {
	
	/**
	 * create all RDP connections needed by the OVD Client 
	 */
	void createRDPConnections();

	/**
	 * check if all RDP connections are correctly connected
	 * @return
	 */
	boolean checkRDPConnections();

	/**
	 * create all WebApps connections needed by the OVD Client 
	 */
	void createWebAppsConnections();

	/**
	 * check if all WebApps connections are correctly connected
	 * @return
	 */
	boolean checkWebAppsConnections();

	/**
	 * run this method when session is ready
	 */
	void runSessionReady();
	
	public void perform();
	
	public void run();
}
