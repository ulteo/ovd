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


import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.lang.Thread;
import java.net.InetAddress;
import java.net.InetSocketAddress;
import java.net.Proxy;
import java.net.ProxySelector;
import java.net.URI;
import java.net.URL;
import java.net.UnknownHostException;
import java.util.Iterator;
import java.util.List;
import java.util.StringTokenizer;


import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.PasswordAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.transport.IgnoreHostKeyVerification;


public class OvdTester extends java.applet.Applet implements java.lang.Runnable {
    public String startuponLoad = "";
    public String startuponFailure = "";
    public String startuponBadPing = "";
	private String startupStatusReport = null;

	protected String js_haveProxy_function_name = "haveProxy";

	// Results
	public int testResult = 0;
	public long avgPing = -1;

	// System properties
	protected String operativeSystem;

	// Browser properties
	protected String userAgent;

	// Ssh parameters
	protected String sshUser,sshPassword,sshHost;
	protected int[] sshPortList;

	//	Proxy parameters
	protected boolean proxy = false;
	protected String proxyType,proxyHost,proxyUsername,proxyPassword;
	protected int proxyPort;

	protected int maxPingAccepted = 250;
	protected String urlProxyTest = "http://www.ulteo.com";

	private boolean can_run = false;

	protected String getNeededParameter(String key) {
		String buffer = getParameter(key);
		if (buffer == null && buffer.equals("")) {
			System.err.println("Missing parameter key '"+key+"'");
			stop();
		}

		return buffer;
	}

	public boolean readParameters() {
		// Get the Javascript URLs to show the results
		startuponLoad = getNeededParameter("onLoad");
		startuponFailure = getNeededParameter("onFail");
		startuponBadPing = getNeededParameter("onBadPing");

		userAgent = getNeededParameter("agent");

		try {
			this.sshHost = getNeededParameter("ssh.host");

			String[] buffer = getNeededParameter("ssh.port").split(",");
			this.sshPortList = new int[buffer.length];
			for(int i=0; i<buffer.length; i++)
				this.sshPortList[i] = Integer.parseInt(buffer[i]);
		}
		catch(NumberFormatException e) {
			System.err.println("Invalid ssh port number");
			return false;
		}
		catch(Exception e) {
			return false;
		}

		String buffer = getParameter("maxPingAccepted");
		if (buffer != null) {
			try {
				maxPingAccepted = Integer.parseInt(buffer);
			} catch (NumberFormatException e) {}
		}

		return true;
	}

	public boolean checkSecurity() {
		try {
			System.getProperty("user.home");
		} catch(java.security.AccessControlException e) {
			return false;
		}

		return true;
	}

    public void init() {
		System.out.println("OvdTester init");

		this.startupStatusReport = this.getParameter("onInit");
		if (this.startupStatusReport == null || this.startupStatusReport.equals("")) {
			System.err.println("OvdTester init: Missing parameter key 'onInit'");
			System.err.println("OvdTester: Unable to continue");
			return;
		}

		boolean status = this.checkSecurity();
		this.applet_startup_info(status);

		if (! status) {
			System.err.println("OvdTester init: Not enought privileges, unable to continue");
			return;
		}

		this.can_run = true;
		System.out.println("OvdTester init continue");

		this.readParameters();

		operativeSystem = System.getProperty("os.name");

	}

	public void start() {
		if (! this.can_run)
			return;

		System.out.println("OvdTester start");

		if (! jvmIsSupported()) {
			testResult = -1;
			stop();
			return;
		}

		if (! browserIsSupported()) {
			testResult = -2;
			System.err.println("You're using a browser currently unsupported by Ulteo Open Virtual Desktop," +
							   "or which doesn't have the capabilities to run it. Please try another one");
			System.err.println("\n***TEST FAILED***\n");
			stop();
			return;
		}

		Thread t = new Thread(this);
		t.start();
	}

	public void stop() {
		if (! this.can_run)
			return;

		System.out.println("OvdTester stop");
		testFinished();

		super.stop();
	}

	public void run(){
		// we try to detect proxy
		detectProxy();

		testPing();

		if (! testSSH()) {
			testResult = -3;
			System.err.println("\n***TEST FAILED***\n");
			System.err.println("You will probably not be able to run Ulteo Open Virtual Desktop in the best conditions");
		}

		stop();
	}


	public void applet_startup_info(boolean status) {
		String url = "javascript:"+this.startupStatusReport+"("+(status?"true":"false")+");";
		System.out.println("OvdTester call javascript '"+url+"')");
		openUrl(url);
	}


	/**
	 * testFinished()
	 * According to the value of testResult, call one of the three
	 * URLs from the parameters: urlOnPass, urlOnFail, urlLowPing
	 */
	public void testFinished() {
		//1. if fails --> red
		//2. if high ping --> yellow
		//3. else --> green
		System.out.println("Test result: "+testResult);
		String methodName = null;
		Object[] methodArgs = null;

		if (testResult < 0){
			testResult = -testResult;
			methodName = startuponFailure;
			methodArgs = new Object[1];
			methodArgs[0] = ""+testResult;
			openUrl("javascript:"+startuponFailure+"("+testResult+")");
		} else if (avgPing > maxPingAccepted){
			methodName = startuponBadPing;
			openUrl("javascript:"+startuponBadPing);
		} else {
			methodName = startuponLoad;
			openUrl("javascript:"+startuponLoad);
		}
	}


	public void testPing() {
		if (operativeSystem.toLowerCase().contains("windows")) {
			if (!operativeSystem.toLowerCase().contains("vista")) {
				//avgPing = pingTestWindows(ulteoapplet.getHostToPing(), 4);
			}
			if(avgPing <= 0){
				// No permission to run "ping", or ICMP traffic blocked, maybe?
				// We do a *very* rough approximation
				// TODO: when we're working on global statistics, don't collect this kind of entries (not precise enough)
				//avgPing = pingTestUnix(ulteoapplet.getHostToPing(), 4) - 1000;
			}
		}else{
			//avgPing = pingTestUnix(ulteoapplet.getHostToPing(), 4);
		}
	}



	/**
	 * Check JVM version: accept only Sun/Apple JVM version 1.5 or higher.
	 *
	 */
	public boolean jvmIsSupported(){
		String javaVersion = System.getProperty("java.version");
		String javaVendor = System.getProperty("java.vendor");

		System.out.println("You're using Java version "+javaVersion+" from "+javaVendor + " on " + operativeSystem);
		float jVersion = 1.0f;
		try{
			jVersion = Float.parseFloat(javaVersion.substring(0, 3));
		}catch(NumberFormatException ex){
			System.err.println("Error parsing java version: "+javaVersion);
			return false;
		}
		if( jVersion <= 1.4){
			System.err.println("Please, update your Java Virtual Machine");
			return false;
		}

		if(!(javaVendor.startsWith("Sun Microsystems") || javaVendor.startsWith("Apple"))){
			System.err.println("Please get Java JRE from Sun or Apple in order to run OD");
			return false;
		}

		return true;
	}


	/**
	 * Make a 'ping' to the echo port of a certain connectme server and give the result back.
	 * The Unix version uses isReachable() java function, which in fact executes a real ping.
	 * @return the average value of a ping to the given connectme server
	 */
	public long pingTestUnix(String server, int averages){
		long pingResult = -1;
		long time1, time2, dif, cumulate = 0;
		try {
			InetAddress address = InetAddress.getByName(server);

			for (int j=0; j<averages; j++){
				time1 = System.currentTimeMillis();
				address.isReachable(3000);
				time2 = System.currentTimeMillis();
				dif = time2 - time1;
//				System.out.println("Ping #"+j+": "+dif+" ms");
				cumulate += dif;
				Thread.sleep(300);
			}
			pingResult = (cumulate/averages);
		} catch (UnknownHostException uhe) {
			System.err.println("Unknown host, ping not possible");
//			e.printStackTrace();
		} catch (IOException ioe) {
			System.err.println("Network exception, ping not possible");
//			e.printStackTrace();
		} catch (InterruptedException inte) {
			System.err.println("ouch, ping not possible");
//			e.printStackTrace();
		}
		System.out.println("Ping result: "+pingResult);
		return pingResult;
	}


	/**
	 * Make a 'ping' to the echo port of a certain connectme server and give the result back.
	 * The Windows version uses Runtime.exec() to call the "ping" program directly, because isReachable() takes too long.
	 * @return the average value of a ping to the given connectme server
	 */

	public long pingTestWindows(String server, int averages){
		long pingResult = -1;
		long cumulate = 0;
		try {
			Process pinger = Runtime.getRuntime().exec("ping "+server+" -n "+averages);
			BufferedReader reader = new BufferedReader(new InputStreamReader(pinger.getInputStream()));
			String lineRead = null, tmpString;
			int start = 0, end = 0, tmp = 0;
			while((lineRead = reader.readLine()) != null){
				if(lineRead.contains("TTL=")){
					StringTokenizer st = new StringTokenizer(lineRead, " ");
					for(int i=0; i<4; i++){
						if(st.nextToken().equals(":")){
							st.nextToken();
						}
					}
					tmpString = st.nextToken();
//					System.out.println("-->" + tmpString);
					start = tmpString.indexOf("=");
					if (start > 0) {
						end = tmpString.indexOf("ms", start);
						if (end < 0) {
							/*French ping separes "ms" from the number,
							so we don't get it in the StringTokenizer*/
							end = tmpString.length();
						}
						tmp = Integer.parseInt(tmpString.substring(start + 1, end));
//						System.out.println("Result of ping: "+tmp);
						cumulate += tmp;
					}
				}
			}
			pingResult = (cumulate/averages);
		} catch (Exception e) {
			System.err.println("ouch, ping not possible");
		}
		System.out.println("Average ping result: "+pingResult);
		return pingResult;
	}

	/**
	 * Accepted browsers:
	 * IE6, IE7,
	 * Firefox,
	 * Opera,
	 * Safari Mac;
	 *
	 * Problematic browsers:
	 * Camino,
	 * Firefox Mac,
	 * Konqueror,
	 * Safari windows;
	 *
	 * @return true if supported, false otherwise
	 *
	 */
	public boolean browserIsSupported() {
		System.out.println("User agent: "+userAgent);
		//boolean kjasSM = false; // Konqueror's Security Manager not working well

		if(System.getSecurityManager() != null &&
		   System.getSecurityManager().toString().startsWith("org.kde.kjas"))
			// Konqueror's Security Manager not working well
			return false;


		if (userAgent.contains("Camino"))
			return false;


		// We are not supported Firefox on Mac OS
		if (userAgent.contains("Firefox") &&
			userAgent.contains("Mac OS X"))
			return false;

		// We are not supported Safari on Windows
		if(userAgent.contains("Safari") &&
		   userAgent.contains("Windows"))
		   return false;

		return true;
	}


	/**
	 * Open
	 * @return true if successful, false otherwise
	 */
	public boolean testSSH() {
		int MAX_ITERATIONS = 5;		

		for (int iterations=0; iterations<MAX_ITERATIONS; iterations++) {
			for (int i=0; i<this.sshPortList.length; i++) {
				int port = this.sshPortList[i];
				System.out.println("Trying to open connection through port "+port+" ("+(iterations+1)+"/"+MAX_ITERATIONS+")");
				if (this.testSSH(port))
					return true;

				try {
					Thread.sleep(100);
				} catch (InterruptedException e) {}
			}
			
			try {
				Thread.sleep(2500);
			} catch (InterruptedException e) {}
		}
		return false;
	}


	protected boolean testSSH(int port) {
		SshClient ssh = new SshClient();
		ssh.setSocketTimeout(20000);

		// Create SSH properties
		SshConnectionProperties properties = new SshConnectionProperties();
		properties.setHost(this.sshHost);
		properties.setPort(port);

		if (proxy) {
			properties.setTransportProviderString(proxyType);
			properties.setPort(443); //Always use this when using proxy
			properties.setProxyHost(proxyHost);
			properties.setProxyPort(proxyPort);
			properties.setProxyUsername(proxyUsername);
			properties.setProxyPassword(proxyPassword);
		}

		try {
			ssh.connect(properties, new IgnoreHostKeyVerification());
		} catch (UnknownHostException e) {
			return false;
		} catch (IOException  e) {
			return false;
		}

		PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
		pwd.setUsername("dummy");
		pwd.setPassword("dummy");

		try {
			ssh.authenticate(pwd);
			// We know it failed, but we hope it didn't produce any exception.
		} catch (IOException e) {
			return false;
		}

		ssh.disconnect();
		return true;
	}


	private void detectProxy() {
		List<Proxy> l;

		try {
			System.setProperty("java.net.useSystemProxies", "true");
			l = ProxySelector.getDefault().select(new URI(this.urlProxyTest));
		} catch (Exception e) {
			e.printStackTrace();
			return;
		}

		Iterator<Proxy> iter = l.iterator();
		if (! iter.hasNext())
			// No proxy
			return;

		Proxy proxy = iter.next();
		InetSocketAddress addr = (InetSocketAddress) proxy.address();
		if(addr == null)
			// No proxy
			return;

		this.proxy = true;
		this.proxyType = proxy.type().toString();
		this.proxyHost = new String(addr.getHostName());
		this.proxyPort = addr.getPort();
		this.proxyUsername = "";
		this.proxyPassword = "";


		String buffer = "javascript:" + js_haveProxy_function_name +
			"('" + this.proxyType + "', '" +
			this.proxyHost + "', '" +
			this.proxyPort + "', '" +
			this.proxyUsername + "', '" +
			this.proxyPassword + "');";
		System.out.println("JS command: "+buffer);

		this.openUrl(buffer);
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
