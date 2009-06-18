package org.ulteo;


import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.InetAddress;
import java.net.InetSocketAddress;
import java.net.Proxy;
import java.net.ProxySelector;
import java.net.URI;
import java.net.UnknownHostException;
import java.util.Iterator;
import java.util.List;
import java.util.StringTokenizer;
import java.applet.Applet;


import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.PasswordAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.forwarding.ForwardingIOChannel;
import com.sshtools.j2ssh.transport.ConsoleKnownHostsKeyVerification;


public class OvdTester extends Applet {

	 public static final String version = "0.2.4";


	 // Results
	 int testResult = -1;
	 long avgPing = -1;

	 // Java properties
	 String javaVendor;
	 String javaVersion;

	 // System properties
	 String operativeSystem;
	 String OSVersion;

	 // Browser properties
	 String browser;
	 String browserVersion;
	 String userAgent;

	 // Connection properties
	 String passwordParam;
	 InputStream in;
	 OutputStream out;
	 SshClient ssh;
	 int sshPort, port;
	 String portList;
	 String sshUser,sshPassword,sshHost;
	 ForwardingIOChannel channel;
	 String afterLoad,afterSSH, afterConnected, sshError;
	 //	Proxy parameters
	 String proxyType,proxyHost,proxyUsername,proxyPassword;
	 int proxyPort;



    

    public void init(){
	 System.out.println("Starting UlteoVNC version "+version);

	 String tmp;
	 tmp = getParameter("preLoad");
	 if(tmp != null && tmp.equalsIgnoreCase("true")){

		 //startupPreload = tmp.equalsIgnoreCase("true");
		 tmp = null;

		 // Get the Javascript URLs to show the results
		 //tmp = readParameter("onLoad");
		 if(tmp != null){
			//startuponLoad = tmp;
			 tmp = null;
		 }
		 //tmp = readParameter("onFail");
		 if(tmp != null){
			 //startuponFailure = tmp;
			 tmp = null;
		 }
		 //tmp = readParameter("onBadPing");
		 if(tmp != null){
			// startuponBadPing = tmp;
			 tmp = null;
		 }


    }
}

	public void run(){
		// first of all, we try to detect proxy
		String proxy_param[] = DetectProxy();
		if ( proxy_param != null ){
			if (proxy_param.length == 5){
			// param : proxyType,proxyHost,proxyPort,proxyUsername,proxyPassword
				try {
					//this.ulteoapplet.openUrl("javascript:"+"haveProxy"+"('"+proxy_param[0]+"','"+ proxy_param[1]+"','"+proxy_param[2]+"','"+proxy_param[3]+"','"+proxy_param[4]+"')");
					proxyType = new String(proxy_param[0]);
					proxyHost = new String(proxy_param[1]);
					if ((proxy_param[2]).length() != 0) {
						proxyPort = (new Integer(proxy_param[2])).intValue();
					}
					else {
						proxyPort = -1;
					}
					proxyUsername = new String(proxy_param[3]);
					proxyPassword = new String(proxy_param[4]);
				} catch (Exception e) {
					e.printStackTrace();
					proxyType = new String("");
					proxyHost = new String("");
					proxyPort = -1;
					proxyUsername = new String("");
					proxyPassword = new String("");
				}
			}
			else {
				proxyType = new String(proxy_param[0]);
				proxyHost = new String(proxy_param[1]);
				if ((proxy_param[2]).length() != 0) {
					proxyPort = (new Integer(proxy_param[2])).intValue();
				}
				else {
					proxyPort = -1;
				}
				proxyUsername = new String(proxy_param[3]);
				proxyPassword = new String(proxy_param[4]);
			}
		}
		else {
			proxyType = new String("");
			proxyHost = new String("");
			proxyPort = -1;
			proxyUsername = new String("");
			proxyPassword = new String("");
		}
		testResult = startTest();
		//ulteoapplet.testFinished(testResult, avgPing);
	}

	public int startTest(){
		int result = -1;
		readParameters();

		result = javaTest();

		if(result < 0){
			System.err.println("\n***TEST FAILED***\n");
			System.err.println("You will probably not be able to run OnlineDesktop in the best conditions");
//			JOptionPane.showMessageDialog(null, "Your Java Virtual Machine is not supported\nYou will probably not be able to run OnlineDesktop in the best conditions\n Please check the minimum requirements.", "Warning", JOptionPane.ERROR_MESSAGE);
			return -1;
		}

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

		result = browserTest();

		if(result < 0){
			System.err.println("\n***TEST FAILED***\n");
			System.err.println("You will probably not be able to run OnlineDesktop in the best conditions");
//			JOptionPane.showMessageDialog(null, "Your browser is not supported\nYou will probably not be able to run OnlineDesktop in the best conditions\n Please check the minimum requirements.", "Warning", JOptionPane.ERROR_MESSAGE);
			return -2;
		}

		result = connectionTest();

		if(result < 0){
			System.err.println("\n***TEST FAILED***\n");
			System.err.println("You will probably not be able to run OnlineDesktop in the best conditions");
//			JOptionPane.showMessageDialog(null, "You might have problems connecting to the OD server\n Please check that you aren't behind a firewall or proxy.", "Warning", JOptionPane.ERROR_MESSAGE);
			return -3;
		}else{
			System.out.println("\n*_*_*_TEST PASSED_*_*_*\n");
		}
		stop();
		System.out.println("End of Test");
		return result;
	}

	/**
	 * Check JVM version: accept only Sun/Apple JVM version 1.5 or higher.
	 *
	 */
	public int javaTest(){
		javaVersion = System.getProperty("java.version");
		javaVendor = System.getProperty("java.vendor");
		operativeSystem = System.getProperty("os.name");
		System.out.println("You're using Java version "+javaVersion+" from "+javaVendor + " on " + operativeSystem);
		float jVersion = 1.0f;
		try{
			jVersion = Float.parseFloat(javaVersion.substring(0, 3));
		}catch(NumberFormatException ex){
			System.err.println("Error parsing java version: "+javaVersion);
		}
		if( jVersion <= 1.4){
			System.err.println("Please, update your Java Virtual Machine");
			return -1;
		}

		if(!(javaVendor.startsWith("Sun Microsystems") || javaVendor.startsWith("Apple"))){
			System.err.println("Please get Java JRE from Sun or Apple in order to run OD");
			return -1;
		}
		return 1;
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
	 * @return 0 if supported, -1 otherwise
	 *
	 */
	public int browserTest(){
		System.out.println("User agent: "+userAgent);
		boolean kjasSM = false; // Konqueror's Security Manager not working well

		if((System.getSecurityManager() != null) && (System.getSecurityManager().toString().startsWith("org.kde.kjas"))) {
			kjasSM = true;
		}

		if(userAgent.contains("Camino") || kjasSM
			|| (userAgent.contains("Firefox") && userAgent.contains("Mac OS X"))
			|| (userAgent.contains("Safari") && userAgent.contains("Windows"))){
			System.err.println("You're using a browser currently unsupported by OnlineDesktop," +
					"or which doesn't have the capabilities to run it. Please try another one");
			return -1;
		}
		return 1;
	}

	/**
	 * Open
	 * @return 0 if successful, -1 otherwise
	 */
	public int connectionTest(){
		ssh = new SshClient();
	    ssh.setSocketTimeout(20000);
	    // Create SSH properties
	    SshConnectionProperties properties = new SshConnectionProperties();
	    properties.setHost(sshHost);

	    String[] sTemp = portList.split(",");
		int[] arrayPorts = new int[sTemp.length];
		for(int i=0; i<sTemp.length; i++){
			try{
			arrayPorts[i] = Integer.parseInt(sTemp[i]);
			properties.setPort(arrayPorts[i],i);
			}catch(NumberFormatException nfe){
				System.err.println("One of the entered ports is not valid "+sTemp[i]);
				throw nfe;
			}
		}
	    if(!proxyHost.equals("")){
			properties.setTransportProviderString(proxyType);
			properties.setPort(443); //Always use this when using proxy
			properties.setProxyHost(proxyHost);
			properties.setProxyPort(proxyPort);
			properties.setProxyUsername(proxyUsername);
			properties.setProxyPassword(proxyPassword);
		}
	    try{
	    	ssh.connect(properties,new ConsoleKnownHostsKeyVerification());
	    	PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
	    	pwd.setUsername("dummy");
	    	pwd.setPassword("dummy");
	    	ssh.authenticate(pwd);
	    	// We know it failed, but we hope it didn't produce any exception.

	    	return 1;
	    }catch(Exception ex){
	    	ex.printStackTrace();
	    	return -1;
	    }
	}


	public void stop() {

	    if(in != null && out != null){
	    	try{
	    	in.close();
	      	out.close();
	    	}catch(IOException ioe){
	    		System.err.println("Problem closing IO streams");
	    	}
	    }
	    if(channel != null) channel = null;
	    if(ssh != null)   ssh.disconnect();

	  }

	/**
	 * Get various parameters passed to the applet:
	 * User agent, from PHP detection
	 *
	 */
	public void readParameters(){
		//userAgent = readParameter("agent");
		//sshHost = readParameter("ssh.host");
		//portList = readParameter("ssh.port");
	}

//	private int readIntParameter(String name, int defaultValue) {
//		String s = readParameter(name);
//	    int result = defaultValue;
//	    if (s != null) {
//	      try {
//	    	  result = Integer.parseInt(s);
//	      } catch (NumberFormatException e) {
//	    	  System.err.println("Parameter "+name+" has invalid value: "+s);
//	      }
//	    }
//	    return result;
//	  }

	private String[] DetectProxy(){
		String proxy_param[] = new String[5];
		int result = -1;
		result = javaTest();
		if(result < 0)
			return null;

		try {
			System.setProperty("java.net.useSystemProxies","true");
			List<Proxy> l = ProxySelector.getDefault().select(new URI("http://www.ulteo.com"));

			for (Iterator<Proxy> iter = l.iterator(); iter.hasNext(); ) {
				Proxy proxy = iter.next();
				InetSocketAddress addr = (InetSocketAddress) proxy.address();
				if(addr == null) {
					// No proxy
					// little hack for JS side
					proxy_param[0] = new String("");
					proxy_param[1] = new String("");
					proxy_param[2] = new String("");
					proxy_param[3] = new String("");
					proxy_param[4] = new String("");
					return proxy_param;
				} else {
					proxy_param[0] = new String(proxy.type().toString());
					proxy_param[1] = new String(addr.getHostName());
					proxy_param[2] = new String(new Integer(addr.getPort()).toString());
					proxy_param[3] = new String("");
					proxy_param[4] = new String("");

					return proxy_param;
				}
			}
		} catch (Exception e) {
			e.printStackTrace();
		}
		return null;
	}
}
