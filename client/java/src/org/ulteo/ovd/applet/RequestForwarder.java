/*
 * Copyright (C) 2009-2013 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2010, 2013
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

import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.ovd.sm.SessionManagerCommunication;
import org.ulteo.utils.KerberosConfiguration;

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
	public String url;
	public String method;
	public String content_type;
	public String data;
	public String request_id;
	
	public AjaxOrder(String url, String method, String content_type, String data, String request_id) {
		this.url = url;
		this.method = method;
		this.content_type = content_type;
		this.data = data;
		this.request_id = request_id;
	}
	
	public String toString() {
		return "AjaxOrder("+this.url+", "+this.method+", "+this.content_type+", "+this.request_id+")";
	}
}

public class RequestForwarder implements Runnable, HostnameVerifier, X509TrustManager {
	public static final String FIELD_SESSION_MODE = "session_mode";
	
	private static final String CONTENT_TYPE_XML = "text/xml";
	private static final String REQUEST_METHOD_GET = "GET";
	private static final String REQUEST_METHOD_POST = "POST";
	
	private WebClient ref = null;
	private List<AjaxOrder> spool = null;
	private boolean do_continue = true;
	private List<String> cookies = null;
	
	
	public RequestForwarder(WebClient ref) {
		this.ref = ref;
		this.spool = new ArrayList<AjaxOrder>();
		this.cookies = new ArrayList<String>();
	}
	
	public void setDisable() {
		this.do_continue = false;
	}
	
	@Override
	public void run() {
		if (OSTools.isWindows())
			new KerberosConfiguration().initialize();
		
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
				url = new URL(o.url);
			}
			catch(Exception err) {
				System.out.println("Excption while creating URL object "+err);
			}
			
			String method = REQUEST_METHOD_GET;
			if (o.method.equalsIgnoreCase("post")) {
				method = REQUEST_METHOD_POST;
			}
			
			boolean success = this.askWebservice(url, o.content_type, method, o.data, o.request_id);
			if (success == false)
				this.ref.forwardAjaxResponse(o.request_id, 0, "text/plain", "");
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
	
	private boolean askWebservice(URL url, String content_type, String method, String data, String request_id) {
		HttpURLConnection connexion = null;
		
		int http_code = 0;
		String http_message = "";
		String contentType = "";
		String r_data = "";
		
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
			for (String cookie : this.cookies) {
				connexion.setRequestProperty("Cookie", cookie);
			}
			
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
					
					this.cookies.add(cookie);
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
		
		
		this.ref.forwardAjaxResponse(request_id, http_code, contentType, r_data);
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
