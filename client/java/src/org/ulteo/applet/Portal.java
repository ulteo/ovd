/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
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


package org.ulteo.applet;

import java.net.URL;

import org.ulteo.Logger;
import org.ulteo.SshConnection;
import org.ulteo.Utils;

public class Portal extends java.applet.Applet {
	protected SshConnection ssh = null;

	// Ssh parameters
	protected String sshUser,sshPassword,sshHost;
	protected int sshPort;

	//	Proxy parameters
	protected String proxyType, proxyHost, proxyUsername, proxyPassword;
	protected int proxyPort;

	protected boolean continue2run = true;
	protected boolean stopped = false;

	private String startupStatusReport = null;
	private JSDialog dialog = null;


	// Begin extends Applet
	public void init() {
		System.out.println(this.getClass().toString() +"  init");

		this.dialog = new JSDialog(this);
		if (! this.dialog.init()) { 
			System.err.println("OvdTester: Unable to continue");
			this.continue2run = false;
			return;
		}

/*
		this.startupStatusReport = this.getParameter("onInit");
		if (this.startupStatusReport == null || this.startupStatusReport.equals("")) {
			System.err.println("OvdTester init: Missing parameter key 'onInit'");
			System.err.println("OvdTester: Unable to continue");
			this.continue2run = false;
			this.stop();
			return;
		}
*/

		boolean status = this.checkSecurity();
//		this.applet_startup_info(status);
		if (! status) {
			System.err.println(this.getClass().toString() +"  init: Not enought privileges, unable to continue");
			this.dialog.talk(JSDialog.ERROR_SECURITY);
			this.continue2run = false;
			this.stop();
			return;
		}


		if (! readParameters()) {
			this.dialog.talk(JSDialog.ERROR_USAGE);
			this.continue2run = false;
			this.stop();
			return;
		}

		try {
			this.ssh = new SshConnection(this.sshHost, this.sshPort, this.sshUser, this.sshPassword);
		}
		catch(NumberFormatException e) {
			System.err.println(this.getClass()+" invalid SSH password");
			this.dialog.talk(JSDialog.ERROR_USAGE);
			this.continue2run = false;
			this.stop();
			return;
		}
//		this.ssh.addEventHandler(new SshHandler(this));

		if(proxyHost != null && !proxyHost.equals("")) {
			System.out.println("Enable proxy parameters");
			this.ssh.setProxy(this.proxyType, this.proxyHost, this.proxyPort, this.proxyUsername, this.proxyPassword);
		}
	}


	public void start() {
		if (this.stopped || ! this.continue2run)
			return;	
		System.out.println(this.getClass().toString() +"  start");

		if (! this.ssh.connect()) {
			this.dialog.talk(JSDialog.ERROR_SSH);
			this.continue2run = false;
			this.stop();
			return;
		}
	}
   
	public void stop() {
		if (this.stopped)
			return;
		System.out.println("org.ulteo.applet.Applet stop");


		if (this.ssh != null)
			this.ssh.disconnect();
	}
   
	public void destroy() {
		System.out.println("org.ulteo.applet.Applet destroy");

		this.ssh = null;
		this.sshUser = null;
		this.sshPassword = null;
		this.sshHost = null;

		this.proxyType = null;
		this.proxyHost = null;
		this.proxyUsername = null;
		this.proxyPassword = null;
	}
	// end extends Applet


	public boolean checkSecurity() {
		try {
			System.getProperty("user.home");
		} catch(java.security.AccessControlException e) {
			return false;
		}

		return true;
	}

	public String getParameterNonEmpty(String key) throws Exception {
		String buffer = super.getParameter(key);
		if (buffer != null && buffer.equals("")) {
			System.err.println("Parameter "+key+": empty value");
			throw new Exception();
		}

		return buffer;
	}

	public String getParameter(String key, boolean required) throws Exception {
		String buffer = this.getParameterNonEmpty(key);

		if (required &&  buffer == null) {
			System.err.println("Missing parameter key '"+key+"'");
			throw new Exception();
		}

		return buffer;
	}


	public boolean readParameters() {
		// SSH parameters
		try {
			this.sshHost = this.getParameter("ssh.host", true);

			String[] buffer = this.getParameter("ssh.port", true).split(",");
			this.sshPort = Integer.parseInt(buffer[0]);
			// this.ssh_port = new int[buffer.length];
			// for(int i=0; i<buffer.length; i++)
			//	 this.ssh_port[i] = Integer.parseInt(buffer[i]);
		
			this.sshUser = this.getParameter("ssh.user", true);
			this.sshPassword = this.getParameter("ssh.password", true);
		}
		catch(NumberFormatException e) {
			System.err.println("Invalid ssh port number");
			return false;
		}
		catch(Exception e) {
			return false;
		}

		// Read proxy parameters, if any -- by ArnauVP
		try {
			
			this.proxyType = this.getParameterNonEmpty("proxyType");
			this.proxyHost = this.getParameterNonEmpty("proxyHost");
			String buffer = this.getParameterNonEmpty("proxyPort");
			if (buffer!=null)
				this.proxyPort = Integer.parseInt(buffer);
			this.proxyUsername = super.getParameter("proxyUsername");
			if (this.proxyUsername == null || this.proxyUsername.equals(""))
				this.proxyUsername = "dummy"; // even if the proxy is anonymous, the ssh applet needs a login and password
			this.proxyPassword = super.getParameter("proxyPassword");
			if (this.proxyPassword == null || this.proxyPassword.equals(""))
				this.proxyPassword = "dummy"; // even if the proxy is anonymous, the ssh applet needs a login and password
		} catch(NumberFormatException e) {
			System.err.println("Invalid proxyPort ("+this.getParameter("proxyPort")+")");
			return false;
		} catch(Exception e) {
			return false;
		}

		return true;
    }

	public void applet_startup_info(boolean status) {
		String url = "javascript:"+this.startupStatusReport+"("+(status?"true":"false")+");";
		System.out.println("org.ulteo.applet.Applet call javascript '"+url+"')");
		this.openUrl(url);
	}

	public void openUrl(String url) {
		System.out.println("Openurl: "+url);
		try {
			getAppletContext().showDocument(new URL(url));
		} catch(Exception e) {
			System.err.println("Couldn't execute javascript "+e.getMessage());
			stop();
		}
	}

}
