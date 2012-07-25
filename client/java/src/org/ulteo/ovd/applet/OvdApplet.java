/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010, 2012
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Julien LANGLOIS <julien@ulteo.com> 2011
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.WebClientCommunication;
import org.ulteo.ovd.client.profile.ProfileProperties;
import org.ulteo.ovd.client.profile.ProfileWeb;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.printer.OVDStandalonePrinterThread;
import org.ulteo.ovd.sm.Properties;
import org.ulteo.ovd.sm.Protocol;
import org.ulteo.pcsc.PCSC;
import org.ulteo.rdp.rdpdr.OVDPrinter;
import org.ulteo.utils.AbstractFocusManager;
import org.ulteo.utils.LibraryLoader;
import org.ulteo.utils.jni.WorkArea;

import netscape.javascript.JSObject;

public abstract class OvdApplet extends Applet {
	
	protected boolean finished_init = false;
	protected boolean started_stop = false;

	protected AbstractFocusManager focusManager;
	protected OvdClient ovd = null;
	
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
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_WINDOWS, LibraryLoader.LIB_PCSC_WINDOWS);
				PCSC.libraryLoaded();
			} catch (FileNotFoundException ex) {
				org.ulteo.Logger.warn(ex.getMessage());
				PCSC.disableLibraryLoading();
			}
		}
		else if (OSTools.isLinux()) {
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_LINUX, LibraryLoader.LIB_X_CLIENT_AREA);
			} catch (FileNotFoundException ex) {
				Logger.error(ex.getMessage());
				WorkArea.disableLibraryLoading();
			}
			try {
				LibraryLoader.LoadLibrary(LibraryLoader.RESOURCE_LIBRARY_DIRECTORY_LINUX, LibraryLoader.LIB_PCSC_UNIX);
				PCSC.LoadPCSCLite();
				PCSC.libraryLoaded();
			} catch (FileNotFoundException ex) {
				org.ulteo.Logger.warn(ex.getMessage());
				PCSC.disableLibraryLoading();
			}
		}
	}

	protected abstract void _init() throws Exception;

	protected abstract void _start();
	
	protected abstract void _stop();

	protected abstract void _destroy();

	protected abstract int getMode();
	
	/**
	 * read personalize parameters
	 * @throws Exception
	 */
	protected abstract void readParameters() throws Exception;

	protected abstract OvdClient createOvdClient(Properties properties);
	
	
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
			String keymap = this.getParameterNonEmpty("keymap");
			String rdp_input_method = this.getParameter("rdp_input_method");
			String wc = this.getParameter("wc_url");
			
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
			
			// configure client
			this.ovd = createOvdClient(properties);
			this.ovd.setKeymap(keymap);
			if (rdp_input_method != null)
				this.ovd.setInputMethod(rdp_input_method);
			
			// load web profile
			WebClientCommunication webComm = new WebClientCommunication(wc);
			ProfileProperties pproperties = new ProfileWeb().loadProfile(webComm);
			if (pproperties != null) {
				this.ovd.setPacketCompression(pproperties.isUsePacketCompression());
				this.ovd.setOffscreenCache(pproperties.isUseOffscreenCache());
				this.ovd.setUseTLS(pproperties.isUseTLS());
				if (pproperties.isUsePersistantCache())
					this.ovd.setPersistentCaching(pproperties.getPersistentCacheMaxCells(), pproperties.getPersistentCachePath());
				if (pproperties.isUseBandwithLimitation()) {
					int diskBandwidthLimit = 0;
					if (pproperties.isUseDiskBandwithLimitation())
						diskBandwidthLimit = pproperties.getDiskBandwidthLimit();
					this.ovd.setBandWidthLimitation(pproperties.getSocketTimeout(), diskBandwidthLimit);
				}
				
				if (pproperties.isUseKeepAlive()) {
					int keepAliveInterval = 0;
					this.ovd.setUseKeepAlive(true);
					keepAliveInterval = pproperties.getKeepAliveInterval();
					this.ovd.setKeepAliveInterval(keepAliveInterval);
				}
			}

			_init();
			
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
			this.forwardServerStatusToJS(0, OvdApplet.JS_API_O_SERVER_FAILED);
		Logger.info(this.getClass().toString() +" stopped");
	}
	
	
	@Override
	public final void destroy() {
		this.ovd = null;
		this.focusManager = null;
		_destroy();
		System.gc();
	}
	
	public void forwardToJS(String functionName, Object[] args) {
		if (args == null)
			args = new Object[0];
		
		try {
			try {
				JSObject win = JSObject.getWindow(this);
				win.call(functionName, args);
			} catch (ClassCastException e) {
				// a cast exception is raised when the applet is executed by the 
				// appletViewer class (used by some IDEs) and with OpenJDK JVM. This will 
				// not execute the JS, so it not possible to run an OVD session
				throw new netscape.javascript.JSException(e.getMessage());
			}
		} catch (netscape.javascript.JSException e) {
			String argsStr = "";
			if (args.length > 0) {
				for (int i = 0; i < args.length; i++) {
					argsStr += args[i];
					
					if (i < args.length - 1)
						argsStr += ", ";
				}
			}
			
			Logger.error(String.format("%s: error while execute %s(%s) => %s",
					this.getClass(), functionName, argsStr, e.getMessage()));
		}
	}
	
	public void forwardServerStatusToJS(Integer serverId_, String status_) {
		Object[] args = {serverId_, status_};
		this.forwardToJS(JS_API_F_SERVER, args);
	}
	
	public void forwardApplicationStatusToJS(Integer app_id_, Integer token_, String status_) {
		Object[] args = {app_id_, token_, status_};
		this.forwardToJS(JS_API_F_INSTANCE, args);
	}
}
