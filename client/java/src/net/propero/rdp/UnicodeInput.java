/*
 * Copyright (C) 2011-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012, 2014
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

package net.propero.rdp;

import net.propero.rdp.keymapping.KeyCode;
import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.keymapping.KeyMapException;

import org.apache.log4j.Logger;

import java.awt.KeyboardFocusManager;
import java.awt.MouseInfo;
import java.awt.Toolkit;
import java.awt.event.*;
import java.io.IOException;
import java.io.InputStream;
import java.util.Collections;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.LayoutDetector;

public class UnicodeInput extends Input {

    protected static Logger logger = Logger.getLogger(UnicodeInput.class);
    
    protected ScancodeConverter scancode = null;
    private boolean shiftServerDown = false;

    protected static final int RDP_INPUT_UNICODE = 5;
    
    /**
     * Create a new Input object with a given keymap object
     * @param c Canvas on which to listen for input events
     * @param r Rdp layer on which to send input messages
     * @param k Key map to use in handling keyboard events
     */
	@SuppressWarnings("unchecked")
	public UnicodeInput(RdesktopCanvas c, Rdp r, KeyCode_FileBased k, Options opt_) {
		super(c,r,k,opt_);
		this.scancode = new ScancodeConverter();
		scancode.load();

		this.opt = opt_;

		KeyboardFocusManager.getCurrentKeyboardFocusManager().setDefaultFocusTraversalKeys (KeyboardFocusManager.FORWARD_TRAVERSAL_KEYS, Collections.EMPTY_SET);
		KeyboardFocusManager.getCurrentKeyboardFocusManager().setDefaultFocusTraversalKeys (KeyboardFocusManager.BACKWARD_TRAVERSAL_KEYS, Collections.EMPTY_SET);		

	}

    /**
     * Create a new Input object, using a keymap generated from a specified file
     * @param c Canvas on which to listen for input events
     * @param r Rdp layer on which to send input messages
     * @param keymapFile Path to file containing keymap data
     */
	@SuppressWarnings("unchecked")
	public UnicodeInput(RdesktopCanvas c, Rdp r, String k, Options opt_, Common common_) {
		super(c,r,k,opt_,common_);
 		scancode = new ScancodeConverter();
 		scancode.load();
		this.opt = opt_;

		KeyboardFocusManager.getCurrentKeyboardFocusManager().setDefaultFocusTraversalKeys (KeyboardFocusManager.FORWARD_TRAVERSAL_KEYS, Collections.EMPTY_SET);
		KeyboardFocusManager.getCurrentKeyboardFocusManager().setDefaultFocusTraversalKeys (KeyboardFocusManager.BACKWARD_TRAVERSAL_KEYS, Collections.EMPTY_SET);		

	}

    /**
     * Add all relevant input listeners to the canvas
    */
	protected void setInputListeners() {
		this.mouseAdapter = new UnicodeRdesktopMouseAdapter();
		this.canvas.addMouseListener(this.mouseAdapter);
		if (! OSTools.isMac() || MouseInfo.getNumberOfButtons() > 3) {
			this.opt.isMouseWheelEnabled = true;
			this.canvas.addMouseWheelListener(this.mouseAdapter);
		} else
			Input.logger.warn("No mouse wheel was detected");
		this.mouseMotionAdapter = new RdesktopMouseMotionAdapter();
		this.canvas.addMouseMotionListener(this.mouseMotionAdapter);
		this.keyAdapter = new UnicodeAdapter();
		this.canvas.addKeyListener(this.keyAdapter);
	}

    /**
     * Send a keyboard event to the server
     * @param time Time stamp to identify this event
     * @param flags Flags defining the nature of the event (eg: press/release/quiet/extended)
     * @param scancode Scancode value identifying the key in question
     */
	public void sendScancode(long time, int flags, int scancode) {
		if (scancode == 0) {
			return;
		}
		if ((scancode & KeyCode.SCANCODE_EXTENDED) != 0) {
			rdp.sendInput((int) time, RDP_INPUT_SCANCODE, flags | KBD_FLAG_EXT,
					scancode & ~KeyCode.SCANCODE_EXTENDED, 0);
		} else
			rdp.sendInput((int) time, RDP_INPUT_SCANCODE, flags, scancode, 0);
	}

	class UnicodeAdapter extends KeyAdapter {

        /**
         * Construct an RdesktopKeyAdapter based on the parent KeyAdapter class
         */
		public UnicodeAdapter() {
			super();
		}

		boolean checkModifiers(KeyEvent e) {
			if (e.getKeyLocation() == KeyEvent.KEY_LOCATION_NUMPAD && !e.isActionKey()) {
				if (! numLockOn) {
					numLockOn = true;
					sendScancode(getTime(), RDP_KEYPRESS, 0x45);
					sendScancode(getTime(), RDP_KEYRELEASE, 0x45);
				}
			}
			if (e.getKeyCode() == KeyEvent.VK_NUM_LOCK ) {
				numLockOn = !numLockOn;
				sendScancode(getTime(), RDP_KEYPRESS, 0x45);
				sendScancode(getTime(), RDP_KEYRELEASE, 0x45);
			}
			
			if (e.getID() == KeyEvent.KEY_PRESSED) {
				if (e.getKeyCode() == KeyEvent.VK_ALT){
					if (e.getKeyLocation() == KeyEvent.KEY_LOCATION_RIGHT) {
						altgrDown = true;
						if (ctrlDown) {
							sendScancode(getTime(), RDP_KEYRELEASE, 0x1d); // l.ctrl
							ctrlDown = false;
						}
					}
					else {
						altDown = true;
						sendScancode(getTime(), RDP_KEYPRESS, KBD_ALT_KEY); // l.alt
					}
					return true;
				}
				if (e.getKeyCode() == KeyEvent.VK_CONTROL){
					ctrlDown = true;
					sendScancode(getTime(), RDP_KEYPRESS, 0x1d); // l.ctrl
					return true;
				}
				if (e.getKeyCode() == KeyEvent.VK_SHIFT){
					shiftDown = true;
					return true;
				}
			}
			if (e.getID() == KeyEvent.KEY_RELEASED) {
				if (e.getKeyCode() == KeyEvent.VK_ALT){
					if (e.getKeyLocation() == KeyEvent.KEY_LOCATION_RIGHT) {
						altgrDown = false;
					}
					else {
						altDown = false;
						sendScancode(getTime(), RDP_KEYRELEASE, KBD_ALT_KEY); // l.alt
					}
					return true;
				}
				if (e.getKeyCode() == KeyEvent.VK_CONTROL){
					if (ctrlDown) {
						ctrlDown = false;
						sendScancode(getTime(), RDP_KEYRELEASE, 0x1d); // l.ctrl
					}
					return true;
				}
				if (e.getKeyCode() == KeyEvent.VK_SHIFT){
					if(shiftServerDown)
						sendScancode(getTime(), RDP_KEYRELEASE, KBD_SHIFT_KEY);
					
					shiftServerDown = false;
					shiftDown = false;
					return true;
				}
			}
			return false;
		}
		
        /**
         * Handle a keyPressed event, sending any relevant keypresses to the server
         */
		public void keyPressed(KeyEvent e) {
			if (checkModifiers(e)) {
				return;
			}
			long time = getTime();
			int scan = -1;
			
			logger.debug("PRESSED keychar='" + e.getKeyChar() + "' keycode=0x"
					+ Integer.toHexString(e.getKeyCode()) + " char='"
					+ ((char) e.getKeyCode()) + "'");
			
			if (rdp != null) {
				scan = newKeyMapper.getSpecialKey(e);
				if (!handleSpecialKeys(time, e, true) && scan != -1) {
					if (shiftDown) {
						shiftServerDown = true;
						sendScancode(time, RDP_KEYPRESS, KBD_SHIFT_KEY);
					}
					sendScancode(time, RDP_KEYPRESS, scan);
					proceedOnKeyPressed = true;
				}
				else {
					if (ctrlDown || altDown) {
						char c = Character.toLowerCase((char)e.getKeyCode());
						// With non latin keyboard layout, there is no keyCode for a key.
						// We need the keyChar
						if (c == 0)
							c = e.getKeyChar();
						
						scan = newKeyMapper.getFromMap(KeyCode_FileBased.NOSHIFT_SECTION, Integer.toString(c));
						
						if (scan == -1) {
							String str = e.toString();
							
							// Only works with Sun jvm
							if (OSTools.isWindows()) {
								Pattern p = Pattern.compile(".*scancode=([0-9]*).*");
								Matcher m = p.matcher(str);
								
								if (m.matches()) {
									try {
										scan = Integer.parseInt(m.group(1));
									}
									catch (NumberFormatException e2) {
										logger.warn("Failed to convert scancode "+m.group(1)+" "+e2.getMessage());
									}
								}
							}
							else {
								Pattern p = Pattern.compile(".*rawCode=([0-9]*).*");
								Matcher m = p.matcher(str);
								
								if (m.matches()) {
									try {
										int rawCode = Integer.parseInt(m.group(1));
										// https://mail.gnome.org/archives/gtk-vnc-list/2009-November/msg00017.html
										if (rawCode < 80)
											scan = rawCode - 8;
									}
									catch (NumberFormatException e2) {
										logger.warn("Failed to convert rawcode "+m.group(1)+" "+e2.getMessage());
									}
								}
							}
							 
							// Brutal way
							if (scan == -1) {
								String layout = LayoutDetector.get();
								InputStream istr = UnicodeInput.class.getResourceAsStream(RdpConnection.KEYMAP_PATH + layout);
								
								if (istr != null) {
									try {
										KeyCode_FileBased keyMap;
										
										keyMap = new KeyCode_FileBased_Localised(istr, opt);
										scan = keyMap.getFromMap(KeyCode_FileBased.NOSHIFT_SECTION, Integer.toString(e.getKeyChar()));
										
										istr.close();
									}
									catch (IOException ex) {
										logger.warn("Unable to close keymap " + layout +" : "+ ex);
									}
									catch (KeyMapException e1) {
										logger.warn("Unable to load keymap file: "+e1.getMessage());
									}
								}
							}
						}
						
						sendScancode(time, RDP_KEYPRESS, scan);
						proceedOnKeyPressed = true;
					}	
				}
			}
		}

        /**
         * Handle a keyTyped event, sending any relevant keypresses to the server
         */
		public void keyTyped(KeyEvent e) {
			logger.debug("TYPED keychar='" + e.getKeyChar() + "' keycode=0x"
					+ Integer.toHexString(e.getKeyCode()) + " char='"
					+ ((char) e.getKeyCode()) + "'");
			if (proceedOnKeyPressed) {
				proceedOnKeyPressed = false;
				return;
			}
			int scan = newKeyMapper.getSpecialKey(e);

			if (scan != -1)
				return;
			
			if (Character.isDefined(e.getKeyChar())) {
				rdp.sendInput(getTime(), RDP_INPUT_UNICODE, 0, e.getKeyChar(), 0);
			}
		}

        /**
         * Handle a keyReleased event, sending any relevent key events to the server
         */
		public void keyReleased(KeyEvent e) {
			proceedOnKeyPressed = false;
			if (checkModifiers(e)) {
				return;
			}
			long time = getTime();
			int scan = -1;

			logger.debug("RELEASED keychar='" + e.getKeyChar() + "' keycode=0x"
					+ Integer.toHexString(e.getKeyCode()) + " char='"
					+ ((char) e.getKeyCode()) + "'");
			if (rdp != null) {
				scan = newKeyMapper.getSpecialKey(e);
				if (!handleSpecialKeys(time, e, false) && scan != -1) {
					sendScancode(time, RDP_KEYRELEASE, scan);
				}
				else {
					if (ctrlDown || altDown) {
						char c = Character.toLowerCase((char)e.getKeyCode());
						scan = newKeyMapper.getFromMap(KeyCode_FileBased.NOSHIFT_SECTION, Integer.toString(c));
						sendScancode(time, RDP_KEYRELEASE, scan);
					}	
				}
			}
		}

	}

	protected void doLockKeys(){
		// 	doesn't work on Java 1.4.1_02 or 1.4.2 on Linux, there is a bug in java....
		// does work on the same version on Windows.
		if(!this.opt.readytosend) return;
		if(!this.opt.useLockingKeyState) return;
		if(Constants.OS == Constants.LINUX) return; // broken for linux
		if(Constants.OS == Constants.MAC) return; // unsupported operation for mac
		logger.debug("doLockKeys");
		
		try {
			Toolkit tk = Toolkit.getDefaultToolkit();	   
			if (tk.getLockingKeyState(KeyEvent.VK_CAPS_LOCK) != capsLockOn){
				capsLockOn = !capsLockOn;
				logger.debug("CAPS LOCK toggle");
				sendScancode(getTime(),RDP_KEYPRESS,0x3a);
				sendScancode(getTime(),RDP_KEYRELEASE,0x3a);
		
			}
			if (tk.getLockingKeyState(KeyEvent.VK_SCROLL_LOCK) != scrollLockOn){
				scrollLockOn = !scrollLockOn;
				logger.debug("SCROLL LOCK toggle");
				sendScancode(getTime(),RDP_KEYPRESS,0x46);
				sendScancode(getTime(),RDP_KEYRELEASE,0x46);
			}
	  }catch(Exception e){
		  this.opt.useLockingKeyState = false;
	  }
	}

	
	class UnicodeRdesktopMouseAdapter extends RdesktopMouseAdapter {
		public void mousePressed(MouseEvent e) {
			if (shiftDown) {
				sendScancode(getTime(), RDP_KEYPRESS, KBD_SHIFT_KEY);
				shiftServerDown = true;
			}
			
			super.mousePressed(e);
		}
	}
}
