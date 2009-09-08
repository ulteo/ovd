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

package org.vnc;

import java.awt.Container;
import java.awt.Cursor;
import java.awt.Image;
import java.awt.Point;
import java.awt.Toolkit;
import java.awt.image.BufferedImage;
import java.io.EOFException;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.ConnectException;
import java.net.NoRouteToHostException;
import java.net.UnknownHostException;

import org.vnc.rfbcaching.RfbCacheProperties;


public class VncClient implements Runnable{
	public RfbProto rfb;
	public Thread rfbThread;
	protected int authType;
	public VncCanvas2 vc;
	public Container container;

	protected InputStream in;
	protected OutputStream out;

	protected Dialog dinterface;

	public VncClient(Dialog dinterface, Container container) {
		this.rfb = null;
		this.rfbThread = null;
		this.dinterface = dinterface;
		this.container = container;

		this.in = null;
		this.out = null;
	}


	public void initCanva(int width, int height) {
		try {
			vc = new VncCanvas2(this, width, height);
		} catch(Exception e) {
			e.printStackTrace();
		}
		this.container.add(this.vc);

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
 	}


	public void start_background_process() {
		rfbThread = new Thread(this);
		rfbThread.start();
	}

	public void stop_background_process() {
		if (rfbThread == null || !rfbThread.isAlive()) {
			System.out.println("stop_background_process(): not alive");
			return;
		}

		System.out.println("stop_background_process(): alive");
		rfbThread.interrupt();

		// Wait a moment to give a chance of
		// the thread to exit properly
		try{
			Thread.currentThread().sleep(2000);
		}
		catch(java.lang.InterruptedException ie){}

		if (rfbThread.isAlive()) {
			System.out.println("stop_background_process(): alive even after interrupt -> kill");
			rfbThread.stop();
		}
	}

	public void stop() {
		stop_background_process();

		if (this.vc!= null && this.vc.getParent() != null) {
			System.out.println("Remove canva from container");
			this.vc.getParent().remove(this.vc);
		}

		if (isConnected())
			disconnect();

	}


	// run() - executed by the rfbThread to deal with the RFB socket.
	//
	//
	// Process RFB socket messages.
	// If the rfbThread is being stopped, ignore any exceptions,
	// otherwise rethrow the exception so it can be handled.
	//

	public void run() {
		System.out.println("debug 1_1");

		try {
			vc.processNormalProtocol();
			System.out.println("debug 1_2");
		}
		catch (NoRouteToHostException e) {
			System.err.println("Network error: no route to server: " + Options.host+ " => " + e);
		}
		catch (UnknownHostException e) {
			System.err.println("Network error: server name unknown: " + Options.host+ " => " + e);
		}
		// How it's possible, we already connected !!
		catch (ConnectException e) {
			System.err.println("Network error: could not connect to server: " +
							   Options.host + ":" + Options.port+ " => " + e);
		}
		catch (EOFException e) {
			System.out.println("Network error: remote side closed connection");
			this.dinterface.vncSetError("vala");
		}
		catch (IOException e) {
			System.err.println("Network Error: " + e.getMessage());
		}
		catch (Exception e) {
			System.err.println("Error: " + e.getMessage());
		}
		
		disconnect();
		return;
	}


	public void setInOut(InputStream in, OutputStream out) {
		this.in = in;
		this.out = out;
	}

	public boolean connect() {
		int secType;

		try {
			if (this.in != null && this.out != null)
				rfb = new RfbProto(this.in, this.out);
			else
				rfb = new RfbProto(Options.host, Options.port);

			rfb.readVersionMsg();
			rfb.writeVersionMsg();

			secType = rfb.negotiateSecurity();

		} catch(IOException e) {
			System.err.println("Unable to connect to VNC server");
			return false;
		} catch(Exception e) {
			System.err.println("Another error");
			return false;
		}
	
		
		if (secType == RfbProto.SecTypeTight) {
			System.out.println("Enabling TightVNC protocol extensions");
			rfb.initCapabilities();
			try {
				rfb.setupTunneling();
				authType = rfb.negotiateAuthenticationTight();
			} catch(Exception e) {
				System.err.println("Another error 2");
				return false;
			}
		} else {
			authType = secType;
		}

		return true;
	}

	public boolean isConnected() {
		return rfb != null &&
			rfb.inNormalProtocol &&
			!rfb.closed();
	}

	//
	// disconnect() - close connection to server.
	//
	synchronized public void disconnect() {
		System.out.println("Disconnect");

		if (rfb != null && !rfb.closed())
			rfb.close();
	}

	public boolean authenticate() {
		switch (this.authType) {
		case RfbProto.AuthNone:
			System.out.println("No authentication needed");

			try {
				rfb.authenticateNone();
			} catch(Exception e) {
				System.err.println("Authentication failed");
				return false;
			}
			break;

		case RfbProto.AuthVNC:
			System.out.println("Performing standard VNC authentication");
			try {
				rfb.authenticateVNC(this.dinterface.vncGetPassword());
			} catch(Exception e) {
				System.err.println("Authentication failed");
				return false;
			}
			break;

		default:
			System.err.println("Unknown authentication scheme " + authType);
			return false;
		}

		return true;
	}

	//
	// Do the rest of the protocol initialisation.
	//
	public boolean init() {
		try {
			rfb.writeClientInit();
			rfb.readServerInit();
		} catch (IOException e) {
			System.err.println("Unable to perform the rfb initialisation ");
			return false;
		}

		if (Options.cacheEnable)
			setRfbCachingEncoding();

		System.out.println("debug 1_0");
		setEncodings();


		int width = rfb.framebufferWidth;
		int height = rfb.framebufferHeight;
		System.out.println("Desktop size is " + width + " x " + height);
		// if (width > Options.width
		// scale ...

		initCanva(width, height);

		return true;
	}




	//
	// Send current encoding list to the RFB server.
	//

	int[] encodingsSaved;
	int nEncodingsSaved;

	public void setEncodings()        { setEncodings(false); }
	public void autoSelectEncodings() { setEncodings(true); }

	public void setEncodings(boolean autoSelectOnly) {
		if (! isConnected())
			return;

		int[] encodings = new int[20];
		int nEncodings = 0;

		encodings[nEncodings++] = Options.preferredEncoding;
		if (Options.useCopyRect) {
			encodings[nEncodings++] = RfbProto.EncodingCopyRect;
		}

		if (Options.preferredEncoding != RfbProto.EncodingTight) {
			encodings[nEncodings++] = RfbProto.EncodingTight;
		}
		if (Options.preferredEncoding != RfbProto.EncodingZRLE) {
			encodings[nEncodings++] = RfbProto.EncodingZRLE;
		}
		if (Options.preferredEncoding != RfbProto.EncodingHextile) {
			encodings[nEncodings++] = RfbProto.EncodingHextile;
		}
		if (Options.preferredEncoding != RfbProto.EncodingZlib) {
			encodings[nEncodings++] = RfbProto.EncodingZlib;
		}
		if (Options.preferredEncoding != RfbProto.EncodingCoRRE) {
			encodings[nEncodings++] = RfbProto.EncodingCoRRE;
		}
		if (Options.preferredEncoding != RfbProto.EncodingRRE) {
			encodings[nEncodings++] = RfbProto.EncodingRRE;
		}

		if (Options.compressLevel >= 0 && Options.compressLevel <= 9) {
			encodings[nEncodings++] =
				RfbProto.EncodingCompressLevel0 + Options.compressLevel;
		}
		if (Options.jpegQuality >= 0 && Options.jpegQuality <= 9) {
			encodings[nEncodings++] =
				RfbProto.EncodingQualityLevel0 + Options.jpegQuality;
		}

		if (Options.requestCursorUpdates) {
			encodings[nEncodings++] = RfbProto.EncodingXCursor;
			encodings[nEncodings++] = RfbProto.EncodingRichCursor;
			if (!Options.ignoreCursorUpdates)
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


	private void setRfbCachingEncoding() {
		RfbCacheProperties cacheProps = Options.getCacheProperties();

		if (cacheProps != null){
			rfb.setCacheProps(cacheProps);
			int[] encodings = {RfbProto.EncodingRfbCaching};
			try {
				rfb.writeSetEncodings(encodings, 1);
			} catch (Exception e) {
				System.out.println("Could not set encodings");
				System.out.println("Desktop size is " + rfb.framebufferWidth + " x " +
								   rfb.framebufferHeight);
				return;
			}
		}
	}
}
