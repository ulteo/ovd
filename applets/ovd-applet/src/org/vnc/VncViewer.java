//
//  Copyright (C) 2001-2004 HorizonLive.com, Inc.  All Rights Reserved.
//  Copyright (C) 2002 Constantin Kaplinsky.  All Rights Reserved.
//  Copyright (C) 1999 AT&T Laboratories Cambridge.  All Rights Reserved.
//
//  This is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This software is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this software; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,
//  USA.
//

//
// VncViewer.java - the VNC viewer applet.  This class mainly just sets up the
// user interface, leaving it to the VncCanvas to do the actual rendering of
// a VNC desktop.
//

package org.vnc;

import java.awt.Container;
import java.awt.Cursor;
import java.awt.FlowLayout;
import java.awt.Frame;
import java.awt.Graphics;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Image;
import java.awt.Label;
import java.awt.Panel;
import java.awt.Point;
import java.awt.ScrollPane;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.awt.image.BufferedImage;
import java.io.BufferedReader;
import java.io.EOFException;
import java.io.FileReader;
import java.io.IOException;
import java.io.InputStream;
import java.net.ConnectException;
import java.net.NoRouteToHostException;
import java.net.URL;
import java.net.UnknownHostException;
import java.util.HashMap;

import javax.swing.JOptionPane;

import netscape.javascript.JSException;
import netscape.javascript.JSObject;

import org.vnc.rfbcaching.IRfbCachingConstants;
import org.vnc.rfbcaching.RfbCacheProperties;

import com.sshtools.j2ssh.SshClient;
import com.sshtools.j2ssh.authentication.AuthenticationProtocolState;
import com.sshtools.j2ssh.authentication.PasswordAuthenticationClient;
import com.sshtools.j2ssh.configuration.SshConnectionProperties;
import com.sshtools.j2ssh.connection.ChannelInputStream;
import com.sshtools.j2ssh.connection.ChannelOutputStream;
import com.sshtools.j2ssh.forwarding.ForwardingIOChannel;
import com.sshtools.j2ssh.transport.ConsoleKnownHostsKeyVerification;

public class VncViewer extends java.applet.Applet
  implements java.lang.Runnable, WindowListener {

  public static final String version = "0.2.4";

  boolean inAnApplet = true;
  boolean inSeparateFrame = false;
  boolean rfbConnected = false;
  boolean firstTime = true;
  /*InputStream*/ ChannelInputStream in;
  /*OutputStream*/ ChannelOutputStream out;


  //
  // main() is called when run as a java program from the command line.
  // It simply runs the applet inside a newly-created frame.
  //

  public static void main(String[] argv) {
    VncViewer v = new VncViewer();
    v.mainArgs = argv;
    v.inAnApplet = false;
    v.inSeparateFrame = true;

    v.init();
    v.start();
  }

  String[] mainArgs;

  RfbProto rfb;
  Thread rfbThread;

  Frame vncFrame;
  Container vncContainer;
  ScrollPane desktopScrollPane;
  GridBagLayout gridbag;
  ButtonPanel buttonPanel;
  Label connStatusLabel;
  public VncCanvas vc;
  OptionsFrame options;
  ClipboardFrame clipboard;
  RecordingFrame rec;

  // Control session recording.
  Object recordingSync;
  String sessionFileName;
  boolean recordingActive;
  boolean recordingStatusChanged;
  String cursorUpdatesDef;
  String eightBitColorsDef;

  // Applet preload and testing
  boolean connectImmediately;
  boolean startupPreload = false;
  String startuponLoad = "";
  String startuponFailure = "";
  String startuponBadPing = "";

  // Variables read from parameter values.
  String socketFactory;
  String host;
  int port;
  String portList;
  String passwordParam;
  boolean showControls;
  boolean offerRelogin;
  boolean showOfflineDesktop;
  int deferScreenUpdates;
  int deferCursorUpdates;
  int deferUpdateRequests;

  boolean isSSH;
  SshClient ssh;
  int sshPort;
  String sshUser,sshPassword,sshHost;
  String afterLoad,afterSSH, afterConnected, sshError;
  ForwardingIOChannel channel;
  protected static Runtime rt = Runtime.getRuntime();

  //Proxy parameters
  String proxyType,proxyHost,proxyUsername,proxyPassword;
  int proxyPort;

  // RFBCaching properties
  RfbCacheProperties cacheProps = null;

  // Reference to this applet for inter-applet communication.
  public static VncViewer refApplet;

  // test result
  String infoString = "Performing test...";
  public int testResult = 0;
  boolean isTesting = false;


  public void openUrl(String url) throws Exception {
	  getAppletContext().showDocument
	  (new URL(url));
  }

  /**
   * Sets the value of the given Javascript variable
   *
   * @param varName
   * @param value
   */
  public void setJavaScriptVariable(String varName, String value){
	  JSObject window = JSObject.getWindow(this);
	  window.setMember("testResult", value);
  }

  /**
   * Sets the value of the given Javascript variable
   *
   * @param varName
   * @param value
   */
  public void setJavaScriptVariable(String varName, int value){
	  this.setJavaScriptVariable(varName, ""+value);
  }

  /**
   * Calls the given Javascript method with its parameters
   *
   * @param methodName
   * @param value
   */
  public void callJavaScriptMethod(String methodName, Object[] args) {
	  JSObject window = JSObject.getWindow(this);
	  try {
		  window.call(methodName, args);
		  System.out.println("JS method called");
	  } catch(JSException ex) {
//		  This method doesn't like it when the args are null (although it works in Konqueror)
		  System.err.println("Ouch: " + ex.getMessage());
		  setJavaScriptVariable("testResult", testResult);

	  }
  }


  /**
   * testFinished()
   * According to the value of testResult, call one of the three
   * URLs from the parameters: urlOnPass, urlOnFail, urlLowPing
   */
  public void testFinished(int testResult, long avgPing){

	  //1. if fails --> red
	  //2. if high ping --> yellow
	  //3. else --> green
	  System.out.println("Test result: "+testResult);
	  String methodName = null;
	  Object[] methodArgs = null;

	  try{
		  if (testResult < 0){
			  testResult = -testResult;
			  methodName = startuponFailure;
			  methodArgs = new Object[1];
			  methodArgs[0] = ""+testResult;
			  openUrl("javascript:"+startuponFailure+"("+testResult+")");
		  } else if (avgPing > readIntParameter("maxPingAccepted", 250)){
			  methodName = startuponBadPing;
			  openUrl("javascript:"+startuponBadPing);
		  } else {
			  methodName = startuponLoad;
			  openUrl("javascript:"+startuponLoad);
		  }
	  }catch(Exception e){
		  System.err.println("Couldn't execute javascript "+e.getMessage());

		  callJavaScriptMethod(methodName, methodArgs);
	  }

  }


  //
  // init()
  //

  @Override
public void init() {
	 System.out.println("Starting UlteoVNC version "+version);

	 String tmp;
	 tmp = getParameter("preLoad");
	 if(tmp != null && tmp.equalsIgnoreCase("true")){

		 startupPreload = tmp.equalsIgnoreCase("true");
		 tmp = null;

		 // Get the Javascript URLs to show the results
		 tmp = readParameter("onLoad", false);
		 if(tmp != null){
			 startuponLoad = tmp;
			 tmp = null;
		 }
		 tmp = readParameter("onFail", false);
		 if(tmp != null){
			 startuponFailure = tmp;
			 tmp = null;
		 }
		 tmp = readParameter("onBadPing", false);
		 if(tmp != null){
			 startuponBadPing = tmp;
			 tmp = null;
		 }


		 // Start the test!
		 ODTester odtest = new ODTester(this);
		 Thread t = new Thread(odtest);
		 t.start();
	 }

	if(rt.totalMemory() == rt.maxMemory() && rt.freeMemory() < 11000000){
			System.err.println("Not enough memory to start the applet");
		  	JOptionPane.showMessageDialog(null, "Your Java Machine is low on virtual memory.\nPlease restart the browser before launching Ulteo Online Desktop", "Warning", JOptionPane.ERROR_MESSAGE);
	}else if(startupPreload){
		connectImmediately = false;
		return;
	}else{
		readParameters();

    if (inSeparateFrame) {
        vncFrame = new Frame("TightVNC");
        if (!inAnApplet) {
  	vncFrame.add("Center", this);
        }
        vncContainer = vncFrame;
      } else {
        vncContainer = this;
      }

    refApplet = this;


	if (isSSH) {
		try {
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

	   		ssh.connect(properties,new ConsoleKnownHostsKeyVerification());
	    	PasswordAuthenticationClient pwd = new PasswordAuthenticationClient();
    		pwd.setUsername(sshUser);
	    	pwd.setPassword(sshPassword);
	    	int result = ssh.authenticate(pwd);
	    	if(result==AuthenticationProtocolState.COMPLETE) {
				System.out.println("Authentication completed.");
	    	} else {
				System.out.println("Authentication failed");
			}
			int vncServerPort = port;
			int vncLocalPort = vncServerPort+10;

			channel = new ForwardingIOChannel(ForwardingIOChannel.LOCAL_FORWARDING_CHANNEL,
					"VNC","localhost",vncServerPort,"0.0.0.0",vncLocalPort);
			if(ssh.openChannel(channel)){
				System.out.println("Channel open");
				in = channel.getInputStream();
				out = channel.getOutputStream();
			}
			port = vncLocalPort;
			//host = "localhost";
		} catch(Exception e) {
			System.out.println(e.toString());
			JOptionPane.showMessageDialog(this, "Online Desktop could not start for some reason.\n" +
					"Please close the window and try again.\n", "Connection error",JOptionPane.ERROR_MESSAGE);
			stop();
			return;
		}
	}
    recordingSync = new Object();

    options = new OptionsFrame(this);
    clipboard = new ClipboardFrame(this);
    if (RecordingFrame.checkSecurity())
      rec = new RecordingFrame(this);

    sessionFileName = null;
    recordingActive = false;
    recordingStatusChanged = false;
    cursorUpdatesDef = null;
    eightBitColorsDef = null;

    if (inSeparateFrame)
      vncFrame.addWindowListener(this);

    rfbThread = new Thread(this);
    rfbThread.start();
	}
  }

  @Override
public void update(Graphics g) {
//	  g.clearRect(0, 0, this.getHeight(), this.getWidth());
//	  paint(g);
  }

  @Override
public void paint(Graphics g){
//	  if(isTesting){
//		  Color chosenColor;
//		  Font chosenFont;
//		  if (testResult < 0){
//			chosenColor = Color.RED;
//			chosenFont = getFont().deriveFont(Font.BOLD);
//			g.setFont(chosenFont);
//			infoString = "Test FAILED";
//		  } else if(testResult > 0 ){
//			chosenColor = new Color(0, 100, 0);
//			chosenFont = getFont().deriveFont(Font.BOLD);
//			g.setFont(chosenFont);
//			infoString = "test SUCCESSFUL";
//		  } else {
//			chosenColor = Color.BLACK;
//			infoString = "test in progress...";
//		  }
//		  g.setColor(chosenColor);
//		  g.drawString(infoString, 25, 25);
//	  }
  }

  //
  // run() - executed by the rfbThread to deal with the RFB socket.
  //

  public void run() {

  try{
    try {
       if (!connectAndAuthenticate(in, out)) {
    	   System.out.println("Connection failed");
    	   disconnect();
       }
    } catch(Exception e) {}
    doProtocolInitialisation();
    System.out.println("RFB initialized");
    vc = new VncCanvas2(this, getSize().width, getSize().height);
    vc.setFocusable(true);
    vc.setVisible(true);

    //  Disable the local cursor (only soft cursor visible)
        try {
        Image img = new BufferedImage(1, 1, BufferedImage.TYPE_4BYTE_ABGR);
        //if (img != null) {
          Cursor c = getToolkit().createCustomCursor(img,
              new Point(0, 0), "Dot");
          vc.setCursor(c);
        //}
      }
      catch (Throwable t) {
        t.printStackTrace();
      }
     //this is where prepareCanvas() normally goes --> no splash;
      System.out.println("Starting RFB protocol");
     setRfbCachingEncoding();
	 setEncodings();
     processNormalProtocol();

    } catch (NoRouteToHostException e) {
      fatalError("Network error: no route to server: " + host, e);
    } catch (UnknownHostException e) {
      fatalError("Network error: server name unknown: " + host, e);
    } catch (ConnectException e) {
      fatalError("Network error: could not connect to server: " +
		 host + ":" + port, e);
    } catch (EOFException e) {
      if (showOfflineDesktop) {
	e.printStackTrace();
	System.out.println("Network error: remote side closed connection");
	if (vc != null) {
	  vc.enableInput(false);
	}
	if (inSeparateFrame) {
	  vncFrame.setTitle(rfb.desktopName + " [disconnected]");
	}
	if (rfb != null && !rfb.closed())
	  rfb.close();
	if (showControls && buttonPanel != null) {
	  buttonPanel.disableButtonsOnDisconnect();
	  if (inSeparateFrame) {
	    vncFrame.pack();
	  } else {
	    validate();
	  }
	}
      } else {
	fatalError("Network error: remote side closed connection", e);
      }
    } catch (IOException e) {
      String str = e.getMessage();
      if (str != null && str.length() != 0) {
	fatalError("Network Error: " + str, e);
      } else {
	fatalError(e.toString(), e);
      }
    } catch (Exception e) {
      String str = e.getMessage();
      if (str != null && str.length() != 0) {
	fatalError("Error: " + str, e);
      } else {
	fatalError(e.toString(), e);
      }
    }

  }

 private void setRfbCachingEncoding() {
   if (cacheProps != null){
   rfb.setCacheProps(cacheProps);
   int[] encodings = {RfbProto.EncodingRfbCaching};
   try {
       rfb.writeSetEncodings(encodings, 1);
   } catch (Exception e) {
       System.out.println("Could not set encodings");
       System.out.println("Desktop size is " + rfb.framebufferWidth + " x " +
              rfb.framebufferHeight);
       showConnectionStatus(null);
       return;
   }
   }
 }

 public void prepareCanvas(){
	   	  vncContainer.removeAll();
	      gridbag = new GridBagLayout();
	      vncContainer.setLayout(gridbag);

	      GridBagConstraints gbc = new GridBagConstraints();
	      gbc.gridwidth = GridBagConstraints.REMAINDER;
	      gbc.anchor = GridBagConstraints.CENTER;
	      gbc.weightx = 1.0;
	      gbc.weighty = 1.0;

	      if (showControls) {
	        buttonPanel = new ButtonPanel(this);
	        gridbag.setConstraints(buttonPanel, gbc);
	        vncContainer.add(buttonPanel);
	      }
	      if (inSeparateFrame) {

		// Create a panel which itself is resizeable and can hold
		// non-resizeable VncCanvas component at the top left corner.
		Panel canvasPanel = new Panel();
		canvasPanel.setLayout(new FlowLayout(FlowLayout.LEFT, 0, 0));
		canvasPanel.add(vc);

		// Create a ScrollPane which will hold a panel with VncCanvas
		// inside.
		desktopScrollPane = new ScrollPane(ScrollPane.SCROLLBARS_AS_NEEDED);
		gbc.fill = GridBagConstraints.BOTH;
		gridbag.setConstraints(desktopScrollPane, gbc);
		desktopScrollPane.add(canvasPanel);

		// Finally, add our ScrollPane to the Frame window.
		vncFrame.add(desktopScrollPane);
		vncFrame.setTitle(rfb.desktopName);
		vncFrame.pack();
		vc.resizeDesktopFrame();

	      } else {

		// Just add the VncCanvas component to the Applet.
		gridbag.setConstraints(vc, gbc);
		add(vc);
		validate();

	      }

	      if (showControls)
		buttonPanel.enableButtons();
	     moveFocusToDesktop();
  }


  //
  // Create a VncCanvas instance.
  //

//  void createCanvas(int maxWidth, int maxHeight) throws IOException {
//	  System.out.println("Size: "+maxWidth+"x"+maxHeight);
//    // Determine if Java 2D API is available and use a special
//    // version of VncCanvas if it is present.
//    vc = null;
//    vc = new VncCanvas2(this, maxWidth, maxHeight);
//    try {
//      // This throws ClassNotFoundException if there is no Java 2D API.
//      Class cl = Class.forName("java.awt.Graphics2D");
//      // If we could load Graphics2D class, then we can use VncCanvas2D.
//      cl = Class.forName("org.vnc.VncCanvas2");
//      Class[] argClasses = { this.getClass(), Integer.TYPE, Integer.TYPE };
//      java.lang.reflect.Constructor cstr = cl.getConstructor(argClasses);
//      Object[] argObjects =
//        { this, new Integer(maxWidth), new Integer(maxHeight) };
//      vc = (VncCanvas)cstr.newInstance(argObjects);
//    } catch (Exception e) {
//      System.out.println("Warning: Java 2D API is not available\n");
//    }
//
//    // If we failed to create VncCanvas2D, use old VncCanvas.
//    if (vc == null)
//      vc = new VncCanvas(this, maxWidth, maxHeight);
//  }


  //
  // Process RFB socket messages.
  // If the rfbThread is being stopped, ignore any exceptions,
  // otherwise rethrow the exception so it can be handled.
  //

  void processNormalProtocol() throws Exception {
    try {
      vc.processNormalProtocol();
    } catch (Exception e) {
      if (rfbThread == null) {
	System.out.println("Ignoring RFB socket exceptions" +
			   " because applet is stopping");
      } else {
	throw e;
      }
    }
  }


  //
  // Connect to the RFB server and authenticate the user.
  //

  boolean connectAndAuthenticate(InputStream in, /*OutputStream*/ChannelOutputStream out) throws Exception
  {
    showConnectionStatus("Initializing...");
    if (inSeparateFrame) {
      vncFrame.pack();
      vncFrame.setVisible(true);
    } else {
      validate();
    }

    if (isSSH){
        rfb = new RfbProto(in, out, this);
    }else{
        rfb = new RfbProto(host, port, this);
    }

    showConnectionStatus("Connected to server");

    rfb.readVersionMsg();
    showConnectionStatus("RFB server supports protocol version " +
			 rfb.serverMajor + "." + rfb.serverMinor);

    rfb.writeVersionMsg();
    showConnectionStatus("Using RFB protocol version " +
			 rfb.clientMajor + "." + rfb.clientMinor);

    int secType = rfb.negotiateSecurity();
    int authType;
    if (secType == RfbProto.SecTypeTight) {
      showConnectionStatus("Enabling TightVNC protocol extensions");
      rfb.initCapabilities();
      rfb.setupTunneling();
      authType = rfb.negotiateAuthenticationTight();
    } else {
      authType = secType;
    }

    switch (authType) {
    case RfbProto.AuthNone:
      showConnectionStatus("No authentication needed");
      rfb.authenticateNone();
      break;
    case RfbProto.AuthVNC:
      showConnectionStatus("Performing standard VNC authentication");
      if (passwordParam != null) {
        rfb.authenticateVNC(passwordParam);
      } else {
        String pw = askPassword();
        rfb.authenticateVNC(pw);
      }
      break;
    default:
      //throw new Exception("Unknown authentication scheme " + authType);
      return false;
    }
    return true;
  }


  //
  // Show a message describing the connection status.
  // To hide the connection status label, use (msg == null).
  //

  void showConnectionStatus(String msg)
  {
    if (msg == null) {
      if (vncContainer.isAncestorOf(connStatusLabel)) {
	vncContainer.remove(connStatusLabel);
      }
      return;
    }

    System.out.println(msg);

    /*if (connStatusLabel == null) {
      connStatusLabel = new Label("Status: " + msg);
      connStatusLabel.setFont(new Font("Helvetica", Font.PLAIN, 12));
    } else {
      connStatusLabel.setText("Status: " + msg);
    }

    if (!vncContainer.isAncestorOf(connStatusLabel)) {
      GridBagConstraints gbc = new GridBagConstraints();
      gbc.gridwidth = GridBagConstraints.REMAINDER;
      gbc.fill = GridBagConstraints.HORIZONTAL;
      gbc.anchor = GridBagConstraints.NORTHWEST;
      gbc.weightx = 1.0;
      gbc.weighty = 1.0;
      gbc.insets = new Insets(20, 30, 20, 30);
      gridbag.setConstraints(connStatusLabel, gbc);
      vncContainer.add(connStatusLabel);
    }

    if (inSeparateFrame) {
      vncFrame.pack();
    } else {
      validate();
    }*/
  }


  //
  // Show an authentication panel.
  //

  String askPassword() throws Exception
  {
    showConnectionStatus(null);

    AuthPanel authPanel = new AuthPanel(this);

    GridBagConstraints gbc = new GridBagConstraints();
    gbc.gridwidth = GridBagConstraints.REMAINDER;
    gbc.anchor = GridBagConstraints.NORTHWEST;
    gbc.weightx = 1.0;
    gbc.weighty = 1.0;
    gbc.ipadx = 100;
    gbc.ipady = 50;
    gridbag.setConstraints(authPanel, gbc);
    vncContainer.add(authPanel);

    if (inSeparateFrame) {
      vncFrame.pack();
    } else {
      validate();
    }

    authPanel.moveFocusToDefaultField();
    String pw = authPanel.getPassword();
    vncContainer.remove(authPanel);

    return pw;
  }


  //
  // Do the rest of the protocol initialisation.
  //

  void doProtocolInitialisation() throws IOException
  {
    rfb.writeClientInit();
    rfb.readServerInit();

    System.out.println("Desktop size is " + rfb.framebufferWidth + " x " +
		       rfb.framebufferHeight);
    showConnectionStatus(null);
  }


  //
  // Send current encoding list to the RFB server.
  //

  int[] encodingsSaved;
  int nEncodingsSaved;

  void setEncodings()        { setEncodings(false); }
  void autoSelectEncodings() { setEncodings(true); }

  void setEncodings(boolean autoSelectOnly) {
    if (options == null || rfb == null || !rfb.inNormalProtocol)
      return;
    //We can now clear the splash screen and draw the canvas (only once)
    if(firstTime){
    	firstTime = false;
    	prepareCanvas();
    }
    int preferredEncoding = options.preferredEncoding;
    if (preferredEncoding == -1) {
    	preferredEncoding = RfbProto.EncodingTight;
      //long kbitsPerSecond = rfb.kbitsPerSecond();
      /*if (nEncodingsSaved < 1) {


         System.out.println("Using Tight encoding");
         preferredEncoding = RfbProto.EncodingTight;
        //Ulteo: don't change automatically the encoding
       /*} else if (kbitsPerSecond > 2000 &&
                 encodingsSaved[0] != RfbProto.EncodingHextile) {
        // Switch to Hextile if the connection speed is above 2Mbps.
        System.out.println("Throughput " + kbitsPerSecond +
                           " kbit/s - changing to Hextile encoding");
        preferredEncoding = RfbProto.EncodingHextile;

      } else if (kbitsPerSecond < 1000 &&
                 encodingsSaved[0] != RfbProto.EncodingTight) {
        // Switch to Tight/ZRLE if the connection speed is below 1Mbps.
        System.out.println("Throughput " + kbitsPerSecond +
                           " kbit/s - changing to Tight/ZRLE encodings");
        preferredEncoding = RfbProto.EncodingTight;
      } else {
        // Don't change the encoder.
        if (autoSelectOnly)
          return;
        preferredEncoding = encodingsSaved[0];
      }*/
    } else {
      // Auto encoder selection is not enabled.
      if (autoSelectOnly)
        return;
    }

    int[] encodings = new int[20];
    int nEncodings = 0;

    encodings[nEncodings++] = preferredEncoding;
    if (options.useCopyRect) {
      encodings[nEncodings++] = RfbProto.EncodingCopyRect;
    }

    if (preferredEncoding != RfbProto.EncodingTight) {
      encodings[nEncodings++] = RfbProto.EncodingTight;
    }
    if (preferredEncoding != RfbProto.EncodingZRLE) {
      encodings[nEncodings++] = RfbProto.EncodingZRLE;
    }
    if (preferredEncoding != RfbProto.EncodingHextile) {
      encodings[nEncodings++] = RfbProto.EncodingHextile;
    }
    if (preferredEncoding != RfbProto.EncodingZlib) {
      encodings[nEncodings++] = RfbProto.EncodingZlib;
    }
    if (preferredEncoding != RfbProto.EncodingCoRRE) {
      encodings[nEncodings++] = RfbProto.EncodingCoRRE;
    }
    if (preferredEncoding != RfbProto.EncodingRRE) {
      encodings[nEncodings++] = RfbProto.EncodingRRE;
    }

    if (options.compressLevel >= 0 && options.compressLevel <= 9) {
      encodings[nEncodings++] =
        RfbProto.EncodingCompressLevel0 + options.compressLevel;
    }
    if (options.jpegQuality >= 0 && options.jpegQuality <= 9) {
      encodings[nEncodings++] =
        RfbProto.EncodingQualityLevel0 + options.jpegQuality;
    }

    if (options.requestCursorUpdates) {
      encodings[nEncodings++] = RfbProto.EncodingXCursor;
      encodings[nEncodings++] = RfbProto.EncodingRichCursor;
      if (!options.ignoreCursorUpdates)
	encodings[nEncodings++] = RfbProto.EncodingPointerPos;
    }

    encodings[nEncodings++] = RfbProto.EncodingLastRect;
    encodings[nEncodings++] = RfbProto.EncodingNewFBSize;

    boolean encodingsWereChanged = false;
    if (nEncodings != nEncodingsSaved) {
      encodingsWereChanged = true;
    } else {
      for (int i = 0; i < nEncodings; i++) {
        if (encodings[i] != encodingsSaved[i]) {
          encodingsWereChanged = true;
          break;
        }
      }
    }

    if (encodingsWereChanged) {
      try {
        rfb.writeSetEncodings(encodings, nEncodings);
        if (vc != null) {
          vc.softCursorFree();
        }
      } catch (Exception e) {
        e.printStackTrace();
      }
      encodingsSaved = encodings;
      nEncodingsSaved = nEncodings;
    }
  }


  //
  // setCutText() - send the given cut text to the RFB server.
  //

  void setCutText(String text) {
    try {
      if (rfb != null && rfb.inNormalProtocol) {
	rfb.writeClientCutText(text);
      }
    } catch (Exception e) {
      e.printStackTrace();
    }
  }


  //
  // Order change in session recording status. To stop recording, pass
  // null in place of the fname argument.
  //

  void setRecordingStatus(String fname) {
    synchronized(recordingSync) {
      sessionFileName = fname;
      recordingStatusChanged = true;
    }
  }

  //
  // Start or stop session recording. Returns true if this method call
  // causes recording of a new session.
  //

  boolean checkRecordingStatus() throws IOException {
    synchronized(recordingSync) {
      if (recordingStatusChanged) {
	recordingStatusChanged = false;
	if (sessionFileName != null) {
	  startRecording();
	  return true;
	} else {
	  stopRecording();
	}
      }
    }
    return false;
  }

  //
  // Start session recording.
  //

  protected void startRecording() throws IOException {
    synchronized(recordingSync) {
      if (!recordingActive) {
	// Save settings to restore them after recording the session.
	cursorUpdatesDef =
	  options.choices[options.cursorUpdatesIndex].getSelectedItem();
	eightBitColorsDef =
	  options.choices[options.eightBitColorsIndex].getSelectedItem();
	// Set options to values suitable for recording.
	options.choices[options.cursorUpdatesIndex].select("Disable");
	options.choices[options.cursorUpdatesIndex].setEnabled(false);
	options.setEncodings();
	options.choices[options.eightBitColorsIndex].select("No");
	options.choices[options.eightBitColorsIndex].setEnabled(false);
	options.setColorFormat();
      } else {
	rfb.closeSession();
      }

      System.out.println("Recording the session in " + sessionFileName);
      rfb.startSession(sessionFileName);
      recordingActive = true;
    }
  }

  //
  // Stop session recording.
  //

  protected void stopRecording() throws IOException {
    synchronized(recordingSync) {
      if (recordingActive) {
	// Restore options.
	options.choices[options.cursorUpdatesIndex].select(cursorUpdatesDef);
	options.choices[options.cursorUpdatesIndex].setEnabled(true);
	options.setEncodings();
	options.choices[options.eightBitColorsIndex].select(eightBitColorsDef);
	options.choices[options.eightBitColorsIndex].setEnabled(true);
	options.setColorFormat();

	rfb.closeSession();
	System.out.println("Session recording stopped.");
      }
      sessionFileName = null;
      recordingActive = false;
    }
  }


  //
  // readParameters() - read parameters from the html source or from the
  // command line.  On the command line, the arguments are just a sequence of
  // param_name/param_value pairs where the names and values correspond to
  // those expected in the html applet tag source.
  //

  void readParameters() {
    host = readParameter("HOST", !inAnApplet);
    if (host == null) {
      host = getCodeBase().getHost();
      if (host.equals("")) {
	fatalError("HOST parameter not specified");
      }
    }

    String str = readParameter("PORT", true);
    port = Integer.parseInt(str);

    cacheProps = readCacheProperties();

	// Added by Sandaruwan
	isSSH = true;
	str = readParameter("SSH",false);
	if (str != null && str.equalsIgnoreCase("No"))
		isSSH = false;


    // Read "ENCPASSWORD" or "PASSWORD" parameter if specified. Also SSH password
    readPasswordParameters();

    if (inAnApplet) {
      str = readParameter("Open New Window", false);
      if (str != null && str.equalsIgnoreCase("Yes"))
	inSeparateFrame = true;
    }

    // "Show Controls" set to "No" disables button panel.
    showControls = false;
    str = readParameter("Show Controls", false);
    if (str != null && str.equalsIgnoreCase("Yes"))
      showControls = true;


	sshHost = readParameter("ssh.host", isSSH);
	sshUser = readParameter("ssh.user", isSSH);
    //ArnauVP: we read the whole list and we'll parse it later
	portList = readParameter("ssh.port", isSSH);

	afterLoad = readParameter("afterLoad", false);
	afterSSH = readParameter("afterSSH", false);
	afterConnected = readParameter("afterConnected", false);
	sshError = readParameter("sshError", false);

	// End - Sandaruwan

	//Read proxy parameters, if any -- by ArnauVP
	proxyType = readParameter("proxyType", false);
	proxyHost = readParameter("proxyHost", false);
	proxyPort = readIntParameter("proxyPort", 80);
	proxyUsername = readParameter("proxyUsername", false);
	proxyPassword = readParameter("proxyPassword", false);

	//viewOnly = readParameter("viewOnly",true);

	// "Offer Relogin" set to "No" disables "Login again" and "Close
    // window" buttons under error messages in applet mode.
    offerRelogin = false;
    str = readParameter("Offer Relogin", false);
    if (str != null && str.equalsIgnoreCase("Yes"))
      offerRelogin = true;

    // Do we continue showing desktop on remote disconnect?
    showOfflineDesktop = false;
    str = readParameter("Show Offline Desktop", false);
    if (str != null && str.equalsIgnoreCase("Yes"))
      showOfflineDesktop = true;



    // Fine tuning options.
    deferScreenUpdates = readIntParameter("Defer screen updates", 20);
    deferCursorUpdates = readIntParameter("Defer cursor updates", 10);
    deferUpdateRequests = readIntParameter("Defer update requests", 50);

    // SocketFactory.
    socketFactory = readParameter("SocketFactory", false);
  }

  // Read cache parameters from html-applet properties
  //
  private RfbCacheProperties readCacheProperties(){
      String isCacheOnS = readParameter("rfb.cache.enabled", false);
      boolean isCacheOn = false;
      if (isCacheOnS!=null){
          if (isCacheOnS.equalsIgnoreCase("TRUE")){
              isCacheOn = true;
          }
      }
      if (!isCacheOn){
          //System.out.println("Caching is switched off");
          return null;
      }
      int cacheVerMajor = readIntParameter("rfb.cache.ver.major", IRfbCachingConstants.RFB_CACHE_DEFAULT_VER_MAJOR);
      int cacheVerMinor = readIntParameter("rfb.cache.ver.minor", IRfbCachingConstants.RFB_CACHE_DEFAULT_VER_MINOR);
      int cacheSize = readIntParameter("rfb.cache.size", IRfbCachingConstants.RFB_CACHE_DEFAULT_SIZE);
      String cacheMaintAlgS = readParameter("rfb.cache.alg", false);
      int cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_DEFAULT_MAINT_ALG;
      if (cacheMaintAlgS!=null){
          if (cacheMaintAlgS.equalsIgnoreCase("FIFO")){
              cacheMaintAlgI = IRfbCachingConstants.RFB_CACHE_DEFAULT_MAINT_ALG;
          }else if (!cacheMaintAlgS.equalsIgnoreCase("LRU")){
              //System.out.println("Unknown cache algorithm specified, (LRU) will be used as default");
          }
      }
      int cacheDataSize = readIntParameter("rfb.cache.datasize", IRfbCachingConstants.RFB_CACHE_DEFAULT_DATA_SIZE);
      return new RfbCacheProperties(cacheMaintAlgI, cacheSize, cacheDataSize, cacheVerMajor, cacheVerMinor);
  }


  //
  // Read password parameters. If an "ENCPASSWORD" parameter is set,
  // then decrypt the password into the passwordParam string. Otherwise,
  // try to read the "PASSWORD" parameter directly to passwordParam.
  //

  private void readPasswordParameters() {
    String encPasswordParam = readParameter("ENCPASSWORD", false);
    String sshEncPassword = readParameter("ssh.password", true);
    if (encPasswordParam == null) {
      passwordParam = readParameter("PASSWORD", false);
    } else {
      // ENCPASSWORD is hexascii-encoded. Decode.
      byte[] pw = {0, 0, 0, 0, 0, 0, 0, 0};
      int len = encPasswordParam.length() / 2;
      if (len > 8)
        len = 8;
      for (int i = 0; i < len; i++) {
        String hex = encPasswordParam.substring(i*2, i*2+2);
        Integer x = new Integer(Integer.parseInt(hex, 16));
        pw[i] = x.byteValue();
      }
      // Decrypt the VNC password.
      byte[] key = {23, 82, 107, 6, 35, 78, 88, 7};
      DesCipher des = new DesCipher(key);
      des.decrypt(pw, 0, pw, 0);
      passwordParam = new String(pw);

      // Same with SSH password
      byte[] c = {
    	        0, 0, 0, 0, 0, 0, 0, 0};
      len = sshEncPassword.length() / 2;
      for (int i = 0; i < len; i++) {
    	  String hex = sshEncPassword.substring(i * 2, i * 2 + 2);
    	  Integer x = new Integer(Integer.parseInt(hex, 16));
    	  c[i] = x.byteValue();
      }
      sshPassword = new String(c);

    }
  }
  
public String readParameter(String name, boolean required) {
    if (inAnApplet) {
      String s = getParameter(name);
      if ((s == null) && required) {
    	  fatalError(name + " parameter not specified");
      }
      return s;
    }

    for (int i = 0; i < mainArgs.length; i += 2) {
      if (mainArgs[i].equalsIgnoreCase(name)) {
	try {
	  return mainArgs[i+1];
	} catch (Exception e) {
	  if (required) {
	    fatalError(name + " parameter not specified");
	  }
	  return null;
	}
      }
    }
    if (required) {
      fatalError(name + " parameter not specified");
    }
    return null;
  }

  int readIntParameter(String name, int defaultValue) {
    String str = readParameter(name, false);
    int result = defaultValue;
    if (str != null) {
      try {
    	  result = Integer.parseInt(str);
      } catch (NumberFormatException e) { }
    }
    return result;
  }

  //
  // moveFocusToDesktop() - move keyboard focus to VncCanvas.
  //

  void moveFocusToDesktop() {
    if (vncContainer != null) {
      if (vc != null && vncContainer.isAncestorOf(vc)) {
    	  vc.requestFocusInWindow();
//    	  System.out.println("Requested focus: " + vc.hasFocus());
      }
    }
  }

  public ClipboardFrame getClipboard() {
	return clipboard;
  }

  //
  // disconnect() - close connection to server.
  //

  synchronized public void disconnect() {
    System.out.println("Disconnect");

    if (rfb != null && !rfb.closed())
      rfb.close();
    options.dispose();
    clipboard.dispose();
    if (rec != null)
      rec.dispose();

    if (inAnApplet) {
      showMessage("Disconnected");
    } else {
      System.exit(0);
    }
  }

  //
  // fatalError() - print out a fatal error message.
  // FIXME: Do we really need two versions of the fatalError() method?
  //

  synchronized public void fatalError(String str) {
    System.out.println(str);

    if (inAnApplet) {
      // vncContainer null, applet not inited,
      // can not present the error to the user.
      Thread.currentThread().stop();
    } else {
      System.exit(1);
    }
  }


  synchronized public void fatalError(String str, Exception e) {

    if (rfb != null && rfb.closed()) {
      // Not necessary to show error message if the error was caused
      // by I/O problems after the rfb.close() method call.
      System.out.println("RFB thread finished");
      return;
    }

    System.out.println(str);
    e.printStackTrace();

    if (rfb != null)
      rfb.close();

    if (inAnApplet) {
      showMessage(str);
    	//show popup and close the window
    } else {
      System.exit(1);
    }
  }

  //
  // Show message text and optionally "Relogin" and "Close" buttons.
  //

  void showMessage(String msg) {
    vncContainer.removeAll();
    JOptionPane.showMessageDialog(this, "The Online Desktop has closed.\n" +
			"Thanks for using our service!\n", "Online Desktop session finished",JOptionPane.INFORMATION_MESSAGE);
    	//System.err.println("ERROR: "+msg+"\n");
  }

  //
  // Stop the applet.
  // Main applet thread will terminate on first exception
  // after seeing that rfbThread has been set to null
  //


 @Override
public void stop() {
    System.out.println("Stopping applet");
    try{
        if (rfb != null && !rfb.closed())
            rfb.close();
    }catch(NullPointerException npe){
       	System.err.println("Problem closing RFB");
    }
    if(in != null && out != null){
    	try{
    	in.close();
      	out.close();
    	}catch(IOException ioe){
    		System.err.println("Problem closing IO streams");
    	}
    }
    vc = null;
    channel = null;
    if(ssh != null)   ssh.disconnect();
    rfbThread = null;
  }

  //
  // This method is called before the applet is destroyed.
  //

//  public void destroy() {
//    System.out.println("Destroying applet");
//    if(vncContainer != null && options != null && clipboard != null){
//    	vncContainer.removeAll();
//    	options.dispose();
//    	clipboard.dispose();
//    }
//    if (rec != null)
//      rec.dispose();
//    if (inSeparateFrame)
//      vncFrame.dispose();
//    refApplet = null;
//  }

  //
  // Start/stop receiving mouse events.
  //

//  public void enableInput(boolean enable) {
//    vc.enableInput(enable);
//  }

  //
  // Close application properly on window close event.
  //

  public void windowClosing(WindowEvent evt) {
    System.out.println("Closing window");
    if (rfb != null)
      disconnect();

    vncContainer.setVisible(false);

    if (!inAnApplet) {
      System.exit(0);
    }
  }

  @Override
public String getAppletInfo() {
		return "UlteoVNC";
	}

  public String getHostToPing(){
	  return getParameter("hostToPing");
  }

  //
  // Ignore window events we're not interested in.
  //

  public void windowActivated(WindowEvent evt) {
//	  System.out.println("Window activated");
  }

  public void windowDeactivated (WindowEvent evt) {}
  public void windowOpened(WindowEvent evt) {}
  public void windowClosed(WindowEvent evt) {}
  public void windowIconified(WindowEvent evt) {}
  public void windowDeiconified(WindowEvent evt) {}
  
  
  
  
  // -------------------------------------------------------------------
  

//cd ~/ulteo/client/client-python
//python ovd-client.py -l frank -g 800x600 http://connectme22.ulteo.com/sessionmanager/
//test1234
  
//  private  HashMap<String, String> map = null;
//  private final boolean K1ZFP = true;
//    @Override
//  public String getParameter(String name) 
//    {
//  	  if (K1ZFP)
//  	  		{
//  			  if (map==null)
//  				  {
//  				  map = new HashMap<String, String>();
//  				  map.put("width", "800");
//  				  map.put( "height","600");
//  					try 
//  					{
//  					final String NAME="name=\"";
//  					final String VALUE ="value=\""; 
//  					FileReader fr = new FileReader("/home/frankpreel/ulteo/client/client-python/content.html");
//  					BufferedReader br = new BufferedReader(fr);
//  					String s;
//  					while ((s = br.readLine()) != null)
//  						{
//  						int idxname = s.indexOf(NAME);
//  						int idxval = s.indexOf(VALUE);
//  						if (idxname>=0 && idxval>=0)
//  							{
//  							String newstr = s.substring(idxname+NAME.length(), s.lastIndexOf("\""));
//  							idxval = newstr.indexOf(VALUE);
//  							String _name = newstr.substring(0, newstr.indexOf("\""));
//  							String _val = newstr.substring(idxval+VALUE.length());
//  							
//  							//System.out.println(newstr+"("+_name+"-"+_val+")");
//  							map.put(_name, _val);
//  							}
//  						}
//  					fr.close();
//  				} catch (Throwable e) { e.printStackTrace();}					  
//  				  }
//  			  return map.get(name);
//  		}
//  	return super.getParameter(name);
//  }
  
}
