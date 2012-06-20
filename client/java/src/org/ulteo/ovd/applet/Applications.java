/*
 * Copyright (C) 2009-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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

package org.ulteo.ovd.applet;

import java.util.ArrayList;

import org.ulteo.ovd.sm.Properties;

import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.rdp.OvdAppChannel;
import org.ulteo.rdp.seamless.SeamlessFrame;
import org.ulteo.rdp.seamless.SeamlessPopup;


class FileApp {
	int type;
	String path;
	String share;

	FileApp(String f_type, String f_path, String f_share) {
		if (f_type.equalsIgnoreCase("http"))
			type = OvdAppChannel.DIR_TYPE_HTTP_URL;
		else
			type = OvdAppChannel.DIR_TYPE_SHARED_FOLDER;
		
		path = f_path;
		share = f_share;
	}
	
	public String toString() {
		return String.format("file(type: %d, path: %s, share: %s)", this.type, this.path, this.share);
	}
}


public class Applications extends OvdApplet {
	
	private SpoolOrder spooler;

	
	@Override
	protected void _init(Properties properties) {
		if (properties.isPrinters()) {
			SeamlessFrame.focusManager = focusManager;
			SeamlessPopup.focusManager = focusManager;
		}

		this.ovd = new OvdClientApplicationsApplet(properties, this);
		this.ovd.setKeymap(this.keymap);
		if (this.rdp_input_method != null)
			this.ovd.setInputMethod(this.rdp_input_method);
		
		this.spooler = new SpoolOrder((OvdClientApplicationsApplet) this.ovd);
	}
	
	@Override
	protected void _start() {	
		this.ovd.sessionReady();;
		this.spooler.start();
	}
	
	@Override
	protected void _stop() {
		if (this.spooler.isAlive())
			this.spooler.interrupt();
	}
	
	@Override
	protected void _destroy() {
		this.spooler = null;
	}

	@Override
	protected int getMode() {
		return Properties.MODE_REMOTEAPPS;
	}
	
	@Override
	protected void readParameters() throws Exception {
		this.wc = this.getParameter("wc_url");
	}
	
	// ********
	// Methods called by Javascript
	// ********
	
	public boolean serverConnect(int id, String host, int port, String login, String password) {
		this.spooler.add(new OrderServer(id, host, port, null, login, password));
		return true;
	}
	
	public boolean serverConnect(int id, String host, int port, String token, String login, String password) {
		this.spooler.add(new OrderServer(id, host, port, token, login, password));
		return true;
	}
	
	public void startApplication(int token, int app_id, int server_id) {
		this.spooler.add(new OrderApplication(token, app_id, new Integer(server_id), null));
	}
	
	public void startApplicationWithFile(int token, int app_id, int server_id, String f_type, String f_path, String f_share) {
		FileApp f = new FileApp(f_type, f_path, f_share);
		this.spooler.add(new OrderApplication(token, app_id, new Integer(server_id), f));
	}
}
