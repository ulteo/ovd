//
//  Copyright (C) 2009 Ulteo SAS.  All Rights Reserved.
//  Copyright (C) 2001-2004 HorizonLive.com, Inc.  All Rights Reserved.
//  Copyright (C) 2001-2006 Constantin Kaplinsky.  All Rights Reserved.
//  Copyright (C) 2000 Tridia Corporation.  All Rights Reserved.
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

package org.vnc;

//
// RfbProto.java
//

import java.awt.event.InputEvent;
import java.awt.event.KeyEvent;
import java.awt.event.MouseEvent;
import java.awt.event.MouseWheelEvent;
import java.io.BufferedInputStream;
import java.io.DataInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.Socket;
import java.util.Vector;
import java.util.zip.Deflater;

import org.vnc.rfbcaching.IRfbCache;
import org.vnc.rfbcaching.IRfbCacheFactory;
import org.vnc.rfbcaching.IRfbCachingConstants;
import org.vnc.rfbcaching.RfbCacheEntry;
import org.vnc.rfbcaching.RfbCacheFactory;
import org.vnc.rfbcaching.RfbCacheProperties;

public class RfbProto {

	public final static String
    versionMsg_3_3 = "RFB 003.003\n",
    versionMsg_3_7 = "RFB 003.007\n",
    versionMsg_3_8 = "RFB 003.008\n";

  // Vendor signatures: standard VNC/RealVNC, TridiaVNC, and TightVNC
	public final static String
    StandardVendor  = "STDV",
    TridiaVncVendor = "TRDV",
    TightVncVendor  = "TGHT";

  // Security types
	public final static int
    SecTypeInvalid = 0,
    SecTypeNone    = 1,
    SecTypeVncAuth = 2,
    SecTypeTight   = 16;

  // Supported tunneling types
	public final static int
    NoTunneling = 0;
	public final static String
    SigNoTunneling = "NOTUNNEL";

  // Supported authentication types
	public final static int
    AuthNone      = 1,
    AuthVNC       = 2,
    AuthUnixLogin = 129;
	public final static String
    SigAuthNone      = "NOAUTH__",
    SigAuthVNC       = "VNCAUTH_",
    SigAuthUnixLogin = "ULGNAUTH";

  // VNC authentication results
	public final static int
    VncAuthOK      = 0,
    VncAuthFailed  = 1,
    VncAuthTooMany = 2;

  // Server-to-client messages
	public final static int
    FramebufferUpdate   = 0,
    SetColourMapEntries = 1,
    Bell                = 2,
    ServerCutText       = 3;

  // Client-to-server messages
	public final static int
    SetPixelFormat           = 0,
    FixColourMapEntries      = 1,
    SetEncodings             = 2,
    FramebufferUpdateRequest = 3,
    KeyboardEvent            = 4,
    PointerEvent             = 5,
    ClientCutText            = 6;

  // Supported encodings and pseudo-encodings
	public final static int
    EncodingRaw            = 0,
    EncodingCopyRect       = 1,
    EncodingRRE            = 2,
    EncodingCoRRE          = 4,
    EncodingHextile        = 5,
    EncodingZlib           = 6,
    EncodingTight          = 7,
    EncodingZRLE           = 16,
    EncodingCompressLevel0 = 0xFFFFFF00,
    EncodingQualityLevel0  = 0xFFFFFFE0,
    EncodingXCursor        = 0xFFFFFF10,
    EncodingRichCursor     = 0xFFFFFF11,
    EncodingPointerPos     = 0xFFFFFF18,
    EncodingLastRect       = 0xFFFFFF20,
    EncodingNewFBSize      = 0xFFFFFF21;

public final static int EncodingRfbCaching	   = IRfbCachingConstants.RFB_CACHE_ENCODING;

public final static String
    SigEncodingRaw            = "RAW_____",
    SigEncodingCopyRect       = "COPYRECT",
    SigEncodingRRE            = "RRE_____",
    SigEncodingCoRRE          = "CORRE___",
    SigEncodingHextile        = "HEXTILE_",
    SigEncodingZlib           = "ZLIB____",
    SigEncodingTight          = "TIGHT___",
    SigEncodingZRLE           = "ZRLE____",
    SigEncodingCompressLevel0 = "COMPRLVL",
    SigEncodingQualityLevel0  = "JPEGQLVL",
    SigEncodingXCursor        = "X11CURSR",
    SigEncodingRichCursor     = "RCHCURSR",
    SigEncodingPointerPos     = "POINTPOS",
    SigEncodingLastRect       = "LASTRECT",
    SigEncodingNewFBSize      = "NEWFBSIZ",
  	SigEncodingRfbCaching	  = "RFBCACHE";

public final static int MaxNormalEncoding = 255;

  // Contstants used in the Hextile decoder
public final static int
    HextileRaw                 = 1,
    HextileBackgroundSpecified = 2,
    HextileForegroundSpecified = 4,
    HextileAnySubrects         = 8,
    HextileSubrectsColoured    = 16;

  // Contstants used in the Tight decoder
public final static int TightMinToCompress = 12;
public final static int
    TightExplicitFilter = 0x04,
    TightFill           = 0x08,
    TightJpeg           = 0x09,
    TightMaxSubencoding = 0x09,
    TightFilterCopy     = 0x00,
    TightFilterPalette  = 0x01,
    TightFilterGradient = 0x02;


  public String host;
  public int port;
  public Socket sock;
  public DataInputStream is;
  public OutputStream os;
  public SessionRecorder rec;
  public boolean inNormalProtocol = false;

  // RFB Caching declaration section
  // Cache object
  public IRfbCache cache;
  // Cache key size in bytes
  public int cacheKeySize = 20;
  // Minimum data size of frame buffer to be cached
  public int cacheMinDataSize;
  // Cache size
  public int cacheMaxEntries;
  // Cache maintenance algorithm
  public int cacheMaintAlg;
  // Cache version
  public int cacheMajor, cacheMinor;
  // Reserved byte for RFB caching protocol extension
  public int cacheReserved;
  // RFB cache client preferred properties
  public RfbCacheProperties clientProps = null;

  public void setCacheProps(RfbCacheProperties cacheProps){
	  clientProps = cacheProps;
  }
  // RFB session cache support indicator
  boolean isServerSupportCaching;

  // Number of bytes to be cached for single frame buffer update request
  int numBytesCached;

//  // Getter
//  public int getNumBytesCached() {
//	return numBytesCached;
//  }
//
//  // Setter
//  public void setNumBytesCached(int numBytesCached) {
//	this.numBytesCached = numBytesCached;
//  }
//
  // The size of socket InputStream buffer to store bytes after mark() call
  int rfbCacheMarkReadLimit = 4800000;

  boolean isCaching = false;


  /*Ulteo changes by ArnauVP*/
  String OSName = "unknown";

  //Support for dead keys: the dead code is recorded, then the next
  // key pressed is modified to send the correct X keysym. Original
  // support for Spanish, may need slight modification for other keyboards
  // Correct case is detected with key typed events, in VncCanvas
  boolean deadKeyPressed = false;
  boolean deadKeyDown = false;
  int deadKeyChar = 0;

  // mustFake forces the fake of a key press when we only get a release
  boolean mustFake = false;


  // This will be set to true on the first framebuffer update
  // containing Zlib-, ZRLE- or Tight-encoded data.
  boolean wereZlibUpdates = false;

  // This will be set to false if the startSession() was called after
  // we have received at least one Zlib-, ZRLE- or Tight-encoded
  // framebuffer update.
  boolean recordFromBeginning = true;

  // This fields are needed to show warnings about inefficiently saved
  // sessions only once per each saved session file.
  boolean zlibWarningShown;
  boolean tightWarningShown;

  // Before starting to record each saved session, we set this field
  // to 0, and increment on each framebuffer update. We don't flush
  // the SessionRecorder data into the file before the second update.
  // This allows us to write initial framebuffer update with zero
  // timestamp, to let the player show initial desktop before
  // playback.
  public int numUpdatesInSession;

  // Measuring network throughput.
  public boolean timing;
  public long timeWaitedIn100us;
  public long timedKbits;

  // Protocol version and TightVNC-specific protocol options.
  public int serverMajor, serverMinor;
  public int clientMajor, clientMinor;
  public boolean protocolTightVNC;
  public CapsContainer tunnelCaps, authCaps;
  public CapsContainer serverMsgCaps, clientMsgCaps;
  public CapsContainer encodingCaps;

  // If true, informs that the RFB socket was closed.
  private boolean closed;

  private ClipboardManagement clip = null;

  //
  // Constructor. Make TCP connection to RFB server.
  //

  public RfbProto(String h, int p) throws IOException {
    host = h;
    port = p;

    sock = new Socket(host, port);
    is = new DataInputStream(new BufferedInputStream(sock.getInputStream(),
						     16384));
    os = sock.getOutputStream();

    timing = false;
    timeWaitedIn100us = 5;
    timedKbits = 0;

    initOSName();
  }

  public RfbProto(InputStream in, OutputStream out) /*throws IOException*/ {
	is = new DataInputStream(new BufferedInputStream(in, 16384));
	os = out;

    initOSName();
  }

  public synchronized void close() {
    try {
      is.close();
      os.close();
      if(sock != null){
    	 sock.close();
    	 sock=null;
      }
      closed = true;
      System.out.println("RFB socket closed");
      if (rec != null) {
	rec.close();
	rec = null;
      }
    } catch (Exception e) {
      e.printStackTrace();
    }
  }

  public synchronized boolean closed() {
    return closed;
  }


  //
  //OS detection --> better keyboard handling
  // Ulteo fix by ArnauVP
  //

  private void initOSName() {
    String OS = System.getProperty("os.name");
    if(OS.equalsIgnoreCase("Linux")){
      OSName = "linux";
    } else if(OS.startsWith("Windows")){
      OSName = "windows";
    } else if(OS.equalsIgnoreCase("Mac OS X")){
      OSName = "mac";
    } else {
      OSName = "unknown";
    }
  }


  //
  // Read server's protocol version message
  //

  public void readVersionMsg() throws Exception {

    byte[] b = new byte[12];

    readFully(b);

    if ((b[0] != 'R') || (b[1] != 'F') || (b[2] != 'B') || (b[3] != ' ')
	|| (b[4] < '0') || (b[4] > '9') || (b[5] < '0') || (b[5] > '9')
	|| (b[6] < '0') || (b[6] > '9') || (b[7] != '.')
	|| (b[8] < '0') || (b[8] > '9') || (b[9] < '0') || (b[9] > '9')
	|| (b[10] < '0') || (b[10] > '9') || (b[11] != '\n'))
    {
      throw new Exception("Host " + host + " port " + port +
			  " is not an RFB server");
    }

    serverMajor = (b[4] - '0') * 100 + (b[5] - '0') * 10 + (b[6] - '0');
    serverMinor = (b[8] - '0') * 100 + (b[9] - '0') * 10 + (b[10] - '0');

    if (serverMajor < 3) {
      throw new Exception("RFB server does not support protocol version 3");
    }
  }


  //
  // Write our protocol version message
  //

  public void writeVersionMsg() throws IOException {
    clientMajor = 3;
    if (serverMajor > 3 || serverMinor >= 8) {
      clientMinor = 8;
      os.write(versionMsg_3_8.getBytes());
    } else if (serverMinor >= 7) {
      clientMinor = 7;
      os.write(versionMsg_3_7.getBytes());
    } else {
      clientMinor = 3;
      os.write(versionMsg_3_3.getBytes());
    }
    protocolTightVNC = false;
  }


  //
  // Negotiate the authentication scheme.
  //

  public int negotiateSecurity() throws Exception {
    return (clientMinor >= 7) ?
      selectSecurityType() : readSecurityType();
  }

  //
  // Read security type from the server (protocol version 3.3).
  //

  int readSecurityType() throws Exception {
    int secType = is.readInt();

    switch (secType) {
    case SecTypeInvalid:
      readConnFailedReason();
      return SecTypeInvalid;	// should never be executed
    case SecTypeNone:
    case SecTypeVncAuth:
      return secType;
    default:
      throw new Exception("Unknown security type from RFB server: " + secType);
    }
  }

  //
  // Select security type from the server's list (protocol versions 3.7/3.8).
  //

  int selectSecurityType() throws Exception {
    int secType = SecTypeInvalid;

    // Read the list of secutiry types.
    int nSecTypes = is.readUnsignedByte();
    if (nSecTypes == 0) {
      readConnFailedReason();
      return SecTypeInvalid;	// should never be executed
    }
    byte[] secTypes = new byte[nSecTypes];
    readFully(secTypes);

    // Find out if the server supports TightVNC protocol extensions
    for (int i = 0; i < nSecTypes; i++) {
      if (secTypes[i] == SecTypeTight) {
	protocolTightVNC = true;
	os.write(SecTypeTight);
	return SecTypeTight;
      }
    }

    // Find first supported security type.
    for (int i = 0; i < nSecTypes; i++) {
      if (secTypes[i] == SecTypeNone || secTypes[i] == SecTypeVncAuth) {
	secType = secTypes[i];
	break;
      }
    }

    if (secType == SecTypeInvalid) {
      throw new Exception("Server did not offer supported security type");
    } else {
      os.write(secType);
    }

    return secType;
  }

  //
  // Perform "no authentication".
  //

  public void authenticateNone() throws Exception {
    if (clientMinor >= 8)
      readSecurityResult("No authentication");
  }

  //
  // Perform standard VNC Authentication.
  //

  public void authenticateVNC(String pw) throws Exception {
    byte[] challenge = new byte[16];
    readFully(challenge);

    if (pw.length() > 8)
      pw = pw.substring(0, 8);	// Truncate to 8 chars

    // Truncate password on the first zero byte.
    int firstZero = pw.indexOf(0);
    if (firstZero != -1)
      pw = pw.substring(0, firstZero);

    byte[] key = {0, 0, 0, 0, 0, 0, 0, 0};
    System.arraycopy(pw.getBytes(), 0, key, 0, pw.length());

    DesCipher des = new DesCipher(key);

    des.encrypt(challenge, 0, challenge, 0);
    des.encrypt(challenge, 8, challenge, 8);

    os.write(challenge);

    readSecurityResult("VNC authentication");
  }

  //
  // Read security result.
  // Throws an exception on authentication failure.
  //

  public void readSecurityResult(String authType) throws Exception {
    int securityResult = is.readInt();

    switch (securityResult) {
    case VncAuthOK:
      System.out.println(authType + ": success");
      break;
    case VncAuthFailed:
      if (clientMinor >= 8)
        readConnFailedReason();
      throw new Exception(authType + ": failed");
    case VncAuthTooMany:
      throw new Exception(authType + ": failed, too many tries");
    default:
      throw new Exception(authType + ": unknown result " + securityResult);
    }
  }

  //
  // Read the string describing the reason for a connection failure,
  // and throw an exception.
  //

  public void readConnFailedReason() throws Exception {
    int reasonLen = is.readInt();
    byte[] reason = new byte[reasonLen];
    readFully(reason);
    throw new Exception(new String(reason));
  }

  //
  // Initialize capability lists (TightVNC protocol extensions).
  //

  public void initCapabilities() {
    tunnelCaps    = new CapsContainer();
    authCaps      = new CapsContainer();
    serverMsgCaps = new CapsContainer();
    clientMsgCaps = new CapsContainer();
    encodingCaps  = new CapsContainer();

    // Supported authentication methods
    authCaps.add(AuthNone, StandardVendor, SigAuthNone,
		 "No authentication");
    authCaps.add(AuthVNC, StandardVendor, SigAuthVNC,
		 "Standard VNC password authentication");

    // Supported encoding types
    encodingCaps.add(EncodingCopyRect, StandardVendor,
		     SigEncodingCopyRect, "Standard CopyRect encoding");
    encodingCaps.add(EncodingRRE, StandardVendor,
		     SigEncodingRRE, "Standard RRE encoding");
    encodingCaps.add(EncodingCoRRE, StandardVendor,
		     SigEncodingCoRRE, "Standard CoRRE encoding");
    encodingCaps.add(EncodingHextile, StandardVendor,
		     SigEncodingHextile, "Standard Hextile encoding");
    encodingCaps.add(EncodingZRLE, StandardVendor,
		     SigEncodingZRLE, "Standard ZRLE encoding");
    encodingCaps.add(EncodingZlib, TridiaVncVendor,
		     SigEncodingZlib, "Zlib encoding");
    encodingCaps.add(EncodingTight, TightVncVendor,
		     SigEncodingTight, "Tight encoding");

    // Supported pseudo-encoding types
    encodingCaps.add(EncodingCompressLevel0, TightVncVendor,
		     SigEncodingCompressLevel0, "Compression level");
    encodingCaps.add(EncodingQualityLevel0, TightVncVendor,
		     SigEncodingQualityLevel0, "JPEG quality level");
    encodingCaps.add(EncodingXCursor, TightVncVendor,
		     SigEncodingXCursor, "X-style cursor shape update");
    encodingCaps.add(EncodingRichCursor, TightVncVendor,
		     SigEncodingRichCursor, "Rich-color cursor shape update");
    encodingCaps.add(EncodingPointerPos, TightVncVendor,
		     SigEncodingPointerPos, "Pointer position update");
    encodingCaps.add(EncodingLastRect, TightVncVendor,
		     SigEncodingLastRect, "LastRect protocol extension");
    encodingCaps.add(EncodingNewFBSize, TightVncVendor,
		     SigEncodingNewFBSize, "Framebuffer size change");
    encodingCaps.add(EncodingRfbCaching, TightVncVendor,
		     SigEncodingRfbCaching, "RFB Caching support");
  }

  //
  // Setup tunneling (TightVNC protocol extensions)
  //

  public void setupTunneling() throws IOException {
    int nTunnelTypes = is.readInt();
    if (nTunnelTypes != 0) {
      readCapabilityList(tunnelCaps, nTunnelTypes);

      // We don't support tunneling yet.
      writeInt(NoTunneling);
    }
  }

  //
  // Negotiate authentication scheme (TightVNC protocol extensions)
  //

  public int negotiateAuthenticationTight() throws Exception {
    int nAuthTypes = is.readInt();
    if (nAuthTypes == 0)
      return AuthNone;

    readCapabilityList(authCaps, nAuthTypes);
    for (int i = 0; i < authCaps.numEnabled(); i++) {
      int authType = authCaps.getByOrder(i);
      if (authType == AuthNone || authType == AuthVNC) {
	writeInt(authType);
	return authType;
      }
    }
    throw new Exception("No suitable authentication scheme found");
  }

  //
  // Read a capability list (TightVNC protocol extensions)
  //

  public void readCapabilityList(CapsContainer caps, int count) throws IOException {
    int code;
    byte[] vendor = new byte[4];
    byte[] name = new byte[8];
    for (int i = 0; i < count; i++) {
      code = is.readInt();
      readFully(vendor);
      readFully(name);
      caps.enable(new CapabilityInfo(code, vendor, name));
    }
  }

  //
  // Write a 32-bit integer into the output stream.
  //

  public void writeInt(int value) throws IOException {
    byte[] b = new byte[4];
    b[0] = (byte) ((value >> 24) & 0xff);
    b[1] = (byte) ((value >> 16) & 0xff);
    b[2] = (byte) ((value >> 8) & 0xff);
    b[3] = (byte) (value & 0xff);
    os.write(b);
  }

  //
  // Write the client initialisation message
  //

  public void writeClientInit() throws IOException {
    if (Options.shareDesktop) {
      os.write(1);
    } else {
      os.write(0);
    }
  }


  //
  // Write ClientCacheInit handshake message
  //
  public void writeClientCacheInit() throws IOException {
	  //System.out.println("RfbProto: WriteClientCacheInit");
	  if (clientProps == null){
	  	//System.out.println("RfbProto: WriteClientCacheInit, userCacheProperties null");
	  	return;
	  }
	  byte[] b = new byte[8];
	  b[0] = IRfbCachingConstants.RFB_CACHE_CLIENT_INIT_MSG;

	  boolean isVerMatch = (clientProps.getCacheMajor() == cacheMajor && clientProps.getCacheMinor()<= cacheMinor);

	  if (isVerMatch){
		  b[1] = (byte) ((byte)(clientProps.getCacheMajor() << 4)|(clientProps.getCacheMinor() & 0xf));
	  }
	  else
		  b[1] = 0; // Caching not supported due to version mismatch
	  cacheMaxEntries = Math.min(clientProps.getCacheMaxEntries(),cacheMaxEntries);
	  b[2] = (byte) ((cacheMaxEntries >> 8) & 0xff);

	  b[3] = (byte) ( cacheMaxEntries & 0xff);
	  cacheMaintAlg = clientProps.getCacheMaintAlg();
	  b[4] = (byte) ( cacheMaintAlg & 0xff);
	  b[5] = 0; //reserved byte
	  cacheMinDataSize = (clientProps.getCacheMinDataSize()>0)?clientProps.getCacheMinDataSize():cacheMinDataSize;
	  b[6] = (byte) ((cacheMinDataSize >> 8) & 0xff);
	  b[7] = (byte) (cacheMinDataSize & 0xff);
	  if (isVerMatch){
		  try{
			  // Possible usage:
			  // IRfbCacheFactory factory;
			  // switch(b[1]){
			  // 	case VER1:
			  //      factory = (IRfbCacheFactory)(Class.forName(Factory1_Name).newInstance();
			  //      break;
			  //    case VER2:
			  //      factory = (IRfbCacheFactory)(Class.forName(Factory2_Name).newInstance();
			  //      break;
			  //    default:
			  //	  factory = (IRfbCacheFactory)(Class.forName(IRfbCachingConstants.RFB_CACHE_DEFAULT_FACTORY)).newInstance();
			  // }
			  //
//			  IRfbCacheFactory factory = (IRfbCacheFactory)(Class.forName(IRfbCachingConstants.RFB_CACHE_DEFAULT_FACTORY)).newInstance();
			  IRfbCacheFactory factory = new RfbCacheFactory();
			  IRfbCache cache = factory.CreateRfbCache(
		      						new RfbCacheProperties(cacheMaintAlg,
		      											   cacheMaxEntries,
		      											   cacheMinDataSize,
		      											   clientProps.getCacheMajor(),
		      											   clientProps.getCacheMajor()));

		      if (cache!=null){
		    	  this.cache = cache;
		      }else{
		    	  this.isServerSupportCaching = false;
		    	  b[1] = 0;
		      }
//		      System.out.println("RFB Cache created:");
//		      System.out.println("\tcacheVersion:"+b[1]);
//		      System.out.println("\tcacheMaintAlg:"+cacheMaintAlg);
//		      System.out.println("\tcacheMinDataSize:"+cacheMinDataSize);
//		      System.out.println("\tcacheSize:"+cacheMaxEntries);
		  }catch(Exception e){
			  e.printStackTrace();
			  this.isServerSupportCaching = false;
			  b[1] = 0;
		  }
      }else{
      	  this.isServerSupportCaching = false;
//      	  System.out.println("Cache version mismatch");
      }
	  os.write(b);

  }

  //
  // Read ServerCacheInit handshake message
  //
  public void readServerCacheInit() throws IOException {
	  //System.out.println("RfbProto: ReadServerCacheInit");
	  int ver = is.readUnsignedByte();
	  cacheMajor =  (ver >> 4);
	  cacheMinor =  ver & 0xf ;
	  cacheMaxEntries = is.readUnsignedShort();
	  cacheMaintAlg = is.readUnsignedByte();
	  cacheReserved = is.readUnsignedByte();
	  cacheMinDataSize =  is.readUnsignedShort();
	  if ((clientProps!=null) && (clientProps.getCacheMajor() == cacheMajor) &&  (clientProps.getCacheMinor()<= cacheMinor)){
		  isServerSupportCaching = true;
	  }
  }

  //
  // Read the server initialisation message
  //

  public String desktopName;
  public int framebufferWidth, framebufferHeight;
  public int bitsPerPixel, depth;
  public boolean bigEndian, trueColour;
  public int redMax, greenMax, blueMax, redShift, greenShift, blueShift;

  public void readServerInit() throws IOException {
    framebufferWidth = is.readUnsignedShort();
    framebufferHeight = is.readUnsignedShort();
    bitsPerPixel = is.readUnsignedByte();
    depth = is.readUnsignedByte();
    bigEndian = (is.readUnsignedByte() != 0);
    trueColour = (is.readUnsignedByte() != 0);
    redMax = is.readUnsignedShort();
    greenMax = is.readUnsignedShort();
    blueMax = is.readUnsignedShort();
    redShift = is.readUnsignedByte();
    greenShift = is.readUnsignedByte();
    blueShift = is.readUnsignedByte();
    byte[] pad = new byte[3];
    readFully(pad);
    int nameLength = is.readInt();
    byte[] name = new byte[nameLength];
    readFully(name);
    desktopName = new String(name);

    // Read interaction capabilities (TightVNC protocol extensions)
    if (protocolTightVNC) {
      int nServerMessageTypes = is.readUnsignedShort();
      int nClientMessageTypes = is.readUnsignedShort();
      int nEncodingTypes = is.readUnsignedShort();
      is.readUnsignedShort();
      readCapabilityList(serverMsgCaps, nServerMessageTypes);
      readCapabilityList(clientMsgCaps, nClientMessageTypes);
      readCapabilityList(encodingCaps, nEncodingTypes);
    }

    inNormalProtocol = true;
  }


  //
  // Create session file and write initial protocol messages into it.
  //

  public void startSession(String fname) throws IOException {
    rec = new SessionRecorder(fname);
    rec.writeHeader();
    rec.write(versionMsg_3_3.getBytes());
    rec.writeIntBE(SecTypeNone);
    rec.writeShortBE(framebufferWidth);
    rec.writeShortBE(framebufferHeight);
    byte[] fbsServerInitMsg =	{
      32, 24, 0, 1, 0,
      (byte)0xFF, 0, (byte)0xFF, 0, (byte)0xFF,
      16, 8, 0, 0, 0, 0
    };
    rec.write(fbsServerInitMsg);
    rec.writeIntBE(desktopName.length());
    rec.write(desktopName.getBytes());
    numUpdatesInSession = 0;

    if (wereZlibUpdates)
      recordFromBeginning = false;

    zlibWarningShown = false;
    tightWarningShown = false;
  }

  //
  // Close session file.
  //

  public void closeSession() throws IOException {
    if (rec != null) {
      rec.close();
      rec = null;
    }
  }


  //
  // Set new framebuffer size
  //

  public void setFramebufferSize(int width, int height) {
    framebufferWidth = width;
    framebufferHeight = height;
  }


  //
  // Read the server message type
  //

  public int readServerMessageType() throws IOException {
    int msgType = is.readUnsignedByte();

    // If the session is being recorded:
    if (rec != null) {
      if (msgType == Bell) {	// Save Bell messages in session files.
	rec.writeByte(msgType);
	if (numUpdatesInSession > 0)
	  rec.flush();
      }
    }

    return msgType;
  }


  //
  // Read a FramebufferUpdate message
  //

  int updateNRects;

  public void readFramebufferUpdate() throws IOException {
    is.readByte();
    updateNRects = is.readUnsignedShort();

    // If the session is being recorded:
    if (rec != null) {
      rec.writeByte(FramebufferUpdate);
      rec.writeByte(0);
      rec.writeShortBE(updateNRects);
    }

    numUpdatesInSession++;
  }

  // Read a FramebufferUpdate rectangle header

  public int updateRectX, updateRectY, updateRectW, updateRectH, updateRectEncoding;

  public void readFramebufferUpdateRectHdr() throws Exception {
    updateRectX = is.readUnsignedShort();
    updateRectY = is.readUnsignedShort();
    updateRectW = is.readUnsignedShort();
    updateRectH = is.readUnsignedShort();
    updateRectEncoding = is.readInt();

    if (updateRectEncoding == EncodingZlib ||
        updateRectEncoding == EncodingZRLE ||
	updateRectEncoding == EncodingTight)
      wereZlibUpdates = true;

    // If the session is being recorded:
    if (rec != null) {
      if (numUpdatesInSession > 1)
	rec.flush();		// Flush the output on each rectangle.
      rec.writeShortBE(updateRectX);
      rec.writeShortBE(updateRectY);
      rec.writeShortBE(updateRectW);
      rec.writeShortBE(updateRectH);
      if (updateRectEncoding == EncodingZlib && !recordFromBeginning) {
	// Here we cannot write Zlib-encoded rectangles because the
	// decoder won't be able to reproduce zlib stream state.
	if (!zlibWarningShown) {
	  System.out.println("Warning: Raw encoding will be used " +
			     "instead of Zlib in recorded session.");
	  zlibWarningShown = true;
	}
	rec.writeIntBE(EncodingRaw);
      } else {
	rec.writeIntBE(updateRectEncoding);
	if (updateRectEncoding == EncodingTight && !recordFromBeginning &&
	    !tightWarningShown) {
	  System.out.println("Warning: Re-compressing Tight-encoded " +
			     "updates for session recording.");
	  tightWarningShown = true;
	}
      }
    }

    if (updateRectEncoding < 0 || updateRectEncoding > MaxNormalEncoding)
      return;

    if (updateRectX + updateRectW > framebufferWidth ||
	updateRectY + updateRectH > framebufferHeight) {
      throw new Exception("Framebuffer update rectangle too large: " +
			  updateRectW + "x" + updateRectH + " at (" +
			  updateRectX + "," + updateRectY + ")");
    }
  }

  // Read CopyRect source X and Y.

  int copyRectSrcX, copyRectSrcY;

  void readCopyRect() throws IOException {
    copyRectSrcX = is.readUnsignedShort();
    copyRectSrcY = is.readUnsignedShort();

    // If the session is being recorded:
    if (rec != null) {
      rec.writeShortBE(copyRectSrcX);
      rec.writeShortBE(copyRectSrcY);
    }
  }


  //
  // Read a ServerCutText message
  //

  public void readServerCutText() throws IOException {

    StringBuffer buffer = new StringBuffer();

    int j=0;
    is.skipBytes(3);
    int len = is.readInt();
//    System.out.println("readCut, len: "+len);
    while (j<len){
    int ch = is.read();
//    System.out.println("readCut, readInt: "+ ch);
//    System.out.println("readCut, translation: "+UnicodeToKeysym.inverseTranslate(ch));
    buffer.append((char)UnicodeToKeysym.inverseTranslate(ch));
	j++;
    }
//    System.out.println("readCut, result: "+buffer.toString());
    if (this.clip != null)
      this.clip.recv(buffer.toString());
  }


  //
  // Read an integer in compact representation (1..3 bytes).
  // Such format is used as a part of the Tight encoding.
  // Also, this method records data if session recording is active and
  // the viewer's recordFromBeginning variable is set to true.
  //

  int readCompactLen() throws IOException {
    int[] portion = new int[3];
    portion[0] = is.readUnsignedByte();
    int byteCount = 1;
    int len = portion[0] & 0x7F;
    if ((portion[0] & 0x80) != 0) {
      portion[1] = is.readUnsignedByte();
      byteCount++;
      len |= (portion[1] & 0x7F) << 7;
      if ((portion[1] & 0x80) != 0) {
	portion[2] = is.readUnsignedByte();
	byteCount++;
	len |= (portion[2] & 0xFF) << 14;
      }
    }

    if (rec != null && recordFromBeginning)
      for (int i = 0; i < byteCount; i++)
	rec.writeByte(portion[i]);
    if (isCaching) numBytesCached+=byteCount;
    return len;
  }


  //
  // Write a FramebufferUpdateRequest message
  //

  public void writeFramebufferUpdateRequest(int x, int y, int w, int h,
				     boolean incremental)
       throws IOException
  {
    byte[] b = new byte[10];

    b[0] = (byte) FramebufferUpdateRequest;
    b[1] = (byte) (incremental ? 1 : 0);
    b[2] = (byte) ((x >> 8) & 0xff);
    b[3] = (byte) (x & 0xff);
    b[4] = (byte) ((y >> 8) & 0xff);
    b[5] = (byte) (y & 0xff);
    b[6] = (byte) ((w >> 8) & 0xff);
    b[7] = (byte) (w & 0xff);
    b[8] = (byte) ((h >> 8) & 0xff);
    b[9] = (byte) (h & 0xff);

    os.write(b);
  }


  //
  // Write a SetPixelFormat message
  //

  void writeSetPixelFormat(int bitsPerPixel, int depth, boolean bigEndian,
			   boolean trueColour,
			   int redMax, int greenMax, int blueMax,
			   int redShift, int greenShift, int blueShift)
       throws IOException
  {
    byte[] b = new byte[20];

    b[0]  = (byte) SetPixelFormat;
    b[4]  = (byte) bitsPerPixel;
    b[5]  = (byte) depth;
    b[6]  = (byte) (bigEndian ? 1 : 0);
    b[7]  = (byte) (trueColour ? 1 : 0);
    b[8]  = (byte) ((redMax >> 8) & 0xff);
    b[9]  = (byte) (redMax & 0xff);
    b[10] = (byte) ((greenMax >> 8) & 0xff);
    b[11] = (byte) (greenMax & 0xff);
    b[12] = (byte) ((blueMax >> 8) & 0xff);
    b[13] = (byte) (blueMax & 0xff);
    b[14] = (byte) redShift;
    b[15] = (byte) greenShift;
    b[16] = (byte) blueShift;

    os.write(b);
  }


  //
  // Write a FixColourMapEntries message.  The values in the red, green and
  // blue arrays are from 0 to 65535.
  //

  void writeFixColourMapEntries(int firstColour, int nColours,
				int[] red, int[] green, int[] blue)
       throws IOException
  {
    byte[] b = new byte[6 + nColours * 6];

    b[0] = (byte) FixColourMapEntries;
    b[2] = (byte) ((firstColour >> 8) & 0xff);
    b[3] = (byte) (firstColour & 0xff);
    b[4] = (byte) ((nColours >> 8) & 0xff);
    b[5] = (byte) (nColours & 0xff);

    for (int i = 0; i < nColours; i++) {
      b[6 + i * 6]     = (byte) ((red[i] >> 8) & 0xff);
      b[6 + i * 6 + 1] = (byte) (red[i] & 0xff);
      b[6 + i * 6 + 2] = (byte) ((green[i] >> 8) & 0xff);
      b[6 + i * 6 + 3] = (byte) (green[i] & 0xff);
      b[6 + i * 6 + 4] = (byte) ((blue[i] >> 8) & 0xff);
      b[6 + i * 6 + 5] = (byte) (blue[i] & 0xff);
    }

    os.write(b);
  }


  //
  // Write a SetEncodings message
  //

  public void writeSetEncodings(int[] encs, int len) throws IOException {
    byte[] b = new byte[4 + 4 * len];

    b[0] = (byte) SetEncodings;
    b[2] = (byte) ((len >> 8) & 0xff);
    b[3] = (byte) (len & 0xff);

    for (int i = 0; i < len; i++) {
      b[4 + 4 * i] = (byte) ((encs[i] >> 24) & 0xff);
      b[5 + 4 * i] = (byte) ((encs[i] >> 16) & 0xff);
      b[6 + 4 * i] = (byte) ((encs[i] >> 8) & 0xff);
      b[7 + 4 * i] = (byte) (encs[i] & 0xff);
    }

    os.write(b);
  }

  public void writeClientCutText(String text) throws IOException {
	   int len = text.length();
	   char keyChar;
	   char translated;
	   Vector<Byte> processedBytes = new Vector<Byte>();

	   for (int i=0; i<len; i++) {
		   keyChar = text.charAt(i);
//		   System.out.println("writeCut, keyChar: "+keyChar+" ("+new Integer(keyChar).intValue()+")");
		   translated = (char) UnicodeToKeysym.translate(keyChar);
//		   System.out.println("writeCut, translated: "+translated+" ("+new Integer(translated).intValue()+")");

		   if(translated < 256){ // single Byte (ASCII)
			   byte tmp = (byte) (translated & 0xff);
			   processedBytes.add(new Byte(tmp));
		   }else{
			   //Send a '?' until we solve the issue server-side
			   byte tmp = (byte) (63 & 0xff);
			   processedBytes.add(new Byte(tmp));

			   /*This is how it should work if the server accepted UTF bytes*/
//			   String tmpString = new String(""+translated);
//			   byte[] tmpArray = tmpString.getBytes("UTF-8");
//			   for(int k=0; k<tmpArray.length; k++){
//				   processedBytes.add(new Byte(tmpArray[k]));
//			   }
		   }
	   }

	   int byteLen = processedBytes.size();
	   byte[] b = new byte[8 + byteLen];
	   for(int k=0; k<processedBytes.size();k++){
		   b[8+k] = (processedBytes.get(k)).byteValue();
	   }


	   // once we have the number of bytes, allocate space and send it
//	   System.out.println("writeCut, byteLen: "+byteLen);

	   b[0] = (byte) ClientCutText;
	   b[4] = (byte) ((byteLen >> 24) & 0xff);
	   b[5] = (byte) ((byteLen >> 16) & 0xff);
	   b[6] = (byte) ((byteLen >> 8) & 0xff);
	   b[7] = (byte) (byteLen & 0xff);
	   os.write(b);


 }


  //
  // A buffer for putting pointer and keyboard events before being sent.  This
  // is to ensure that multiple RFB events generated from a single Java Event
  // will all be sent in a single network packet.  The maximum possible
  // length is 4 modifier down events, a single key event followed by 4
  // modifier up events i.e. 9 key events or 72 bytes.
  //

  byte[] eventBuf = new byte[72];
  int eventBufLen;


  // Useful shortcuts for modifier masks.

  final static int CTRL_MASK  = InputEvent.CTRL_MASK;
  final static int SHIFT_MASK = InputEvent.SHIFT_MASK;
  final static int META_MASK  = InputEvent.META_MASK;
  final static int ALT_MASK   = InputEvent.ALT_MASK;


  //
  // Write a pointer event message.  We may need to send modifier key events
  // around it to set the correct modifier state.
  //

  int pointerMask = 0;


  //Modified for Ulteo by ArnauVP

  synchronized void writePointerEvent(MouseEvent evt) throws IOException {
	  
	   int modifiers = evt.getModifiersEx();

	   int mask2 = 2;
	   int mask3 = 4;
	   if (Options.reverseMouseButtons2And3) {
	     mask2 = 4;
	     mask3 = 2;
	   }

	   // Note: For some reason, AWT does not set BUTTON1_MASK on left
	   // button presses. Here we think that it was the left button if
	   // modifiers do not include BUTTON2_MASK or BUTTON3_MASK.

	   if (evt.getID() == MouseEvent.MOUSE_PRESSED) {
	     if ((modifiers & (InputEvent.BUTTON1_DOWN_MASK | InputEvent.BUTTON2_DOWN_MASK | InputEvent.BUTTON3_DOWN_MASK)) == InputEvent.BUTTON2_DOWN_MASK){
	          pointerMask = mask2;
	     }
	     else if ((modifiers & (InputEvent.BUTTON1_DOWN_MASK | InputEvent.BUTTON2_DOWN_MASK | InputEvent.BUTTON3_DOWN_MASK)) == InputEvent.BUTTON3_DOWN_MASK){
	          pointerMask = mask3;
	     }
	     else if ((modifiers & (InputEvent.BUTTON1_DOWN_MASK | InputEvent.BUTTON2_DOWN_MASK | InputEvent.BUTTON3_DOWN_MASK)) == InputEvent.BUTTON1_DOWN_MASK){
	          pointerMask = 1;
	     }
	     else{
		return;
	     }
	   }
	   else if (evt.getID() == MouseEvent.MOUSE_RELEASED) {
	     pointerMask = 0;
	     modifiers &= ~InputEvent.ALT_DOWN_MASK;
	     modifiers &= ~InputEvent.META_DOWN_MASK;
	   }

	   eventBufLen = 0;
	   writeModifierKeyEvents(modifiers);
	   int x = evt.getX();
	   int y = evt.getY();

	   if (x < 0) {
	     x = 0;
	   }
	   if (y < 0) {
	     y = 0;

	   }

	   eventBuf[eventBufLen++] = (byte) PointerEvent;
	   eventBuf[eventBufLen++] = (byte) pointerMask;
	   eventBuf[eventBufLen++] = (byte) ( (x >> 8) & 0xff);
	   eventBuf[eventBufLen++] = (byte) (x & 0xff);
	   eventBuf[eventBufLen++] = (byte) ( (y >> 8) & 0xff);
	   eventBuf[eventBufLen++] = (byte) (y & 0xff);


	   //
	   // Always release all modifiers after an "up" event
	   //

	   if (pointerMask == 0) {
	     writeModifierKeyEvents(0);
	   }

	   os.write(eventBuf, 0, eventBufLen);
	 }

	 // Scroll wheel events
	 void writePointerWheelEvent(MouseWheelEvent evt) throws IOException {
	   int totalScroll = 0;
	   int notches = evt.getWheelRotation();
	    // 1 notch = 3 lines in ConDesk
	   if(notches < 0){ //moving UP
		notches = -notches;
	 	pointerMask = 8;
	   }else { //moving DOWN
	 	pointerMask = 16;
	   }

	   //This way we can configure the scroll in our home OS
	   if(evt.getScrollType() == MouseWheelEvent.WHEEL_UNIT_SCROLL){
	     totalScroll = Math.round(evt.getScrollAmount()/3);
	     totalScroll = totalScroll*notches; //notches is normally 1
	   }else{ //block scroll
	     totalScroll = 6;
	     totalScroll = totalScroll*notches;
	   }
	   int x = evt.getX();
	   int y = evt.getY();
	   if (x < 0) {
	     x = 0;
	   }
	   if (y < 0) {
	     y = 0;
	   }

	   int i;
	   eventBufLen = 0;
	   writeModifierKeyEvents(evt.getModifiers());

	   for(i = 0; i < totalScroll; i++){
	    //Press
	    eventBuf[eventBufLen++] = (byte) PointerEvent;
	    eventBuf[eventBufLen++] = (byte) pointerMask;
	    eventBuf[eventBufLen++] = (byte) ( (x >> 8) & 0xff);
	    eventBuf[eventBufLen++] = (byte) (x & 0xff);
	    eventBuf[eventBufLen++] = (byte) ( (y >> 8) & 0xff);
	    eventBuf[eventBufLen++] = (byte) (y & 0xff);

	    //Release
	    eventBuf[eventBufLen++] = (byte) PointerEvent;
	    eventBuf[eventBufLen++] = (byte) 0;
	    eventBuf[eventBufLen++] = (byte) ( (x >> 8) & 0xff);
	    eventBuf[eventBufLen++] = (byte) (x & 0xff);
	    eventBuf[eventBufLen++] = (byte) ( (y >> 8) & 0xff);
	    eventBuf[eventBufLen++] = (byte) (y & 0xff);
	   }
	    writeModifierKeyEvents(0); //we fake the release
	    os.write(eventBuf, 0, eventBufLen);

	 }

	// Firefox 3 on Linux systems don't trigger the keyPressed event
	// for tab key (\t), use a hack instead
	private boolean tabKeyPressed = false;

  //
  // Write a key event message.  We may need to send modifier key events
  // around it to set the correct modifier state.  Also we need to translate
  // from the Java key values to the X keysym values used by the RFB protocol.
  //

  public void writeKeyEvent(KeyEvent evt) throws IOException{

		 int keyCode, keysym, keyChar, modifiersMask;

		    keysym = 0;
		    keyCode = evt.getKeyCode();
		    keyChar = evt.getKeyChar();
		    modifiersMask = evt.getModifiersEx();
		    boolean down = (evt.getID() == KeyEvent.KEY_PRESSED);
		    boolean typed = (evt.getID() == KeyEvent.KEY_TYPED);
//		    System.out.println("WriteKeyEvent: keyCode: "+keyCode+" and keyChar: "+keyChar+"\n");
			int translatedKeysym = translateActionKey(keyCode);

			// Firefox 3 on Linux systems don't trigger the keyPressed event
			// for tab key (\t), use a hack instead			
			// hack begin
			if (keyCode == evt.VK_TAB) {
				if (evt.getID() == KeyEvent.KEY_RELEASED && ! this.tabKeyPressed) {
					KeyEvent evt2 = new KeyEvent(evt.getComponent(), KeyEvent.KEY_PRESSED, evt.getWhen(), evt.getModifiers(), 
												 evt.getKeyCode(), evt.getKeyChar(), evt.getKeyLocation());
					this.writeKeyEvent(evt2);
				}
				
				if (evt.getID() == KeyEvent.KEY_PRESSED)
					this.tabKeyPressed = true;
				else if (evt.getID() == KeyEvent.KEY_RELEASED)
					this.tabKeyPressed = false;
			}
			// hack end

		    if(OSName.equals("windows")){

		    	// Two dead keys in a row get typed as the symbol alone
		    	if(deadKeyPressed && down && (keyChar == deadKeyChar)){
		    		typed = true;
		    	}

		    	if(typed) {
		    		if(keyChar == 9){
		    			keysym = Keysyms.Tab;
		    		}else if(keyChar != 10 && keyChar != 8){
		    			if(deadKeyPressed){
		    				keysym = UnicodeToKeysym.deadTranslate(keyChar,deadKeyChar);
		    				deadKeyPressed = false;
		    				deadKeyChar = 0;
		    				if(keysym < 0)	return; //In case of bad Translation
		    			}else{
		    				keysym = UnicodeToKeysym.translate(keyChar);
		    			}
		    		} else {
		    			return;
		    		}
		    		if(!evt.isActionKey() && evt.isControlDown() && keyChar < 0x20){ //control+letter
		    			keysym = keyChar + 0x60;
		    		}

		    	} else if (translatedKeysym>=0) {
		    		keysym = translatedKeysym;
		    	} else if (keyCode == 127) {
		    		keysym = Keysyms.Delete;
		    	} else if (keyCode == 8) {
		    		keysym = Keysyms.BackSpace;
		    	} else if (keyCode == 10) {
		    		keysym = Keysyms.Return;
		    	}else {
		    		if((keyChar == 96 || //grave
		    		   keyChar == 180 || //acute
		    		   keyChar == 94 || //circonflexe
		    		   keyChar == 126 || //tilde
		    		   (keyChar == 39 && keyCode == 129) || //acute in us intl
		    		   (keyChar == 34 && keyCode == 129) || //tréma in us intl
		    		   keyChar == 168) &&//tréma
		    		   down){
		    			deadKeyPressed = true;
		    			deadKeyChar = keyChar;
		    		}
		    		return;
		    	}

		    	//Write AltGr for Control+Alt -- (always?)
		    	if(modifiersMask == 640){
		    		modifiersMask = 8192;
		    	}
		    }

		    if(OSName.equals("linux")){

		    	// return key pressed and key released events if we already
		    	// have the key typed one.
		    	if(typed) {
		    		if(keyChar != 10 && keyChar != 9 && keyChar != 8){
		    			keysym = UnicodeToKeysym.translate(keyChar);
		    		} else {
		    			return;
		    		}
		    		if(!evt.isActionKey() && keyChar < 0x20 && evt.isControlDown()){ //control+letter
		    			keysym = keyChar + 0x60;
				        if (keysym == 104) keysym = Keysyms.BackSpace;
		    		}
		    	} else if (translatedKeysym>=0) {
		    		keysym = translatedKeysym;
		    	} else if (keyCode == 127) {
		    		keysym = Keysyms.Delete;
		    	} else if (keyCode == 8) {
		    		keysym = Keysyms.BackSpace;
		    	}
		    	//Tab and return from normal press/release, otherwise
		    	//they'll work in kate but not in OpenOffice
		    	else if (keyCode == 10) {
		    		keysym = Keysyms.Return;
		    	} else if (keyCode == 9) {
		    		keysym = Keysyms.Tab;
		    	} else {
		    		return;
		    	}
		    }

		    if(OSName.equals("mac")){

		    	//Mostly same as linux: use key typed events
		      	if(typed) {
		    		if(keyChar != 10 && keyChar != 9){
		    			keysym = UnicodeToKeysym.translate(keyChar);
		    		} else {
		    			return;
		    		}
		    		if(!evt.isActionKey() && keyChar < 0x20){ //control+letter
		    			keysym = keyChar + 0x60;
				        if (keysym == 104) keysym = Keysyms.BackSpace;
		    		}


		    	} else if (translatedKeysym>=0) {
		    		keysym = translatedKeysym;
		    	} else if (keyCode == 127) {
		    		keysym = Keysyms.Delete;
		    	} else if (keyCode == 10) {
		    		keysym = Keysyms.Return;
		    	} else if (keyCode == 9) {
		    		keysym = Keysyms.Tab;
		    	} else {
		    		return;
		    	}

		      	//Control + arrows doesn't work, we need press & release.
		      	if((keysym > 65360) && (keysym < 65365) && modifiersMask == 128)
		      		mustFake = true;

		    	//Write AltGr for Mac symbols made with Alt or Alt+shift
		    	if((modifiersMask == 512 || modifiersMask == 576) &&
		    	 (keysym < 97 || keysym > 122 )){
		    		modifiersMask = 8192;
		    	}
		    }


			// Fake press and release for key typed events
		    if(typed || mustFake){
		    	eventBufLen = 0;
//			    System.out.println("Writing key typed press "+keysym+ " with modifiers "+modifiersMask);
			    writeModifierKeyEvents(modifiersMask);
			    writeKeyEvent(keysym, true);
//			    System.out.println("Writing key typed release "+keysym+ " with modifiers 0");
			    writeKeyEvent(keysym, false);
			    writeModifierKeyEvents(0);
			    os.write(eventBuf, 0, eventBufLen);
			    if(mustFake)	mustFake = false;
		    }else{
		    eventBufLen = 0;
//		    System.out.println("Writing "+keysym+ " with modifiers "+modifiersMask);
		    writeModifierKeyEvents(modifiersMask);
		    writeKeyEvent(keysym, down);
		    if(!down)	writeModifierKeyEvents(0);
		    os.write(eventBuf, 0, eventBufLen);
		    }
	 }

  int translateActionKey(int keyCode){
		 switch (keyCode) {
	     case KeyEvent.VK_HOME:         return Keysyms.Home;
	     case KeyEvent.VK_END:          return Keysyms.End;
	     case KeyEvent.VK_PAGE_UP:      return Keysyms.Page_Up;
	     case KeyEvent.VK_PAGE_DOWN:    return Keysyms.Page_Down;
	     case KeyEvent.VK_UP:           return Keysyms.Up;
	     case KeyEvent.VK_DOWN:         return Keysyms.Down;
	     case KeyEvent.VK_LEFT:         return Keysyms.Left;
	     case KeyEvent.VK_RIGHT:        return Keysyms.Right;
	     case KeyEvent.VK_F1:           return Keysyms.F1;
	     case KeyEvent.VK_F2:           return Keysyms.F2;
	     case KeyEvent.VK_F3:           return Keysyms.F3;
	     case KeyEvent.VK_F4:           return Keysyms.F4;
	     case KeyEvent.VK_F5:           return Keysyms.F5;
	     case KeyEvent.VK_F6:           return Keysyms.F6;
	     case KeyEvent.VK_F7:           return Keysyms.F7;
	     case KeyEvent.VK_F8:           return Keysyms.F8;
	     case KeyEvent.VK_F9:           return Keysyms.F9;
	     case KeyEvent.VK_F10:          return Keysyms.F10;
	     case KeyEvent.VK_F11:          return Keysyms.F11;
	     case KeyEvent.VK_F12:          return Keysyms.F12;
	     case KeyEvent.VK_PRINTSCREEN:  return Keysyms.Print;
	     case KeyEvent.VK_PAUSE:        return Keysyms.Pause;
	     case KeyEvent.VK_INSERT:       return Keysyms.Insert;
	     case KeyEvent.VK_ESCAPE:		return Keysyms.Escape;
	     default: return -1;
	     }
	 }


  //
  // Add a raw key event with the given X keysym to eventBuf.
  //

  void writeKeyEvent(int keysym, boolean down) {
    eventBuf[eventBufLen++] = (byte) KeyboardEvent;
    eventBuf[eventBufLen++] = (byte) (down ? 1 : 0);
    eventBuf[eventBufLen++] = (byte) 0;
    eventBuf[eventBufLen++] = (byte) 0;
    eventBuf[eventBufLen++] = (byte) ((keysym >> 24) & 0xff);
    eventBuf[eventBufLen++] = (byte) ((keysym >> 16) & 0xff);
    eventBuf[eventBufLen++] = (byte) ((keysym >> 8) & 0xff);
    eventBuf[eventBufLen++] = (byte) (keysym & 0xff);
  }


  //
  // Write key events to set the correct modifier state.
  //

  int oldModifiers = 0;

  void writeModifierKeyEvents(int newModifiers) {

	   if ( (newModifiers & InputEvent.CTRL_DOWN_MASK) != (oldModifiers & InputEvent.CTRL_DOWN_MASK)) {
	     writeKeyEvent(Keysyms.Control_L, (newModifiers & InputEvent.CTRL_DOWN_MASK) != 0);
	   }
	   if ( (newModifiers & InputEvent.SHIFT_DOWN_MASK) != (oldModifiers & InputEvent.SHIFT_DOWN_MASK)) {
	     writeKeyEvent(Keysyms.Shift_L, (newModifiers & InputEvent.SHIFT_DOWN_MASK) != 0);
	   }
	   if ( (newModifiers & InputEvent.META_DOWN_MASK) != (oldModifiers & InputEvent.META_DOWN_MASK)) {
	     writeKeyEvent(Keysyms.Meta_L, (newModifiers & KeyEvent.META_DOWN_MASK) != 0);

	   }
	   if ( (newModifiers & KeyEvent.ALT_DOWN_MASK) != (oldModifiers & KeyEvent.ALT_DOWN_MASK)) {
	     writeKeyEvent(Keysyms.Alt_L, (newModifiers & KeyEvent.ALT_DOWN_MASK) != 0);
	   }
	   oldModifiers = newModifiers;
	 }



  //
  // Compress and write the data into the recorded session file. This
  // method assumes the recording is on (rec != null).
  //

  void recordCompressedData(byte[] data, int off, int len) throws IOException {
    Deflater deflater = new Deflater();
    deflater.setInput(data, off, len);
    int bufSize = len + len / 100 + 12;
    byte[] buf = new byte[bufSize];
    deflater.finish();
    int compressedSize = deflater.deflate(buf);
    recordCompactLen(compressedSize);
    rec.write(buf, 0, compressedSize);
  }

  void recordCompressedData(byte[] data) throws IOException {
    recordCompressedData(data, 0, data.length);
  }

  //
  // Write an integer in compact representation (1..3 bytes) into the
  // recorded session file. This method assumes the recording is on
  // (rec != null).
  //

  void recordCompactLen(int len) throws IOException {
    byte[] buf = new byte[3];
    int bytes = 0;
    buf[bytes++] = (byte)(len & 0x7F);
    if (len > 0x7F) {
      buf[bytes-1] |= 0x80;
      buf[bytes++] = (byte)(len >> 7 & 0x7F);
      if (len > 0x3FFF) {
	buf[bytes-1] |= 0x80;
	buf[bytes++] = (byte)(len >> 14 & 0xFF);
      }
    }
    rec.write(buf, 0, bytes);
  }

  public void startTiming() {
    timing = true;

    // Carry over up to 1s worth of previous rate for smoothing.

    if (timeWaitedIn100us > 10000) {
      timedKbits = timedKbits * 10000 / timeWaitedIn100us;
      timeWaitedIn100us = 10000;
    }
  }

  public void stopTiming() {
    timing = false;
    if (timeWaitedIn100us < timedKbits/2)
      timeWaitedIn100us = timedKbits/2; // upper limit 20Mbit/s
  }

  public long kbitsPerSecond() {
    return timedKbits * 10000 / timeWaitedIn100us;
  }

  public long timeWaited() {
    return timeWaitedIn100us;
  }

  public void readFully(byte b[]) throws IOException {
    readFully(b, 0, b.length);
  }

  public void readFully(byte b[], int off, int len) throws IOException {
    long before = 0;
    if (timing)
      before = System.currentTimeMillis();

    is.readFully(b, off, len);

    if (timing) {
      long after = System.currentTimeMillis();
      long newTimeWaited = (after - before) * 10;
      int newKbits = len * 8 / 1000;

      // limit rate to between 10kbit/s and 40Mbit/s

      if (newTimeWaited > newKbits*1000) newTimeWaited = newKbits*1000;
      if (newTimeWaited < newKbits/4)    newTimeWaited = newKbits/4;

      timeWaitedIn100us += newTimeWaited;
      timedKbits += newKbits;
    }
  }

  public void setClipBoard(ClipboardManagement clip) {
    this.clip = clip;
    this.clip.setRfbProto(this);
  }


//
// Start caching process
//
public void startCaching() /*throws IOException*/{
	//mark the current position in the socket stream
	this.is.mark(rfbCacheMarkReadLimit);
	numBytesCached = 0;
	isCaching = true;
}


//
// Reset caching variables
//
public void resetCaching() /*throws IOException*/{
	numBytesCached = 0;
	isCaching = false;
}

//
// Stop caching process
//
public void stopCaching(int encoding) throws IOException{
	// check if buffer size is large enough to be cached
	if (numBytesCached >= cacheMinDataSize){
		byte[] data = new byte[numBytesCached];
		// move InputStream pointer to the marked position
		is.reset();
		// read data from the InputStream buffer
		is.readFully(data, 0, data.length);
		cache.put(cache.hash(data), new RfbCacheEntry (encoding, data));
	}
	// reset cache variables
	resetCaching();
 }
}
