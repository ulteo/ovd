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
import java.awt.Toolkit;
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

import java.awt.Dimension;


import org.vnc.rfbcaching.IRfbCachingConstants;
import org.vnc.rfbcaching.RfbCacheProperties;

public class VncViewer implements WindowListener{

  public static final String version = "0.2.4";
  public boolean inSeparateFrame = true;
  public boolean firstTime = true;

    public static void usage() {
	System.err.println("VncViewer");
	System.err.println("Usage: java VncViewer [options]");
	System.err.println("	HOST vnc host");
	System.err.println("	PORT vnc port");

	System.exit(1);
    }

  //
  // main() is called when run as a java program from the command line.
  // It simply runs the applet inside a newly-created frame.
  //

  public static void main(String[] argv) {
    VncViewer v = new VncViewer();

    v.arg_parser = new ArgParser(argv);

    v.readParameters();

    v.init();
    v.start();
  }

//  public String[] mainArgs;
  public ArgParser arg_parser;
  public int width = 800;
  public int height = 600;

  public RfbProto rfb;
  public Thread rfbThread;

  public Frame vncFrame;
  public Container vncContainer;
  public ScrollPane desktopScrollPane;
  public GridBagLayout gridbag;
  public ButtonPanel buttonPanel;
  public Label connStatusLabel;
  public VncCanvas vc;
  public OptionsFrame options;
  public ClipboardFrame clipboard;
  public RecordingFrame rec;

  // Control session recording.
  public Object recordingSync;
  public String sessionFileName;
  public boolean recordingActive;
  public boolean recordingStatusChanged;
  public String cursorUpdatesDef;
  public String eightBitColorsDef;

  // Applet preload and testing
  //boolean connectImmediately;
  public boolean startupPreload = false;
  public String startuponLoad = "";
  public String startuponFailure = "";
  public String startuponBadPing = "";

  // Variables read from parameter values.
  public String socketFactory;
  public String host;
  public int port;
  public String passwordParam;
  public boolean showControls;
  //boolean offerRelogin;
  public boolean showOfflineDesktop;
  public int deferScreenUpdates;
  public int deferCursorUpdates;
  public int deferUpdateRequests;

  // RFBCaching properties
  RfbCacheProperties cacheProps = null;

  // Reference to this applet for inter-applet communication.
  //public static VncViewer refApplet;

  public VncViewer() {
      this.inSeparateFrame = true;
        vncFrame = new Frame("TightVNC");
        vncContainer = vncFrame;
  }  
  public VncViewer(Container c) {
      this.inSeparateFrame = false;
      this.vncContainer = c;
  }


  //
  // init()
  //

public void init() {
	 System.out.println("Starting UlteoVNC version "+version);

    recordingSync = new Object();
    System.out.println("init 0");
    options = new OptionsFrame(this);
    clipboard = new ClipboardFrame(this);
    if (RecordingFrame.checkSecurity())
      rec = new RecordingFrame(this);
    System.out.println("init 1");
    sessionFileName = null;
    recordingActive = false;
    recordingStatusChanged = false;
    cursorUpdatesDef = null;
    eightBitColorsDef = null;
    System.out.println("init 2");
    if (inSeparateFrame)
      vncFrame.addWindowListener(this);
    System.out.println("init 3");
  }

  //
  // run() - executed by the rfbThread to deal with the RFB socket.
  //

  public void start() {
      System.out.println("Test 0");
      gridbag = new GridBagLayout();
      vncContainer.setLayout(gridbag);
      System.out.println("Test 1");
      if (showControls) {
	  GridBagConstraints gbc = new GridBagConstraints();
	  gbc.gridwidth = GridBagConstraints.REMAINDER;
	  gbc.anchor = GridBagConstraints.CENTER;
	  gbc.weightx = 1.0;
	  gbc.weighty = 1.0;

	  buttonPanel = new ButtonPanel(this);
	  gridbag.setConstraints(buttonPanel, gbc);
	  vncContainer.add(buttonPanel);
      }

      System.out.println("Test 2");
      try{
	  try {
	      if (!connectAndAuthenticate()) {
		  System.out.println("Connection failed");
		  disconnect();
	      }
	  }
	  catch(java.net.ConnectException e) {
	      System.out.println("No such vnc server at "+host+":"+port);
	      disconnect();
	      return;
	  }
	  catch(Exception e) {
	      System.out.println("Unable to launch VNC");
	      e.printStackTrace();
	      disconnect();
	      return;
	  }
    doProtocolInitialisation();
    System.out.println("RFB initialized");
    vc = new VncCanvas2(this, this.width, this.height);
    System.out.println("debug -1");
    vc.setFocusable(true);
    vc.setVisible(true);
    System.out.println("debug 0");


    if (this.vncFrame != null) {
		
	Dimension d = this.vncFrame.getMaximumSize();
	System.out.println("height: "+d.getHeight());
	System.out.println("width: "+d.getWidth());
    }

    
    //  Disable the local cursor (only soft cursor visible)
        try {
        Image img = new BufferedImage(1, 1, BufferedImage.TYPE_4BYTE_ABGR);
        Toolkit t = java.awt.Toolkit.getDefaultToolkit();

        Cursor c = t.createCustomCursor(img, new Point(0, 0), "Dot");
        vc.setCursor(c);
        }
      catch (Throwable t) {
        t.printStackTrace();
      }
this.vncContainer.validate();
    System.out.println("debug 1");
     //this is where prepareCanvas() normally goes --> no splash;
      System.out.println("Starting RFB protocol");
     setRfbCachingEncoding();
System.out.println("debug 1_0");
	 setEncodings();
System.out.println("debug 1_1");
     processNormalProtocol();
System.out.println("debug 1_2");

    System.out.println("debug 2");
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
	    System.out.println("Coucou 0");
	if (showControls && buttonPanel != null) {
	    System.out.println("Coucou 1");
	  buttonPanel.disableButtonsOnDisconnect();
	  if (inSeparateFrame) {
	    vncFrame.pack();
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
     //  vncContainer.removeAll();


	      GridBagConstraints gbc = new GridBagConstraints();
	      gbc.gridwidth = GridBagConstraints.REMAINDER;
	      gbc.anchor = GridBagConstraints.CENTER;
	      gbc.weightx = 1.0;
	      gbc.weighty = 1.0;

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

	      if (inSeparateFrame) {
		// Finally, add our ScrollPane to the Frame window.
		vncFrame.add(desktopScrollPane);
		vncFrame.setTitle(rfb.desktopName);
		vncFrame.pack();
	      }
	      else {
		  // Finally, add our ScrollPane to the Frame window.
		  vncContainer.add(desktopScrollPane);
	      }
	      vc.resizeDesktopFrame();

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

  protected boolean connectAndAuthenticate() throws Exception
  {
    showConnectionStatus("Initializing...");
    if (inSeparateFrame) {
      vncFrame.pack();
      //      vncFrame.setVisible(true);
    }
    vncContainer.setVisible(true);
    System.out.println("rfb proto");
    if (rfb == null) {
	System.out.println("rfb proto def");
	rfb = new RfbProto(host, port, this);
    }

    showConnectionStatus("Connected to server");

    rfb.readVersionMsg();
System.out.println("pouet 22");
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

  String askPassword() // throws Exception
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

  public void setEncodings()        { setEncodings(false); }
  public void autoSelectEncodings() { setEncodings(true); }

  public void setEncodings(boolean autoSelectOnly) {
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

  public void setCutText(String text) {
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

  public void setRecordingStatus(String fname) {
    synchronized(recordingSync) {
      sessionFileName = fname;
      recordingStatusChanged = true;
    }
  }

  //
  // Start or stop session recording. Returns true if this method call
  // causes recording of a new session.
  //

  public boolean checkRecordingStatus() throws IOException {
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

  public void startRecording() throws IOException {
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

  public void stopRecording() throws IOException {
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

  public void readParameters() {
    host = readParameter("HOST", true);

    String str = readParameter("PORT", true);
    port = Integer.parseInt(str);

    cacheProps = readCacheProperties();


    str = readParameter("GEOMETRY", false);
    if (str != null) {
	try {
	    int cut = str.indexOf("x", 0);
	    int w = Integer.parseInt(str.substring(0, cut));
	    int h = Integer.parseInt(str.substring(cut + 1));

	    // attr modification after any possible Exceptions
	    this.width = w;
	    this.height = h;
	} catch(Exception e) {
	    System.err.println("GEOMETRY parsing error");
	}
    }

    // Read "ENCPASSWORD" or "PASSWORD" parameter if specified.
    readPasswordParameters();

   // "Show Controls" set to "No" disables button panel. */
    showControls = false;
   str = readParameter("Show Controls", false);
   if (str != null && str.equalsIgnoreCase("Yes"))
     showControls = true;

	//viewOnly = readParameter("viewOnly",true);

/*	"Offer Relogin" set to "No" disables "Login again" and "Close */
  /*  window" buttons under error messages in applet mode. */
    //offerRelogin = false;
   str = readParameter("Offer Relogin", false);
   if (str != null && str.equalsIgnoreCase("Yes"))
     ;//offerRelogin = true;

/*    Do we continue showing desktop on remote disconnect? */
    showOfflineDesktop = false;
   str = readParameter("Show Offline Desktop", false);
   if (str != null && str.equalsIgnoreCase("Yes"))
     showOfflineDesktop = true;



 /*   Fine tuning options. */
    deferScreenUpdates = readIntParameter("Defer screen updates", 20);
    deferCursorUpdates = readIntParameter("Defer cursor updates", 10);
    deferUpdateRequests = readIntParameter("Defer update requests", 50);

    // SocketFactory.
    socketFactory = readParameter("SocketFactory", false);
 }

  // Read cache parameters from html-applet properties
  //
  public RfbCacheProperties readCacheProperties(){
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

  public void readPasswordParameters() {
      passwordParam = readParameter("PASSWORD", false);
  }
  
public String readParameter(String name, boolean required) {
    System.out.println("Read parameter '"+name+"'");

    String buffer = this.arg_parser.getParameter(name);
    if (buffer == null && required)
	fatalError(name + " parameter not specified", null);

    return buffer;
  }

public int readIntParameter(String name, int defaultValue) {
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

public void moveFocusToDesktop() {
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

//    if (inAnApplet) {
 //     showMessage("Disconnected");
 //   } else {
      System.exit(0);
//    }
  }

  //
  // fatalError() - print out a fatal error message.
  // FIXME: Do we really need two versions of the fatalError() method?
  //

//  synchronized public void fatalError(String str) {
//    System.out.println(str);
//
//    if (inAnApplet) {
//      // vncContainer null, applet not inited,
//      // can not present the error to the user.
//      Thread.currentThread().stop();
//    } else {
//      System.exit(1);
//    }
//  }


  synchronized public void fatalError(String str, Exception e) {

    if (rfb != null && rfb.closed()) {
      // Not necessary to show error message if the error was caused
      // by I/O problems after the rfb.close() method call.
      System.out.println("RFB thread finished");
      return;
    }

    System.out.println(str);
    if (e!=null){
    e.printStackTrace();
    }
    
    if (rfb != null)
      rfb.close();

//    if (inAnApplet) {
//      showMessage(str);
    	//show popup and close the window
//    } else {
      System.exit(1);
//    }
  }

  //
  // Show message text and optionally "Relogin" and "Close" buttons.
  //

  void showMessage(String msg) {
    vncContainer.removeAll();
    System.out.println("Error: "+msg);
  }

  //
  // Stop the applet.
  // Main applet thread will terminate on first exception
  // after seeing that rfbThread has been set to null
  //


public void stop() {
    System.out.println("Stopping applet");
    try{
        if (rfb != null && !rfb.closed())
            rfb.close();
    }catch(NullPointerException npe){
       	System.err.println("Problem closing RFB");
    }

    vc = null;
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

 //   if (!inAnApplet) {
      System.exit(0);
  //  }
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

}
