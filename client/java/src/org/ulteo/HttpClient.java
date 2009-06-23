package org.ulteo;

import java.util.*;
import java.io.*;
import java.net.*;

import java.net.CookieHandler;
import java.net.URI;
import java.net.URL;

public class HttpClient {
    protected URL url;
    protected HttpURLConnection server;
    protected boolean connected = false;

    static CookieHandler manager= new CookieHandler(){
	    private Map<String,String> jar= new HashMap<String,String>();

	    private Map<String,List<String>> getCookieMap(){
		if (jar==null) return null;
		List<String> clist=new ArrayList<String>();
		for (String k: jar.keySet()){
		    clist.add(k+"="+jar.get(k));
		}
		Map<String,List<String>> ret=
		    new HashMap<String,List<String>>();
		ret.put("Cookie",clist);
		return ret;
	    }
   
	    public Map<String,List<String>> get(URI uri, Map<String,List<String>> reqH){
		if (reqH==null) return getCookieMap();
		HashMap<String,List<String>> ret=
		    new HashMap<String,List<String>>(reqH);
		ret.putAll(getCookieMap());
		return ret;
	    }
   
	    public void put(URI uri, Map<String,List<String>> respH)
		throws IOException{
		for (String k: respH.keySet()){
		    if (k==null) continue;  
		    if (k.equalsIgnoreCase("Set-Cookie")){
			for (String v: respH.get(k)){
			    String kv[]=v.split(";")[0].trim().split("=",2);
			    jar.put(kv[0],kv[1]);
			}
		    }
		}
	    }
	}; // anonymous subclass for CookieHandler

    public HttpClient(URL url) {
	this.url = url;
	CookieHandler.setDefault(manager);
    }

    public void connect(String method) throws Exception{
	try {
	    server = (HttpURLConnection)this.url.openConnection();
	    server.setDoInput(true);
	    server.setDoOutput(true);
	    server.setRequestMethod(method);
	    server.setRequestProperty("Content-type",
				      "application/x-www-form-urlencoded");
	    server.connect();
	} catch (Exception e) {
	    throw new Exception("Connection failed");
	}
	this.connected = true;
    }

    public void disconnect() {
	this.url = server.getURL();

	server.disconnect();
	this.connected = false;
    }

    public int request(String method, String path, String args) throws Exception {
	if (this.connected)
	    this.disconnect();

	this.url = new URL(this.url, path);
	System.out.println("http request url: " + this.url.toString());

	this.connect(method);
	    
	try {
	    BufferedWriter bw = new BufferedWriter(new OutputStreamWriter(server.getOutputStream()));
	    bw.write(args, 0, args.length());
	    bw.flush();
	    bw.close();
	} catch(Exception e) {
	    throw new Exception("Unable to write to output stream");
	}

	return server.getResponseCode();
    }

    public int request_GET(String path, String args) throws Exception {
	return this.request("GET", path, args);
    }

    public int request_POST(String path, String args) throws Exception {
	return this.request("POST", path, args);
    }



    public String getResponseText() throws Exception {
	String line;
	String response;
	try {
	    BufferedReader s = new BufferedReader(new InputStreamReader(server.getInputStream()));
	    line = s.readLine();
	    response = "";
	    while (line != null) {
		response += line;
		line = s.readLine();
	    }
	    s.close();
	} catch(Exception e) {
	    throw new Exception("Unable to read input stream");
	}

	return response;
    }
}
