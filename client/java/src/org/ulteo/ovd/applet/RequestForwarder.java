/*
 * Copyright (C) 2009, 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import org.ulteo.ovd.sm.SessionManagerCommunication;

import java.applet.Applet;
import java.io.BufferedInputStream;
import java.io.InputStream;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.List;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import org.w3c.dom.Document;
import org.w3c.dom.Element;

import netscape.javascript.JSObject;

class AjaxOrder {
	public String sm;
	public String mode;
	public String language;
	public String timezone;
	public String callback;
	
	public AjaxOrder(String sm, String mode, String language, String timezone, String callback) {
		this.sm = sm;
		this.mode = mode;
		this.language = language;
		this.timezone = timezone;
		this.callback = callback;
	}
	
	public String toString() {
		return "AjaxOrder("+this.sm+", "+this.mode+", "+this.language+", "+this.timezone+", "+this.callback+")";
	}
}

public class RequestForwarder implements Runnable, HostnameVerifier, X509TrustManager {
	public static final String FIELD_SESSION_MODE = "session_mode";
	
	private static final String CONTENT_TYPE_XML = "text/xml";
	private static final String REQUEST_METHOD_POST = "POST";
	
	private Applet ref = null;
	private List<AjaxOrder> spool = null;
	private boolean do_continue = true;
	
	
	public RequestForwarder(Applet ref) {
		this.ref = ref;
		this.spool = new ArrayList<AjaxOrder>();
	}
	
	public void setDisable() {
		this.do_continue = false;
	}
	
	@Override
	public void run() {
		while(this.do_continue) {
			AjaxOrder o = this.popOrder();
			
			if (o == null) {
				try {
					Thread.sleep(200);
				} catch (InterruptedException e) {
					System.err.println("thread interupted: stop");
					return;
				}
				continue;
			}
			System.out.println("got job "+o);
			URL url = null;
			try {
				url = new URL("https://"+o.sm+"/ovd/client/start.php");
			}
			catch(Exception err) {
				System.out.println("Excption while creating URL object "+err);
			}
			
			boolean success = false;
			if (url != null && o.sm != null) {
				Document doc = SessionManagerCommunication.getNewDocument();
				if (doc == null) {
					System.err.println("Unable to create XML document");
					continue;
				}
				
				Element session = doc.createElement("session");
				doc.appendChild(session);
				
				if (o.mode != null)
					session.setAttribute("mode", o.mode);
				if (o.language != null)
					session.setAttribute("language", o.language);
				if (o.timezone != null)
					session.setAttribute("timezone", o.timezone);
				
				String data = SessionManagerCommunication.Document2String(doc);
				if (data == null) {
					System.err.println("Unable to transform xml document to string");
					continue;
				}
				
				success = this.askWebservice(url, CONTENT_TYPE_XML, REQUEST_METHOD_POST, data, o.callback);
			}
			
			if (success == false)
				this.reportResponse(o.callback, 0, "text/plain", "", new ArrayList<String>());
		}
	}
	
	public synchronized AjaxOrder popOrder() {
		if (this.spool.size() == 0)
			return null;
		
		return this.spool.remove(0);
	}
	
	public synchronized void pushOrder(AjaxOrder o) {
		this.spool.add(o);
	}
	
	private boolean askWebservice(URL url, String content_type, String method, String data, String callback) {
		HttpURLConnection connexion = null;
		
		int http_code = 0;
		String http_message = "";
		String contentType = "";
		String r_data = "";
		List<String> cookies = new ArrayList<String>();
		
		try {
			System.out.println("Connecting URL ... "+url);
			connexion = (HttpURLConnection) url.openConnection();
			
			SSLContext sc = SSLContext.getInstance("SSL");
			sc.init(null, new TrustManager[] { this }, null);
			SSLSocketFactory factory = sc.getSocketFactory();
			((HttpsURLConnection)connexion).setSSLSocketFactory(factory);
			((HttpsURLConnection)connexion).setHostnameVerifier(this);
			
			
			connexion.setDoInput(true);
			connexion.setDoOutput(true);
			connexion.setRequestProperty("Content-type", content_type);
			
			connexion.setAllowUserInteraction(true);
			connexion.setRequestMethod(method);
			OutputStreamWriter out = new OutputStreamWriter(connexion.getOutputStream());
			
			
			if (data != null)
				out.write(data);
			
			out.flush();
			out.close();
			
			http_code = connexion.getResponseCode();
			http_message = connexion.getResponseMessage();
			contentType = connexion.getContentType();
			
			System.out.println("Response "+http_code+ " ==> "+http_message+ " type: "+contentType);
			
			String headerName=null;
			for (int i=1; (headerName = connexion.getHeaderFieldKey(i))!=null; i++) {
				if (headerName.equals("Set-Cookie")) {
					String cookie = connexion.getHeaderField(i);
					
					cookies.add(cookie);
				}
			}
			
			InputStream in = connexion.getInputStream();
			
			BufferedInputStream d = new BufferedInputStream(in);
			for( int c = d.read(); c !=-1; c = d.read())
				r_data+=(char)c;
			
			//System.out.println("content-type: "+contentType+"buffer: \n"+r_data+"==\n");
		}
		catch (Exception e) {
			System.out.println("Exception webservice: "+e);
			return false;
		}
		finally {
			connexion.disconnect();
		}
		
		
		this.reportResponse(callback, http_code, contentType, r_data, cookies);
		return true;
	}
	
	private boolean reportResponse(String callback, int http_code, String contentType, String data, List<String> cookies) {
		Object[] args_cookies = new Object[cookies.size()];
		int i = 0;
		for(String cookie: cookies)
			args_cookies[i++] = cookie;
		
		Object[] args = new Object[4];
		args[0] = new Integer(http_code);
		args[1] = contentType;
		args[2] = data;
		args[3] = args_cookies;
		
		try {
			JSObject win = JSObject.getWindow(this.ref);
			win.call(callback, args);
		}
		catch (netscape.javascript.JSException e) {
			System.err.println(this.getClass()+" error while execute javascript function '"+callback+"' =>"+e.getMessage());
			return false;
		}
		
		return true;
	}
	
	@Override
	public boolean verify(String hostname, SSLSession session) {
		return true;
	}

	@Override
	public void checkClientTrusted(X509Certificate[] arg0, String arg1)
			throws CertificateException {
		return;
	}

	@Override
	public void checkServerTrusted(X509Certificate[] arg0, String arg1)
			throws CertificateException {
		return;
	}

	@Override
	public X509Certificate[] getAcceptedIssuers() {
		return null;
	}
}
