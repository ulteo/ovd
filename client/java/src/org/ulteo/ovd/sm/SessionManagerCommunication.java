/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

import com.sun.org.apache.xerces.internal.dom.DOMImplementationImpl;
import com.sun.org.apache.xerces.internal.parsers.DOMParser;
import com.sun.org.apache.xml.internal.serialize.OutputFormat;
import com.sun.org.apache.xml.internal.serialize.XMLSerializer;
import java.awt.Dimension;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.awt.Toolkit;
import java.io.InputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.logging.Level;
import java.util.logging.Logger;
import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import javax.swing.JDialog;
import javax.swing.JOptionPane;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import net.propero.rdp.RdesktopException;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.I18n;
import org.ulteo.ovd.client.authInterface.KeyLoginListener;
import org.ulteo.rdp.RdpConnectionOvd;
import org.w3c.dom.DOMImplementation;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;


public class SessionManagerCommunication implements Runnable {
	public static final String SESSION_MODE_REMOTEAPPS = "applications";
	public static final String SESSION_MODE_DESKTOP = "desktop";

	private static final String WEBSERVICE_START_SESSION = "start.php";
	private static final String WEBSERVICE_EXTERNAL_APPS = "remote_apps.php";
	private static final String WEBSERVICE_SESSION_STATUS = "session_status.php";
	private static final String WEBSERVICE_LOGOUT = "logout.php";

	public static final String FIELD_LOGIN = "login";
	public static final String FIELD_PASSWORD = "password";
	public static final String FIELD_TOKEN = "token";
	public static final String FIELD_SESSION_MODE = "session_mode";

	private static final String CONTENT_TYPE_FORM = "application/x-www-form-urlencoded";
	private static final String CONTENT_TYPE_XML = "text/xml";

	private static final String SESSION_STATUS_UNKNOWN = "unknown";
	private static final String SESSION_STATUS_ERROR = "error";
	private static final String SESSION_STATUS_INIT = "init";
	private static final String SESSION_STATUS_INITED = "ready";
	private static final String SESSION_STATUS_ACTIVE = "logged";
	private static final String SESSION_STATUS_INACTIVE = "disconnected";
	private static final String SESSION_STATUS_WAIT_DESTROY = "wait_destroy";
	private static final String SESSION_STATUS_DESTROYED = "destroyed";

	private static final long REQUEST_TIME_FREQUENTLY = 2000;
	private static final long REQUEST_TIME_OCCASIONALLY = 5000;

	private String sm = null;
	private boolean use_https = false;
	private ArrayList<RdpConnectionOvd> connections = null;
	private String sessionMode = null;
	private String requestMode = null;
	private String sessionId = null;
	private String sessionStatus = SESSION_STATUS_INIT;
	private boolean sessionIsActive = false;
	private String base_url;
	private JDialog loadFrame = null;
	private boolean graphic = false;
	private String multimedia = null;
	private String printers = null;

	private List<String> cookies = null;

	private CopyOnWriteArrayList<SessionStatusListener> sessionStatusListeners = null;
	private boolean sessionStatusMonitoring = false;
	private long sessionStatusRequestTime = REQUEST_TIME_FREQUENTLY;

	public SessionManagerCommunication(String sm_, JDialog loadFrame, boolean use_https_) {
		this.init(sm_, use_https_);
		this.loadFrame = loadFrame;
		this.graphic = true;
	}

	public SessionManagerCommunication(String sm_, boolean use_https_) {
		this.init(sm_, use_https_);
	}

	private void init(String sm_, boolean use_https_) {
		this.sessionStatusListeners = new CopyOnWriteArrayList<SessionStatusListener>();
		this.connections = new ArrayList<RdpConnectionOvd>();
		this.cookies = new ArrayList<String>();
		this.sm = sm_;
		this.use_https = use_https_;

		this.base_url = "http";
		if (this.use_https)
			this.base_url += "s";
		this.base_url += "://"+this.sm+"/ovd/client/";

	}

	public String getSessionMode() {
		return this.sessionMode;
	}

	private static String makeStringForPost(List<String> listParameter) {
		String listConcat = "";
		if(listParameter.size() > 0) {
			listConcat += listParameter.get(0);
			for(int i = 1 ; i < listParameter.size() ; i++) {
				listConcat += "&";
				listConcat += listParameter.get(i);
			}
		}
		return listConcat;
	}

	private static String concatParams(HashMap<String,String> params) {
		List<String> listParameter = new ArrayList<String>();
		for (String name : params.keySet()) {
			listParameter.add(name+"="+params.get(name));
		}

		return makeStringForPost(listParameter);
	}

	public boolean askForSession(HashMap<String,String> params) {
		if (params == null)
			return false;

		if ((! params.containsKey(FIELD_LOGIN)) || (! params.containsKey(FIELD_PASSWORD)) || (! params.containsKey(FIELD_SESSION_MODE))) {
			System.err.println("ERROR: some askForSession required arguments are missing");
			return false;
		}

		this.requestMode = params.get(FIELD_SESSION_MODE);

		Document response = this.askWebservice(WEBSERVICE_START_SESSION, CONTENT_TYPE_FORM, concatParams(params), true);

		if (response == null)
			return false;

		return this.parseStartSessionResponse(response);
	}

	public boolean askForApplications(HashMap<String,String> params) {
		this.requestMode = SESSION_MODE_REMOTEAPPS;

		if (! params.containsKey(FIELD_TOKEN)) {
			System.err.println("ERROR: some askForApplications required arguments are missing");
			return false;
		}

		if (params.containsKey(FIELD_SESSION_MODE) && (! params.get(FIELD_SESSION_MODE).equals(SESSION_MODE_REMOTEAPPS))) {
			System.out.println("Overriding session mode");
			params.remove(FIELD_SESSION_MODE);
		}
		if (! params.containsKey(FIELD_SESSION_MODE))
			params.put(FIELD_SESSION_MODE, this.requestMode);

		Document response = this.askWebservice(WEBSERVICE_EXTERNAL_APPS, CONTENT_TYPE_FORM, concatParams(params), true);

		if (response == null)
			return false;

		return this.parseStartSessionResponse(response);
	}

	public boolean askForLogout() {
		DOMImplementation domImpl = DOMImplementationImpl.getDOMImplementation();
		Document request = domImpl.createDocument(null, "logout", null);

		Element logout = request.getDocumentElement();
		logout.setAttribute("mode", "logout");

		Document response = this.askWebservice(WEBSERVICE_LOGOUT, CONTENT_TYPE_XML, request, true);

		if (response == null)
			return false;

		return this.parseLogoutResponse(response);
	}

	public boolean askForSessionStatus() {
		DOMImplementation domImpl = DOMImplementationImpl.getDOMImplementation();
		Document request = domImpl.createDocument(null, "session", null);

		Element session = request.getDocumentElement();
		session.setAttribute("id", "");
		session.setAttribute("status", "");

		Document response = this.askWebservice(WEBSERVICE_SESSION_STATUS, CONTENT_TYPE_XML, request, false);

		if (response == null)
			return false;

		return this.parseSessionStatusResponse(response);
	}

	private Document askWebservice(String webservice, String content_type, Object data, boolean showLog) {
		Document document = null;
		HttpURLConnection connexion = null;
		
		try {
			URL url = new URL(this.base_url+webservice);

			if (showLog)
				System.out.println("Connexion a l'url ... "+url);
			connexion = (HttpURLConnection) url.openConnection();

			if (this.use_https) {
				// An all-trusting TrustManager for SSL URL validation
				TrustManager[] trustAllCerts = new TrustManager[] {
					new X509TrustManager() {
						public X509Certificate[] getAcceptedIssuers() {
							return null;
						}
						public void checkServerTrusted(X509Certificate[] certs, String authType) throws CertificateException {
							return;
						}
						public void checkClientTrusted(X509Certificate[] certs, String authType) throws CertificateException {
							return;
						}
					}
				};

				// An all-trusting HostnameVerifier for SSL URL validation
				HostnameVerifier trustAllHosts = new HostnameVerifier() {
					public boolean verify(String hostname, SSLSession session) {
						return true;
					}
				};
				
				SSLContext sc = SSLContext.getInstance("SSL");
				sc.init(null, trustAllCerts, null);
				SSLSocketFactory factory = sc.getSocketFactory();
				((HttpsURLConnection)connexion).setSSLSocketFactory(factory);

				((HttpsURLConnection)connexion).setHostnameVerifier(trustAllHosts);
			}
			connexion.setDoInput(true);
			connexion.setDoOutput(true);
			connexion.setRequestProperty("Content-type", content_type);
			for (String cookie : this.cookies) {
				connexion.setRequestProperty("Cookie", cookie);
			}

			connexion.setAllowUserInteraction(true);
			connexion.setRequestMethod("POST");
			OutputStreamWriter out = new OutputStreamWriter(connexion.getOutputStream());

			if (data instanceof String) {
				out.write((String) data);
			}
			else if (data instanceof Document) {
				Document request = (Document) data;

				OutputFormat outFormat = new OutputFormat(request);
				XMLSerializer serializer = new XMLSerializer(out, outFormat);
				serializer.serialize(request);

				if (showLog)
					this.dumpXML(request, "Receiving XML:");
			}
			else if (data != null) {
				System.err.println("Cannot send "+ data.getClass().getName() +" data to session manager webservices");
				return document;
			}

			out.flush();
			out.close();

			int r = connexion.getResponseCode();
			String res = connexion.getResponseMessage();
			String contentType = connexion.getContentType();

			if (showLog)
				System.out.println("Response "+r+ " ==> "+res+ " type: "+contentType);

			if (r == HttpURLConnection.HTTP_OK && contentType.startsWith(CONTENT_TYPE_XML)) {
				String headerName=null;
				for (int i=1; (headerName = connexion.getHeaderFieldKey(i))!=null; i++) {
					if (headerName.equals("Set-Cookie")) {
						String cookie = connexion.getHeaderField(i);

						boolean cookieIsPresent = false;
						for (String value : this.cookies) {
							if (value.equalsIgnoreCase(cookie))
								cookieIsPresent = true;
						}
						if (! cookieIsPresent)
							this.cookies.add(cookie);
					}
				}
				InputStream in = connexion.getInputStream();

				DOMParser parser = new DOMParser();
				InputSource source = new InputSource(in);
				parser.parse(source);
				in.close();

				document = parser.getDocument();

				if (showLog)
					this.dumpXML(document, "Receiving XML:");
			}
			else {
				System.err.println("Invalid response:\n\tResponse code: "+ r +"\n\tResponse message: "+ res +"\n\tContent type: "+ contentType);
			}
		}
		catch (Exception e) {
			System.err.println("ERROR: "+e.getMessage());
			JOptionPane.showMessageDialog(null, "Cannot access to Session Manager", "Error", JOptionPane.ERROR_MESSAGE);
			KeyLoginListener.PUSHED = false;
			loadFrame.setVisible(false);
			loadFrame.dispose();
		}
		finally {
			connexion.disconnect();
		}

		return document;
	}

	private boolean parseLogoutResponse(Document in) {
		

		return true;
	}

	private boolean parseSessionStatusResponse(Document in) {
		NodeList ns = in.getElementsByTagName("session");

		if (ns.getLength() != 1) {
			System.err.println("Session status webservice does not return session node");
			return false;
		}

		Element sessionNode = (Element) ns.item(0);

		String newSessionStatus = sessionNode.getAttribute("status");

		if (! newSessionStatus.equals(this.sessionStatus)) {
			this.sessionStatus = newSessionStatus;
			System.out.println("session status switch to "+this.getSessionStatus());

			if (this.sessionStatus.equalsIgnoreCase(SESSION_STATUS_INITED) || this.sessionStatus.equalsIgnoreCase(SESSION_STATUS_ACTIVE)) {
				if (! this.sessionIsActive) {
					this.sessionIsActive = true;
					this.fireSessionReady();
				}
			}
			else {
				this.sessionIsActive = false;
				this.fireSessionTerminated();
			}
		}

		return true;
	}

	private boolean parseStartSessionResponse(Document document) {
		Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
		Rectangle dim = null;
		dim = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();
		Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.INFO, "ScreenSize: " + screenSize);

		NodeList ns = document.getElementsByTagName("error");
		Element ovd_node;
		if (ns.getLength() == 1) {
			ovd_node = (Element) ns.item(0);
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "(" + ovd_node.getAttribute("id") + ") " + ovd_node.getAttribute("message"));
			if (graphic) {
				loadFrame.setVisible(false);
				JOptionPane.showMessageDialog(null, ovd_node.getAttribute("message"), "Warning", JOptionPane.WARNING_MESSAGE);
				KeyLoginListener.PUSHED = false;
			}
			return false;
		}
		ns = document.getElementsByTagName("session");
		if (ns.getLength() == 0) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "User already connected");
			if (graphic) {
				loadFrame.setVisible(false);
				JOptionPane.showMessageDialog(null, I18n._("User already connected"), I18n._("Error"), JOptionPane.ERROR_MESSAGE);
				KeyLoginListener.PUSHED = false;
			}
			return false;
		}
		ovd_node = (Element) ns.item(0);
		this.sessionId = ovd_node.getAttribute("id");
		this.sessionMode = ovd_node.getAttribute("mode");
		this.multimedia = ovd_node.getAttribute("multimedia");
		this.printers = ovd_node.getAttribute("redirect_client_printers");
		if (!this.sessionMode.equalsIgnoreCase(this.requestMode)) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "The session manager does not authorize " + this.requestMode + " session mode.");
			if (graphic) {
				loadFrame.setVisible(false);
				JOptionPane.showMessageDialog(null, I18n._("The session manager does not authorize " + this.requestMode), I18n._("Error"), JOptionPane.ERROR_MESSAGE);
				KeyLoginListener.PUSHED = false;
			}
			return false;
		}
		ns = ovd_node.getElementsByTagName("server");
		if (ns.getLength() == 0) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "No application server available");
			if (graphic) {
				loadFrame.setVisible(false);
				JOptionPane.showMessageDialog(null, I18n._("No application server available"), I18n._("Error"), JOptionPane.ERROR_MESSAGE);
				KeyLoginListener.PUSHED = false;
			}
			return false;
		}
		Element server;
		for (int i = 0; i < ns.getLength(); i++) {
			RdpConnectionOvd rc = null;
			server = (Element) ns.item(i);
			NodeList appsList = server.getElementsByTagName("application");
			if (appsList.getLength() == 0) {
				Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, "No applications available");
				if (graphic) {
					loadFrame.setVisible(false);
					JOptionPane.showMessageDialog(null, I18n._("No applications available"), I18n._("Error"), JOptionPane.ERROR_MESSAGE);
					KeyLoginListener.PUSHED = false;
				}
				return false;
			}
			Element appItem = null;
			byte flags = 0x00;
			if (this.sessionMode.equalsIgnoreCase(SESSION_MODE_DESKTOP)) {
				flags |= RdpConnectionOvd.MODE_DESKTOP;
			} else if (this.sessionMode.equalsIgnoreCase(SESSION_MODE_REMOTEAPPS)) {
				flags |= RdpConnectionOvd.MODE_APPLICATION;
			}
			if (this.multimedia.equals("1")) {
				flags |= RdpConnectionOvd.MODE_MULTIMEDIA;
			}
			if (this.printers.equals("1")) {
				flags |= RdpConnectionOvd.MOUNT_PRINTERS;
			}
			try {
				rc = new RdpConnectionOvd(flags);
			} catch (RdesktopException ex) {
				Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, ex.getMessage());
				continue;
			}
			try {
				rc.initSecondaryChannels();
			} catch (RdesktopException e1) {
				Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, e1.getMessage());
			}
			rc.setServer(server.getAttribute("fqdn"));
			rc.setCredentials(server.getAttribute("login"), server.getAttribute("password"));
			// Ensure that width is multiple of 4
			// Prevent artifact on screen with a with resolution
			// not divisible by 4
			rc.setGraphic((int) screenSize.width & ~3, (int) screenSize.height, RdpConnectionOvd.DEFAULT_BPP);
			for (int j = 0; j < appsList.getLength(); j++) {
				appItem = (Element) appsList.item(j);
				NodeList mimeList = appItem.getElementsByTagName("mime");
				ArrayList<String> mimeTypes = new ArrayList<String>();
				if (mimeList.getLength() > 0) {
					Element mimeItem = null;
					for (int k = 0; k < mimeList.getLength(); k++) {
						mimeItem = (Element) mimeList.item(k);
						mimeTypes.add(mimeItem.getAttribute("type"));
					}
				}
				Application app = null;
				try {
					String iconWebservice = "http://" + this.sm + ":1111/icon.php?id=" + appItem.getAttribute("id");
					app = new Application(rc, Integer.parseInt(appItem.getAttribute("id")), appItem.getAttribute("name"), appItem.getAttribute("command"), mimeTypes, new URL(iconWebservice));
				} catch (NumberFormatException e) {
					e.printStackTrace();
					return false;
				} catch (MalformedURLException e) {
					e.printStackTrace();
					return false;
				}
				if (app != null) {
					rc.addApp(app);
				}
			}
			this.connections.add(rc);
		}
		return true;
	}

	private void dumpXML(Document document, String msg) {
		if (msg != null)
			System.out.println(msg);
		
		try {
			TransformerFactory tFactory = TransformerFactory.newInstance();
			Transformer transformer = tFactory.newTransformer();
			DOMSource source = new DOMSource(document);
			StreamResult result = new StreamResult(System.out);
			transformer.transform(source, result);
		} catch (TransformerException ex) {
			Logger.getLogger(SessionManagerCommunication.class.getName()).log(Level.SEVERE, null, ex);
		}
	}

	public ArrayList<RdpConnectionOvd> getConnections() {
		return this.connections;
	}

	public boolean isSessionFinished() {
		return false;
	}

	public void startSessionStatusMonitoring() {
		if (this.sessionStatusMonitoring)
			return;

		this.sessionStatusMonitoring = true;
		new Thread(this).start();
	}

	public void stopSessionStatusMonitoring() {
		this.sessionStatusMonitoring = false;
	}

	public void run() {
		// session status monitoring
		this.sessionStatusRequestTime = REQUEST_TIME_FREQUENTLY;

		while (this.sessionStatusMonitoring) {
			this.askForSessionStatus();
			
			try {
				Thread.sleep(this.sessionStatusRequestTime);
			} catch (InterruptedException ex) {}
		}
	}

	public String getSessionStatus() {
		return this.sessionStatus;
	}

	public void addSessionStatusListener(SessionStatusListener l) {
		this.sessionStatusListeners.add(l);
	}

	public void removeSessionStatusListener(SessionStatusListener l) {
		this.sessionStatusListeners.remove(l);
	}

	private void fireSessionReady() {
		this.sessionStatusRequestTime = REQUEST_TIME_OCCASIONALLY;
		for (SessionStatusListener listener : this.sessionStatusListeners) {
			listener.sessionReady(this.sessionId);
		}
	}

	private void fireSessionTerminated() {
		for (SessionStatusListener listener : this.sessionStatusListeners) {
			listener.sessionTerminated(this.sessionId);
		}
	}
}
