/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import java.applet.Applet;
import java.io.FileNotFoundException;

import org.ulteo.Logger;
import org.ulteo.ovd.client.ClientInfos;
import org.ulteo.ovd.client.Options;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.WebClientCommunication;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.profile.ProfileWeb;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.Protocol;
import org.ulteo.rdp.RdpConnectionOvd;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.utils.AbstractFocusManager;
import org.ulteo.utils.jni.WorkArea;

import netscape.javascript.JSObject;

public abstract class OvdApplet extends Applet {
	
	protected int port = 0;
	protected String server = null;
	protected String keymap = null;
	protected String rdp_input_method = null;

	protected boolean finished_init = false;
	protected boolean started_stop = false;

	protected AbstractFocusManager focusManager;
	protected OvdClient ovd = null;
	protected Options opts;
	protected String wc = null;
	
	public static final String JS_API_F_SERVER = "serverStatus";
	public static final String JS_API_O_SERVER_CONNECTED = "connected";
	public static final String JS_API_O_SERVER_DISCONNECTED = "disconnected";
	public static final String JS_API_O_SERVER_FAILED = "failed";
	public static final String JS_API_O_SERVER_READY = "ready";

	public static final String JS_API_F_INSTANCE = "applicationStatus";
	public static final String JS_API_O_INSTANCE_STARTED = "started";
	public static final String JS_API_O_INSTANCE_STOPPED = "stopped";
	public static final String JS_API_O_INSTANCE_ERROR = "error";
	
	public static String tempdir;
	
	static {
		OSTools.is_applet = true;
		
		tempdir = System.getProperty("java.io.tmpdir");
		if (! tempdir.endsWith(System.getProperty("file.separator")))
			tempdir+= System.getProperty("file.separator");
		
		if (! Logger.initInstance(true, tempdir+"ulteo-ovd-"+Logger.getDate()+".log", true)) {
			System.err.println(Applications.class.toString() + " Unable to iniatialize logger instance");
		}
		
		if (OSTools.isWindows()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_WINDOWS, LibraryLoader.LIB_WINDOW_PATH_NAME);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
			}
		}
		else if (OSTools.isLinux()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_LINUX, LibraryLoader.LIB_X_CLIENT_AREA);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
				WorkArea.disableLibraryLoading();
			}
		}
	}

	protected abstract void _init(Properties properties) throws Exception;

	protected abstract void _start();
	
	protected abstract void _stop();

	protected abstract void _destroy();

	protected abstract int getMode();
	
	/**
	 * read personalize parameters
	 * @throws Exception
	 */
	protected abstract void readParameters() throws Exception;

	
	/**
	 * get a parameter give to the applet
	 * @param key
	 * 		key of the parameter
	 * @return
	 * 		parameter expected
	 * @throws Exception 
	 */
	public String getParameterNonEmpty(String key) throws Exception {
		String param = super.getParameter(key);
		if (param == null || param.isEmpty())
			throw new Exception(String.format("Invalid parameter '%s'", key));
		return param;
	}
	
	
	@Override
	public final void init() {
		Logger.info(this.getClass().toString() + " init");
		System.out.println("//--");
		ClientInfos.showClientInfos();
		System.out.println("--//");

		try {
			System.getProperty("user.home");
			
			this.readParameters();
			this.keymap = this.getParameterNonEmpty("keymap");
			this.rdp_input_method = this.getParameter("rdp_input_method");
			
			Properties properties = new Properties(getMode());
			for (String setting : Protocol.settingsNames) {
				String value = this.getParameter("setting_"+setting);
				if (value != null)
					Protocol.parseSessionSettings(properties, setting, value);		
			}
			
			if (properties.isPrinters()){
				OVDStandalonePrinterThread appletPrinterThread = new OVDStandalonePrinterThread();
				OVDPrinter.setPrinterThread(appletPrinterThread);
				this.focusManager = new AppletFocusManager(appletPrinterThread);
			}
			
			this.opts = new Options();
			WebClientCommunication webComm = new WebClientCommunication(this.wc);
			
			if (!this.getWebProfile(webComm))
				System.out.println("Unable to get webProfile");
			
			_init(properties);
			this.finished_init = true;
		}
		catch (Exception e) {
			Logger.error(this.getClass().toString() + ": " + e.getMessage());
			this.stop();
		}
		finally {
			Logger.info(this.getClass().toString() + " inited");
		}
	}
	
	
	@Override
	public final void start() {	
		if (! this.finished_init || this.started_stop)
			return;	
		
		Logger.info(this.getClass().toString() +" start");
		_start();
		Logger.info(this.getClass().toString() +" started");
	}
	
	
	@Override
	public final void stop() {
		if (this.started_stop)
			return;

		Logger.info(this.getClass().toString() +" stopping");
		this.started_stop = true;
		
		_stop();

		if (this.ovd != null)
			this.ovd.performDisconnectAll();
		else
			this.forwardJS(OvdApplet.JS_API_F_SERVER, 0, OvdApplet.JS_API_O_SERVER_FAILED);
		Logger.info(this.getClass().toString() +" stopped");
	}
	
	
	@Override
	public final void destroy() {
		this.ovd = null;
		this.server = null;
		this.keymap = null;
		this.focusManager = null;
		_destroy();
		System.gc();
	}
	

	@SuppressWarnings("deprecation")
	public void forwardJS(String functionName, Integer instance, String status) {
		try {
			try {
				JSObject win = JSObject.getWindow(this);
				Object[] args = {instance, status};
				win.call(functionName, args);
			} catch (ClassCastException e) {
				// a cast exception is raised when the applet is executed by the 
				// appletViewer class (used by some IDEs) and with OpenJDK JVM. This will 
				// not execute the JS, so it not possible to run an OVD session
				throw new netscape.javascript.JSException(e.getMessage());
			}
		} catch (netscape.javascript.JSException e) {
			Logger.error(String.format("%s: error while execute %s(%d, %s) => %s",
					this.getClass(), functionName, instance, status, e.getMessage()));
		}
	}
	
	public boolean getWebProfile(WebClientCommunication wcc) {
		ProfileWeb webProfile = new ProfileWeb();
		ProfileProperties properties;
		properties = webProfile.loadProfile(wcc);
		
		if (properties == null)
			return false;
		
		this.opts.parseProperties(properties);
		
		return true;
	}
	
	public void applyConfig(RdpConnectionOvd c) {
		if (this.opts.usePacketCompression) {
			c.setPacketCompression(this.opts.usePacketCompression);
		}

		if (this.opts.useOffscreenCache) {
			c.setUseOffscreenCache(this.opts.useOffscreenCache);
		}

		if (this.opts.usePersistantCache) {
			c.setPersistentCaching(this.opts.usePersistantCache);
			
			c.setPersistentCachingPath(this.opts.persistentCachePath);
			c.setPersistentCachingMaxSize(this.opts.persistentCacheMaxCells);
		}
		
		if (this.opts.useBandwithLimitation) {
			c.setUseBandWidthLimitation(true);
			c.setSocketTimeout(this.opts.socketTimeout);
			if (this.opts.useDiskBandwithLimitation) {
				c.getRdpdrChannel().setSpoolable(true, this.opts.diskBandwidthLimit);
			}
		}
	}
}
