/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author David LECHEVALIER <david@ulteo.com> 2009
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

import java.awt.BorderLayout;
import java.awt.KeyboardFocusManager;
import java.io.PrintWriter;
import java.io.StringWriter;
import java.io.Writer;
import java.lang.Thread.UncaughtExceptionHandler;
import java.net.URL;
import java.util.Collections;

import com.sshtools.j2ssh.SshErrorResolver;
import com.sshtools.j2ssh.SshDialog;
import com.sshtools.j2ssh.forwarding.ForwardingIOChannel;
import com.sshtools.j2ssh.transport.TransportProtocol;
import com.sshtools.j2ssh.transport.TransportProtocolEventHandler;

import org.ulteo.Logger;
import org.ulteo.SshConnection;
import org.ulteo.Utils;

import org.vnc.RfbProto;
import org.vnc.VncClient;
import org.vnc.rfbcaching.IRfbCachingConstants;

public class Standalone extends java.applet.Applet implements SshErrorResolver, UncaughtExceptionHandler, org.vnc.Dialog {
	protected VncClient vnc = null;
	protected SshConnection ssh = null;

	// Ssh parameters
	protected String sshUser,sshPassword,sshHost;
	protected int sshPort;

	//	Proxy parameters
	protected boolean proxy = false;
	protected String proxyType,proxyHost,proxyUsername,proxyPassword;
	protected int proxyPort;

	// VNC parameters
	protected String vncPassword = null;
	protected int vncPort;

	protected boolean continue2run = true;
	protected boolean stopped = false;

	private String startupStatusReport = null;
	protected JSDialog dialog = null;

	public boolean checkSecurity() {
		try {
			System.getProperty("user.home");
		} catch(java.security.AccessControlException e) {
			return false;
		}

		return true;
	}


	// Begin extends Applet
	public void init() {
		System.out.println(this.getClass().toString() +" init");

		this.dialog = new JSDialog(this);
		if (! this.dialog.init()) { 
			System.err.println("OvdTester: Unable to continue");
			this.continue2run = false;
			return;
		}
	
		boolean status = this.checkSecurity();
		if (! status) {
			System.err.println(this.getClass().toString() +" init: Not enought privileges, unable to continue");
			this.dialog.talk(JSDialog.ERROR_SECURITY);
			this.continue2run = false;
			this.stop();
			return;
		}

		Runtime rt = Runtime.getRuntime();
		if(rt.totalMemory() == rt.maxMemory() && rt.freeMemory() < 11000000){
			this.dialog.talk(JSDialog.ERROR_MEMORY);
			this.continue2run = false;
			this.stop();
			return;
		}

		if (! this.readParameters()) {
			// Call JS method
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
		//this.ssh.addEventHandler(new SshHandler(this));

		if(proxyHost != null && !proxyHost.equals("")) {
			System.out.println("Enable proxy parameters");
			this.ssh.setProxy(this.proxyType, this.proxyHost, this.proxyPort, this.proxyUsername, this.proxyPassword);
		}

		this.vnc = new VncClient(this, this);

		Thread.setDefaultUncaughtExceptionHandler(this);
		SshDialog.registerResolver(this);
		BorderLayout layout = new BorderLayout();
		this.setLayout(layout);
		
		// Change the focus traversal keys behavior to not forward the tab key (\t) to the browser and lost te focus
		KeyboardFocusManager.getCurrentKeyboardFocusManager().setDefaultFocusTraversalKeys (KeyboardFocusManager.FORWARD_TRAVERSAL_KEYS, Collections.EMPTY_SET);
		KeyboardFocusManager.getCurrentKeyboardFocusManager().setDefaultFocusTraversalKeys (KeyboardFocusManager.BACKWARD_TRAVERSAL_KEYS, Collections.EMPTY_SET);
    }


	public void start() {
		System.out.println(this.getClass().toString() +" start");

		if (this.stopped || ! this.continue2run)
			return;
		System.out.println("org.ulteo.applet.Viewer start");


		if (! this.ssh.connect()) {
			this.dialog.talk(JSDialog.ERROR_SSH);
			this.continue2run = false;
			this.stop();
			return;
		}


		ForwardingIOChannel tunnel = this.ssh.createTunnel(this.vncPort);
		if (tunnel == null) {
			this.dialog.talk(JSDialog.ERROR_SSH);
			System.err.println("Unable to create tunnel");
			this.stop();
			return;
		}
		
		this.vnc.setInOut(tunnel.getInputStream(), tunnel.getOutputStream());
	
		if (! this.vnc.connect()) {
			this.dialog.talk(JSDialog.ERROR_VNC);
			this.stop();
			return;
		}

		if (! this.vnc.authenticate()) {
			this.dialog.talk(JSDialog.ERROR_VNC);
			this.stop();
			return;
		}

		if (! vnc.init()) {
			this.dialog.talk(JSDialog.ERROR_VNC);
			this.stop();
			return;
		}

		this.vnc.start_background_process();
		System.out.println("Session started");
	}
   

	public void stop() {
		if (this.stopped)
			return;

		System.out.println(this.getClass().toString() +" stop");

		if (this.vnc != null)
			this.vnc.stop();
		if (this.ssh != null)
			this.ssh.disconnect();
	}
   

	public void destroy() {
		System.out.println(this.getClass().toString() +" destroy");

		this.ssh = null;
		this.sshUser = null;
		this.sshPassword = null;
		this.sshHost = null;

		this.proxyType = null;
		this.proxyHost = null;
		this.proxyUsername = null;
		this.proxyPassword = null;

		this.vnc = null;
		this.vncPassword = null;
	}
	// end extends Applet
	

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
			if (this.proxyUsername == null || !this.proxyUsername.equals(""))
				this.proxyUsername = "dummy"; // even if the proxy is anonymous, the ssh applet needs a login and password
			this.proxyPassword = super.getParameter("proxyPassword");
			if (this.proxyPassword == null || !this.proxyPassword.equals(""))
				this.proxyPassword = "dummy"; // even if the proxy is anonymous, the ssh applet needs a login and password
		} catch(NumberFormatException e) {
			System.err.println("Invalid proxyPort ("+this.getParameter("proxyPort")+")");
			return false;
		} catch(Exception e) {
			return false;
		}


		// VNC parameters
		try {
			// org.vnc.Options.host = this.getParameter("HOST", true);
			this.vncPort = Integer.parseInt(this.getParameter("PORT", true));
			this.vncPassword = this.getParameter("ENCPASSWORD", true);

		}
		catch(NumberFormatException e) {
			System.err.println("Invalid vncPort");
			return false;
		} catch(Exception e) {
			System.err.println("No VNC port or password");
			return false;
		}

		// Extended VNC parameters
		try {
			String buf;

			org.vnc.Options.preferredEncoding = RfbProto.EncodingTight;
			org.vnc.Options.shareDesktop = true;

			buf = this.getParameterNonEmpty("JPEG image quality");
			if (buf != null) {
				try {
					org.vnc.Options.jpegQuality = Integer.parseInt(buf);
				} catch(NumberFormatException e) {
					System.err.println("invalid JPEG image quality");
					return false;
				}
			}

			buf = this.getParameterNonEmpty("Compression level");
			if (buf != null) {
				try {
					org.vnc.Options.compressLevel = Integer.parseInt(buf);
				} catch(NumberFormatException e) {
					System.err.println("Invalid Compression level");
					return false;
				}
			}

			buf = this.getParameterNonEmpty("Restricted colors");
			if (buf != null && buf.equalsIgnoreCase("yes"))
				org.vnc.Options.eightBitColors = true;

			buf = this.getParameterNonEmpty("View only");
			if (buf != null && buf.equalsIgnoreCase("yes"))
				org.vnc.Options.viewOnly = true;
		}
		catch(Exception e) {
			return false;
		}

		if (this.getParameter("rfb.cache.enabled") != null) {
			org.vnc.Options.cacheEnable = true;

			try {
				String buf = this.getParameterNonEmpty("rfb.cache.ver.major");
				if (buf != null)
					org.vnc.Options.cacheVerMajor = Integer.parseInt(buf);

				buf = this.getParameterNonEmpty("rfb.cache.ver.minor");
				if (buf != null)
					org.vnc.Options.cacheVerMinor = Integer.parseInt(buf);

				buf = this.getParameterNonEmpty("rfb.cache.size");
				if (buf != null)
					org.vnc.Options.cacheSize = Integer.parseInt(buf);

				buf = this.getParameterNonEmpty("rfb.cache.datasize");
				if (buf != null)
					org.vnc.Options.cacheDataSize = Integer.parseInt(buf);

				buf = this.getParameterNonEmpty("rfb.cache.alg");
				if (buf.equalsIgnoreCase("FIFO"))
					org.vnc.Options.cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_MAINT_ALG_FIFO;
				else if (buf.equalsIgnoreCase("LRU"))
					org.vnc.Options.cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_MAINT_ALG_LRU;
				else {
					System.err.println("Unknown rfb.cach.alg");
					return false;
				}
			}
			catch(NumberFormatException e) {
				System.err.println("Invalid rfb.cache parameters");
				return false;
			}
			catch(Exception e) {
				System.err.println("Empty forced rfb.cache parameters");
				return false;
			}
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


	// Begin Implements SshErrorResolver
	public void resolvError(String error) {
		Logger.warn("Unresolved error : "+error);
	}


	public void logError(String errorMessage) {
		Logger.error(errorMessage);
	}
	// End Implements SshErrorResolver


	// Begin Implements UncaughtExceptionHandler
	public void uncaughtException(Thread arg0, Throwable arg1) {
		Writer result = new StringWriter();
		PrintWriter printWriter = new PrintWriter(result);
		arg1.printStackTrace(printWriter);

		logError("An uncaught Exception is arrived: \n"+result.toString());
		
	}
	// End Implements UncaughtExceptionHandler


	// Begin Implements org.vnc.Dialog
	public String vncGetPassword() {
		try {
			return Utils.DecryptEncVNCString(this.vncPassword);
		}
		catch(NumberFormatException e) {
			System.err.println(this.getClass()+" invalid SSH password");
			this.dialog.talk(JSDialog.ERROR_USAGE);
			this.continue2run = false;
		}

		return null;
	}
	
	public void vncSetError(String err) {
		System.err.println("Vnc error: " + err);
		stop();
	}
	// End Implements org.vnc.Dialog
}
