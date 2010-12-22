/* SeamlessChannel.java
 * Component: UlteoRDP
 * 
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author Thomas MOUTON <thomas@ulteo.com> 2009-2010
 * Author Samuel BOVEE <samuel@ulteo.com> 2010
 * 
 * Revision: $Revision: 0.2 $
 * Author: $Author: arnauvp $
 * Date: $Date: 2008/06/17 18:26:30 $
 *
 * Purpose: Allow seamless RDP session
 * 
 * Inspired by: 
 * Cendio RDP seamless.c
   Copyright (C) Peter Astrand <astrand@cendio.se> 2005-2006
   
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, version 2 of the License.
   
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.
   
   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

package net.propero.rdp.rdp5.seamless;

import java.awt.Frame;
import java.awt.Cursor;
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.FocusEvent;
import java.awt.event.FocusListener;
import java.awt.event.WindowEvent;
import java.awt.event.WindowListener;
import java.awt.event.WindowStateListener;
import java.awt.image.BufferedImage;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.Set;
import java.util.StringTokenizer;
import java.util.concurrent.ConcurrentHashMap;

import org.apache.log4j.Logger;

import net.propero.rdp.Common;
import net.propero.rdp.Options;
import net.propero.rdp.RdesktopException;
import net.propero.rdp.RdpPacket;
import net.propero.rdp.RdpPacket_Localised;
import net.propero.rdp.crypto.CryptoException;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;
import net.propero.rdp.rdp5.VChannel;
import net.propero.rdp.rdp5.VChannels;


public class SeamlessChannel extends VChannel implements WindowStateListener, WindowListener, FocusListener {

	// Seamless RDP constants
	public static final int WINDOW_NOTYETMAPPED=-1;
	public static final int WINDOW_NORMAL = 0;
	public static final int WINDOW_MINIMIZED = 1;
	public static final int WINDOW_MAXIMIZED = 2;
	public static final int WINDOW_POSITION_TIMER = 200000;

	public static final int WINDOW_HELLO_RECONNECT	= 0x0001;
	public static final int WINDOW_HELLO_HIDDEN	= 0x0002;
	
	
	protected int seamlessSerial = 0;
	
	protected ConcurrentHashMap<String, SeamlessWindow> windows;
	protected ConcurrentHashMap<String, Integer> stateOrdersHistory = null;
	
	protected static Logger logger = Logger.getLogger("net.propero.rdp");

	private Frame main_window = null;
	private ClipChannel clipChannel = null;
	private ArrayList<SeamListener> listener = new ArrayList<SeamListener>();
    
	public SeamlessChannel(Options opt_, Common common_) {
		super(opt_, common_);
		logger.debug("Seamless Channel created");		
		this.windows = new ConcurrentHashMap<String, SeamlessWindow>();
		this.stateOrdersHistory = new ConcurrentHashMap<String, Integer>();
	}
	public void setMainFrame(Frame f_) {
		this.main_window = f_;
	}

	public void setClip(ClipChannel c_) {
		this.clipChannel = c_;
	}
	
	public void setCursor(Cursor cursor) {
		for(SeamlessWindow sf : this.windows.values()) {
			sf.sw_setCursor(cursor);
		}
	}

	public void closeAllWindows() {
		for (String w : this.windows.keySet()) {
			Window wnd = (Window) this.windows.get(w);
			wnd.setVisible(false);
			wnd.dispose();
		}
	}

	public Rectangle getMaximumWindowBounds() {
		return new Rectangle(this.opt.x_offset, this.opt.y_offset, this.opt.width, this.opt.height);
	}

	public boolean processLine(String line)
	{
		String p;
		String[] tokens;
		long id, flags;
		int numTokens;
	
		logger.debug("New SeamlessRDP command: " + line);
		
		StringTokenizer st = new StringTokenizer(line, ",");
		numTokens = st.countTokens();
	
		tokens = new String[numTokens];
		
		for (int i=0; i<numTokens; i++) {
			String tmp = st.nextToken().trim();
			if (tmp.startsWith("0x")) {
				try {
					tmp = Long.toString(Long.parseLong(tmp.substring(2), 16));
				} catch(NumberFormatException ex) {
					logger.error("Failed to parse message: '"+line+"': "+ex.getMessage());
					return false;
				}
			}
			tokens[i] = tmp;
//			logger.debug("Token " + i + ": " + tokens[i]);
		}


		if (tokens[0].equals("CREATE"))
		{
			long group, parent;
			if (numTokens < 6)
				return false;
	
			try {
				id = Long.parseLong(tokens[2]);
				group = Long.parseLong(tokens[3]);
				parent = Long.parseLong(tokens[4]);
				flags = Long.parseLong(tokens[5]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}

			return this.processCreate(id, group, parent, flags);
		}
		else if (tokens[0].equals("DESTROY"))
		{
			if (numTokens < 4)
				return false;
	
			
			try {
				id = Long.parseLong(tokens[2]);
				flags = Long.parseLong(tokens[3]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}

			return this.processDestroy(id, flags);
		}
		else if (tokens[0].equals("DESTROYGRP"))
		{
			if (numTokens < 4)
				return false;
	
			try {
				id = Long.parseLong(tokens[2]);
				flags = Long.parseLong(tokens[3]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}

			return this.processDestroyGrp(id, flags);
		}
		/*else if (tokens[0].equals("SETICON"))
		{
//			TODO: unimpl("SeamlessRDP SETICON1\n");
			logger.debug("SeamlessRDP Seticon");
		}*/
		else if (tokens[0].equals("POSITION"))
		{
			long x, y, width, height;
	
			if (numTokens < 8)
				return false;
			
			
			try {
				id = Long.parseLong(tokens[2]);
				x = Long.parseLong(tokens[3]);
				y = Long.parseLong(tokens[4]);
				width = Long.parseLong(tokens[5]);
				height = Long.parseLong(tokens[6]);
				flags = Long.parseLong(tokens[7]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}

			return this.processPosition(id, x, y, width, height, flags);
		}
		else if (tokens[0].equals("ZCHANGE"))
		{
			long behind;
	
			if (numTokens < 5)
				return false;
			
			
			try {
				id = Long.parseLong(tokens[2]);
				behind = Long.parseLong(tokens[3]);
				flags = Long.parseLong(tokens[4]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
	
//			TODO: ui_seamless_restack_window(id, behind, flags);
		}
		else if (tokens[0].equals("TITLE"))
		{
			if (numTokens < 5)
				return false;
			
			try {
				id = Long.parseLong(tokens[2]);
				flags = Long.parseLong(tokens[4]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
	
			return this.processTitle(id, tokens[3], flags);
		}
		else if (tokens[0].equals("STATE"))
		{
			long state;
	
			if (numTokens < 5)
				return false;

			try {
				id = Long.parseLong(tokens[2]);
				state = Long.parseLong(tokens[3]);
				flags = Long.parseLong(tokens[4]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}

			return this.processState(id, state, flags);
		}
		else if (tokens[0].equals("DEBUG"))
		{
			logger.debug("SeamlessRDP Debug: " + line);
		}
		else if (tokens[0].equals("SYNCBEGIN"))
		{
			if (numTokens < 3)
				return false;
			
			try {
				flags = Long.parseLong(tokens[2]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
			
			return this.processSyncBegin();
		}
		else if (tokens[0].equals("SYNCEND"))
		{
			if (numTokens < 3)
				return false;
			
			try {
				flags = Long.parseLong(tokens[2]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
	
			/* do nothing, currently */
		}
		else if (tokens[0].equals("HELLO"))
		{
			if (numTokens < 3)
				return false;
			
			try {
				flags = Long.parseLong(tokens[2]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}

			return this.processHello();
		}
		else if (tokens[0].equals("ACK"))
		{
			long serial;
		
			try {
				serial = Long.parseLong(tokens[2]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
	
//			TODO: ui_seamless_ack(serial);
		}
		else if (tokens[0].equals("HIDE"))
		{
			if (numTokens < 3)
				return false;
			
			try {
				flags = Long.parseLong(tokens[2]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
	
//			TODO: ui_seamless_hide_desktop();
		}
		else if (tokens[0].equals("UNHIDE"))
		{
			if (numTokens < 3)
				return false;
			
			try {
				flags = Long.parseLong(tokens[2]);
			} catch(NumberFormatException nfe) {
				logger.error("Couldn't parse argument from " + tokens[0] + " seamless command.", nfe);
				return false;
			}
	
//			TODO: ui_seamless_unhide_desktop();
		}

		return true;
	}

	protected boolean processCreate(long id, long group, long parent, long flags) {
		/*
		ToDo: Create a Window Type to be able to run seamless windows in native
		mode (frame) or javascript mode with openUrl and subapplet
		rdp.openUrl("javascript:rdpAppletWindow({id:"+id+",x:0,y:0,width:0,height:0})");
		*/
		//TODO: ui_seamless_create_window(id, group, parent, flags);

		String name = "w_"+id;
		if( this.windows.containsKey(name)) {
		    logger.error("[processCreate] ID '"+String.format("0x%08x", id)+"' already exist");
		    return false;
		}

		this.addFrame(new SeamFrame((int)id, (int)group, this.getMaximumWindowBounds(), this.common), name);

		return true;
	}

	protected void addFrame(SeamlessWindow f, String name) {
		f.sw_addWindowStateListener(this);
		f.sw_addWindowListener(this);
		f.sw_addFocusListener(this);
		if (this.clipChannel != null)
			f.sw_addFocusListener(this.clipChannel);
		else
			logger.debug("Unable to add a focus listener to the window "+name);
		this.windows.put(name, f);
	}

	protected boolean processDestroy(long id, long flags) {
		/*
		ToDo: Create a Window Type to be able to run seamless windows in native
		mode (frame) or javascript mode with openUrl and subapplet
		rdp.openUrl("javascript:destroyRdpWindow("+id+")");
		*/
		//TODO: ui_seamless_destroy_window(id, flags);

		String name = "w_"+id;
		if(! this.windows.containsKey(name)) {
		    logger.error("[processDestroy] ID '"+String.format("0x%08x", id)+"' doesn't exist");
		    return false;
		}

		SeamlessWindow f = this.windows.remove(name);
		f.sw_destroy();

		return true;
	}

	protected boolean processDestroyGrp(long id, long flags) {
		//TODO: ui_seamless_destroy_group(id, flags);
		Set <String> keys = this.windows.keySet();

		for(String key: new HashSet<String>(keys))
		{
			try
			{
				SeamlessWindow sf = this.windows.get(key);
				if(sf.sw_getGroup() == id)
				{
					sf.sw_destroy();
					this.windows.remove(key);
				}
			} catch (Exception e) {
				logger.error("[processDestroyGrp] Couldn't remove the window " + key + ".", e);
				return false;
			}
		}

		return true;
	}

	protected boolean processPosition(long id, long x, long y, long width, long height, long flags) {
		/*
		ToDo: Create a Window Type to be able to run seamless windows in native
		mode (frame) or javascript mode with openUrl and subapplet
		rdp.openUrl("javascript:rdpAppletWindow({id:"+id+",x:"+x+",y:"+y+",width:"+width+",height:"+height+"})");
		*/

		String name = "w_"+id;
		if(! this.windows.containsKey(name)) {
		    logger.error("[processPosition] ID '"+String.format("0x%08x", id)+"' doesn't exist");
		    return false;
		}

		SeamlessWindow f = this.windows.get(name);
		if (f.sw_getExtendedState() == Frame.ICONIFIED) {
			logger.warn("Try to resize window "+String.format("0x%08x", id)+" but it is iconified.");
			return false;
		}
		f.sw_setMyPosition((int)x, (int)y, (int)width, (int)height);

		return true;
	}

	protected boolean processTitle(long id, String title, long flags) {
		//TODO: ui_seamless_settitle(id, tokens[3], flags);
		String name = "w_"+id;
		if(! this.windows.containsKey(name)) {
		    logger.error("[processTitle] ID '"+String.format("0x%08x", id)+"' doesn't exist");
		    return false;
		}

		SeamlessWindow f = this.windows.get(name);
		f.sw_setTitle(title);

		return true;
	}

	protected boolean processState(long id, long state, long flags) {
		/*
		ToDo: Create a Window Type to be able to run seamless windows in native
		mode (frame) or javascript mode with openUrl and subapplet
		rdp.openUrl("javascript:showRdpWindow({id:"+id+",show:"+(state==0?"true":"false")+"})");
		*/
		//TODO: ui_seamless_setstate(id, state, flags);

		String name = "w_"+id;
		if(! this.windows.containsKey(name)) {
		    logger.error("[processState] ID '"+String.format("0x%08x", id)+"' doesn't exist");
		    return false;
		}

		SeamlessWindow f = this.windows.get(name);
		int frame_state = Frame.NORMAL;
		switch((int)state) {
			case SeamlessChannel.WINDOW_MINIMIZED:
				frame_state = Frame.ICONIFIED;
				break;
			case SeamlessChannel.WINDOW_MAXIMIZED:
				frame_state = Frame.MAXIMIZED_BOTH;
				break;
			default: //this.WINDOW_NORMAL
				frame_state = Frame.NORMAL;
				break;
		}

		if (frame_state == Frame.ICONIFIED)
			this.stateOrdersHistory.put(name, new Integer(frame_state));

		f.sw_setExtendedState(frame_state);

		return true;
	}

	protected boolean processSyncBegin() {
		//TODO: ui_seamless_syncbegin(flags);
		for(SeamlessWindow sf : this.windows.values()) {
			sf.sw_destroy();
		}
		this.windows.clear();

		return true;
	}

	protected boolean processHello() {
		this.fireAckHello();
		try {
		    this.send_sync();
		} catch(Exception e) {
		    logger.error("[processHello] Unable to send_sync !!!");
		}

		if (this.main_window != null)
			this.main_window.setVisible(false);
		//TODO: ui_seamless_begin(!!(flags & SEAMLESSRDP_HELLO_HIDDEN));
		if (this.opt.seamformEnabled)
			new SeamForm(this, this.opt);

		return true;
	}
	
	protected boolean process_setIcon(long window_id, int chunk, String format, int width, int height, byte[] bitmap) {
		String name = "w_"+window_id;
		
		if(! this.windows.containsKey(name)) {
		    logger.error("[process_setIcon] ID '"+String.format("0x%08x", window_id)+"' doesn't exist");
		    return false;
		}

		SeamlessWindow f = this.windows.get(name);

		if (chunk == 0)
		{
			if (f.sw_getIconSize() > 0)
				logger.debug("[process_setIcon] New icon started before previous completed");
			
			if (! format.equals("RGBA"))
			{
				logger.error("[process_setIcon] Uknown icon format \"" + format + "\"");
				return false;
			}
			
			int icon_size = width * height * 4;
			if(! f.sw_setIconSize(icon_size))
			{
				logger.error("[process_setIcon] Icon too large (" + icon_size + " bytes)");
				return false;
			}
			
			f.sw_setIconOffset(0);
		}
		else
		{
			if (f.sw_getIconSize() == 0)
				return false;
		}
		
		if (bitmap.length > (f.sw_getIconSize() - f.sw_getIconOffset()))
		{
			logger.error("[process_setIcon] Too large chunk received (" + bitmap.length + " bytes > " + (f.sw_getIconSize() - f.sw_getIconOffset()) + " bytes)\n");
			f.sw_setIconSize(0);
			return false;
		}
		
		byte[] icon_buffer = f.sw_getIconBuffer();
		for(int i = 0; i < bitmap.length; i++)
		{
			icon_buffer[f.sw_getIconOffset() + i] = bitmap[i];
		}
		f.sw_setIconBuffer(icon_buffer);
		f.sw_setIconOffset(f.sw_getIconOffset() + bitmap.length);
		
		logger.debug("[process_setIcon] icon_offset: "+f.sw_getIconOffset()+" icon_size: "+f.sw_getIconSize());
		if (f.sw_getIconOffset() == f.sw_getIconSize())
		{
			BufferedImage icon = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
			
			int r, g, b, a, argb, cpt = 0;
			
			for(int y = 0; y < height; y++)
			{
				for(int x = 0; x < width; x++)
				{
					r = (int) icon_buffer[cpt++];
					g = (int) icon_buffer[cpt++];
					b = (int) icon_buffer[cpt++];
					a = (int) icon_buffer[cpt++];
					
					argb = (a << 24) + (r << 16) + (g << 8) + b;
					
					icon.setRGB(x, y, argb);
				}
			}
			f.sw_setIconImage(icon);
			
			f.sw_setIconSize(0);
		}

		return true;
	}

	private static String getNextToken(RdpPacket data, boolean end) {
		String word = "";
		boolean found = false;

		while (data.getPosition() < data.size()) {
			char r = (char)data.get8();
			if (r == ',')
				return word;
				
			word+=r;
		}

		if (end)
			return word;
		return "";
	}
	
	public static byte[] DecryptString_(String input) {
		int len = input.length() / 2;	
		byte[] c = new byte[len];

		for (int i = 0; i < len; i++) {
			String hex = input.substring(i * 2, i * 2 + 2);
			Integer x = new Integer(Integer.parseInt(hex, 16));
			c[i] = x.byteValue();
		}

		return c;
	}
	
	/* Split input into lines, and call linehandler for each line. */
	public void process(RdpPacket data) throws RdesktopException, IOException, CryptoException {
		String firstWord = this.getNextToken(data, false);
		
		if (firstWord.length()==0) {
			logger.debug("Received an empty seamless packet");
			return;
		}

		if (firstWord.equals("SETICON")) {
			String tok[] = new String[6];
			for (int i=0; i<6; i++) {
				tok[i] = this.getNextToken(data, false);
				if (tok[i].length()==0) {
					logger.error("Invalid SETICON message");
					return;
				}
			}
			
			String bitmap_hex = this.getNextToken(data, true); 
//			int len = data.size() -data.getPosition();
			byte bitmap[] = this.DecryptString_(bitmap_hex);
//			data.copyToByteArray(bitmap, 0, data.getPosition(), len);

			try
			{
				long id = Long.parseLong(tok[1].substring(2), 16);
				int chunk = (int)Long.parseLong(tok[2]);
				int width = (int)Long.parseLong(tok[4]);
				int height = (int)Long.parseLong(tok[5]);

				process_setIcon(id, chunk, tok[3], width, height, bitmap);
			} catch(NumberFormatException e) {
				logger.error("SETICON: Invalid parseInt value");
			}
			
			return;
		}
		
		String order = firstWord+",";
		
		byte[] utf8Bytes = new byte[data.size() - data.getPosition()];
		data.copyToByteArray(utf8Bytes, 0, data.getPosition(), utf8Bytes.length);

		String utf8Str = new String(utf8Bytes, "UTF8");

		order += utf8Str;

		this.processLine(order);
	}
	
	protected void seamless_send(String command, String args) throws RdesktopException, IOException, CryptoException {
		
		if (this.opt.seamlessEnabled) {
		
			RdpPacket_Localised s;
			String text = command + "," + seamlessSerial + "," + args + "\n";
			seamlessSerial++; // increment the unique identifier for the message
			
			logger.debug("Sending Seamless message: " + text);
			
			byte[] textBytes = text.getBytes("UTF-8");
			s = new RdpPacket_Localised(textBytes.length);
			s.copyFromByteArray(textBytes, 0, 0, textBytes.length);
			s.markEnd();
			send_packet(s);
			
		} else {
			logger.info("Not sending Seamless message because Seamless Mode is not enabled");
		}
	}

	public void send_sync() throws RdesktopException, IOException, CryptoException
	{
		seamless_send("SYNC", "");
	}
	
	public void send_spawn(String cmd) throws RdesktopException, IOException, CryptoException
	{
		seamless_send("SPAWN", cmd);
	}
	
	public void	send_state(int id, int state, int flags) throws RdesktopException, IOException, CryptoException
	{
		seamless_send("STATE", 
				"0x" + Integer.toHexString(id) + "," + 
				"0x" + Integer.toHexString(state) + "," + 
				"0x" + Integer.toHexString(flags));
	}
	
	
	public void	send_position(int id, int x, int y, int width, int height, int flags) throws RdesktopException, IOException, CryptoException
	{
		seamless_send("POSITION", "0x" + Integer.toHexString(id) + "," +
				x + "," +
				y + "," +
				width + "," +
				height + "," +
				"0x" + Integer.toHexString(flags));
	}

	public void send_destroy(int id) throws RdesktopException, IOException, CryptoException
	{
		seamless_send("DESTROY", "0x" + Integer.toHexString(id));
	}
	
	//TODO: select_timeout
//	public void select_timeout(struct timeval *tv)
//	{
//		struct timeval ourtimeout = { 0, SEAMLESSRDP_POSITION_TIMER };
//	
//		if (Options.seamlessEnabled)
//		{
//			if (timercmp(&ourtimeout, tv, <))
//			{
//				tv->tv_sec = ourtimeout.tv_sec;
//				tv->tv_usec = ourtimeout.tv_usec;
//			}
//		}
//	}
	
	
	public void send_zchange(int id, int below, int flags) throws RdesktopException, IOException, CryptoException
	{
		seamless_send("STATE", 
				"0x" + Integer.toHexString(id) + "," + 
				"0x" + Integer.toHexString(below) + "," + 
				"0x" + Integer.toHexString(flags));
	}
	

	public void send_focus(int id, int flags) throws RdesktopException, IOException, CryptoException
	{
		seamless_send("FOCUS", 
				"0x" + Integer.toHexString(id) + "," + 
				"0x" + Integer.toHexString(flags));
	}


	public int flags() {
		return VChannels.CHANNEL_OPTION_INITIALIZED | VChannels.CHANNEL_OPTION_ENCRYPT_RDP;
	}


	/**
	 * The name of the channel used for SeamlessRDP 
	 * <b>must be</b> <i>seamrdp</i>
	 */
	public String name() {
		return "seamrdp";
	}

	public void windowStateChanged(WindowEvent ev) {
		SeamlessWindow f = (SeamlessWindow)ev.getWindow();

		if (ev.getOldState() == Frame.ICONIFIED) {
			int state = SeamlessChannel.WINDOW_NORMAL;
			switch(ev.getNewState()) {
			case Frame.MAXIMIZED_BOTH:
				state = SeamlessChannel.WINDOW_MAXIMIZED;
				break;
			default:
				state = SeamlessChannel.WINDOW_NORMAL;
			}

			try {
				this.send_state(f.sw_getId(), state, 0);
			} catch(Exception e) {
				System.err.println("send new state failed: "+e);
			}
			return;
		}

		if (ev.getNewState() == Frame.ICONIFIED) {
			String name = "w_"+f.sw_getId();

			if (this.stateOrdersHistory.containsKey(name) && this.stateOrdersHistory.get(name).intValue() == Frame.ICONIFIED) {
				this.stateOrdersHistory.remove(name);
				return;
			}

			try {
				this.send_state(f.sw_getId(), SeamlessChannel.WINDOW_MINIMIZED, 0);
			} catch(Exception e) {
				System.err.println("send new state failed: "+e);
			}
		}
	}
	
	public void addSeamListener(SeamListener l) {
		this.listener.add(l);
	}
	
	public void removeSeamListener(SeamListener l) {
		this.listener.remove(l);
	}
	
	protected void fireAckHello() {
		logger.debug("ACK hello received");
		for(SeamListener list : listener)
			list.ackHello(this);
	}

	public void windowClosing(WindowEvent we) {
		SeamlessWindow sw = (SeamlessWindow) we.getWindow();
		
		try {
			this.send_destroy(sw.sw_getId());
		} catch (Exception ex) {
			System.err.println("Send Destroy failed: "+ex.getMessage());
		}
	}

	public void windowClosed(WindowEvent we) {}
	public void windowOpened(WindowEvent we) {}
	public void windowIconified(WindowEvent we) {}
	public void windowDeiconified(WindowEvent we) {}
	public void windowActivated(WindowEvent we) {}
	public void windowDeactivated(WindowEvent we) {}
	public void focusLost(FocusEvent fe) {}
	
	public void focusGained(FocusEvent fe) {
		SeamlessWindow c = (SeamlessWindow) fe.getComponent();
		for (SeamlessWindow sw : this.windows.values()) {
			if (c == sw) {
				int state = -1;
				switch (sw.sw_getExtendedState()) {
					case Frame.NORMAL:
						state = WINDOW_NORMAL;
						break;
					case Frame.MAXIMIZED_BOTH:
						state = WINDOW_MAXIMIZED;
				}
				if (state == -1)
					return;

				try {
					this.send_focus(sw.sw_getId(), 0);
				} catch (Exception ex) {
					logger.error("Send focus on focus gained failed: "+ex.getMessage());
				}
				return;
			}
		}
	}
}
