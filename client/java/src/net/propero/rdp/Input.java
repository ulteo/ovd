/* Input.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.2 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/14 23:26:08 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Handles input events and sends relevant input data
 *          to server
 */

package net.propero.rdp;

import net.propero.rdp.keymapping.KeyCode;
import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.keymapping.KeyMapException;

import org.apache.log4j.Logger;
import org.apache.log4j.Level;

import java.awt.Component;
import java.awt.MouseInfo;
import java.awt.event.*;
import java.util.HashMap;
import java.util.List;
import java.util.Map.Entry;
import java.util.Vector;
import java.util.concurrent.CopyOnWriteArrayList;
import javax.swing.KeyStroke;
import org.ulteo.ovd.integrated.OSTools;

public abstract class Input {

    protected static Logger logger = Logger.getLogger(Input.class);
    
	public KeyCode_FileBased newKeyMapper = null;

	public Vector<Integer> pressedKeys;

	protected static boolean capsLockOn = false;
	protected static boolean numLockOn = false;
	protected static boolean scrollLockOn = false;

	protected static boolean serverAltDown = false;
    protected static boolean altDown = false;
	protected static boolean ctrlDown = false;
	protected static boolean altgrDown = false;

    protected static long last_mousemove = 0;
    
	// VK_CODES list is here: http://msdn.microsoft.com/en-us/library/dd375731(v=vs.85).aspx

	// Using this flag value (0x0001) seems to do nothing, and after running
	// through other possible values, the RIGHT flag does not appear to be
	// implemented
	protected static final int KBD_FLAG_RIGHT = 0x0001;
	protected static final int KBD_FLAG_EXT = 0x0100;

	// QUIET flag is actually as below (not 0x1000 as in rdesktop)
	protected static final int KBD_FLAG_QUIET = 0x200;
	protected static final int KBD_FLAG_DOWN = 0x4000;
	protected static final int KBD_FLAG_UP = 0x8000;

	protected static int SCANCODE_EXTENDED = 0x80;

	protected static final int KBD_ALT_KEY = 0x38;
	protected static final int KBD_ALTGR_KEY = SCANCODE_EXTENDED | KBD_ALT_KEY;
	protected static final int KBD_KEY_WINDOWS_LEFT = SCANCODE_EXTENDED | 0x5B; // Left Windows key
	protected static final int KBD_KEY_WINDOWS_RIGHT = SCANCODE_EXTENDED | 0x5C; // Right Windows key
	
	protected static final int RDP_KEYPRESS = 0;
	protected static final int RDP_KEYRELEASE = KBD_FLAG_DOWN | KBD_FLAG_UP;
	protected static final int MOUSE_FLAG_MOVE = 0x0800;

	protected static final int MOUSE_FLAG_BUTTON1 = 0x1000;
	protected static final int MOUSE_FLAG_BUTTON2 = 0x2000;
	protected static final int MOUSE_FLAG_BUTTON3 = 0x4000;

	protected static final int MOUSE_FLAG_BUTTON4 = 0x0280; // wheel up
	protected static final int MOUSE_FLAG_BUTTON5 = 0x0380; // wheel down
															
	protected static final int MOUSE_FLAG_DOWN = 0x8000;

    protected static final int RDP_INPUT_SYNCHRONIZE = 0;
	protected static final int RDP_INPUT_CODEPOINT = 1;
	protected static final int RDP_INPUT_VIRTKEY = 2;
	protected static final int RDP_INPUT_SCANCODE = 4;
	protected static final int RDP_INPUT_MOUSE = 0x8001;

	protected static int time = 0;

	public KeyEvent lastKeyEvent = null;
	public boolean modifiersValid = false;
	public boolean keyDownWindows = false;

	protected RdesktopCanvas canvas = null;
	protected Rdp rdp = null;
	KeyCode keys = null;
	protected Options opt = null;

	protected KeyAdapter keyAdapter = null;
	protected MouseAdapter mouseAdapter = null;
	protected MouseMotionAdapter mouseMotionAdapter = null;

	protected static final int DEFAULT_KEYSTROKE_DELAY = 500;
	protected HashMap<KeyStroke, Long> keystrokesList = null;
	protected List<InputListener> inputListeners = null;

    /**
     * Create a new Input object with a given keymap object
     * @param c Canvas on which to listen for input events
     * @param r Rdp layer on which to send input messages
     * @param k Key map to use in handling keyboard events
     */
	public Input(RdesktopCanvas c, Rdp r, KeyCode_FileBased k, Options opt_) {
		this.opt = opt_;
		newKeyMapper = k;
		canvas = c;
		rdp = r;
		if (this.opt.debug_keyboard)
			logger.setLevel(Level.DEBUG);
		this.setInputListeners();
		pressedKeys = new Vector<Integer>();
		this.keystrokesList = new HashMap<KeyStroke, Long>();
		this.inputListeners = new CopyOnWriteArrayList<InputListener>();
	}

    /**
     * Create a new Input object, using a keymap generated from a specified file
     * @param c Canvas on which to listen for input events
     * @param r Rdp layer on which to send input messages
     * @param keymapFile Path to file containing keymap data
     */
	public Input(RdesktopCanvas c, Rdp r, String keymapFile, Options opt_, Common common) {
		try {
			newKeyMapper = new KeyCode_FileBased_Localised(keymapFile, this.opt);
		} catch (KeyMapException kmEx) {
			System.err.println(kmEx.getMessage());
			if(!common.underApplet) System.exit(-1);
		}
		this.opt = opt_;
		canvas = c;
		rdp = r;
		if (this.opt.debug_keyboard)
			logger.setLevel(Level.DEBUG);
		this.setInputListeners();
		pressedKeys = new Vector<Integer>();
		this.keystrokesList = new HashMap<KeyStroke, Long>();
		this.inputListeners = new CopyOnWriteArrayList<InputListener>();
	}

	public void addInputListener(InputListener listener) {
		this.inputListeners.add(listener);
	}

	public boolean removeInputListener(InputListener listener) {
		if (! this.inputListeners.contains(listener))
			return false;

		this.inputListeners.remove(listener);
		return true;
	}

	protected void fireKeyStrokePressed(KeyStroke keystroke, KeyEvent ke) {
		for (InputListener each : this.inputListeners)
			each.keyStrokePressed(keystroke, ke);
	}

    /**
     * Add all relevant input listeners to the canvas
    */
	protected void setInputListeners() {
		this.mouseAdapter = new RdesktopMouseAdapter();
		this.canvas.addMouseListener(this.mouseAdapter);
		if (! OSTools.isMac() || MouseInfo.getNumberOfButtons() > 3) {
			this.opt.isMouseWheelEnabled = true;
			this.canvas.addMouseWheelListener(this.mouseAdapter);
		} else
			Input.logger.warn("No mouse wheel was detected");
		this.mouseMotionAdapter = new RdesktopMouseMotionAdapter();
		this.canvas.addMouseMotionListener(this.mouseMotionAdapter);
		this.keyAdapter = new RdesktopKeyAdapter();
		this.canvas.addKeyListener(this.keyAdapter);
	}

	public MouseAdapter getMouseAdapter() {
		return this.mouseAdapter;
	}

	public MouseMotionAdapter getMouseMotionAdapter() {
		return this.mouseMotionAdapter;
	}

	public KeyAdapter getKeyAdapter() {
		return this.keyAdapter;
	}

	public void addKeyStroke(KeyStroke keystroke) {
		if (keystroke == null)
			return;

		this.keystrokesList.put(keystroke, 0L);
	}

	public boolean removeKeyStroke(KeyStroke keystroke) {
		if (keystroke == null || this.keystrokesList.isEmpty())
			return false;

		boolean found = false;
		for (KeyStroke each : this.keystrokesList.keySet()) {
			if (each.equals(keystroke))
				found = true;
		}
		if (! found)
			return false;

		this.keystrokesList.remove(keystroke);
		return true;
	}

	/**
     * Send a sequence of key actions to the server
     * @param pressSequence String representing a sequence of key actions.
     *                      Actions are represented as a pair of consecutive characters,
     *                      the first character's value (cast to integer) being the scancode
     *                      to send, the second (cast to integer) of the pair representing the action
     *                      (0 == UP, 1 == DOWN, 2 == QUIET UP, 3 == QUIET DOWN). 
	 */
	public void sendKeyPresses(String pressSequence) {
		try {
			String debugString = "Sending keypresses: ";
			for (int i = 0; i < pressSequence.length(); i += 2) {
				int scancode = (int) pressSequence.charAt(i);
				int action = (int) pressSequence.charAt(i + 1);
				int flags = 0;

				if (action == KeyCode_FileBased.UP)
					flags = RDP_KEYRELEASE;
				else if (action == KeyCode_FileBased.DOWN)
					flags = RDP_KEYPRESS;
				else if (action == KeyCode_FileBased.QUIETUP)
					flags = RDP_KEYRELEASE | KBD_FLAG_QUIET;
				else if (action == KeyCode_FileBased.QUIETDOWN)
					flags = RDP_KEYPRESS | KBD_FLAG_QUIET;

				long t = getTime();

				debugString += "(0x"
						+ Integer.toHexString(scancode)
						+ ", "
						+ ((action == KeyCode_FileBased.UP || action == KeyCode_FileBased.QUIETUP) ? "up"
								: "down")
						+ ((flags & KBD_FLAG_QUIET) != 0 ? " quiet" : "")
						+ " at " + t + ")";

				sendScancode(t, flags, scancode);
			}

			if (pressSequence.length() > 0)
				logger.debug(debugString);
		} catch (Exception ex) {
			return;
		}
	}

    /**
     * Retrieve the next "timestamp", by incrementing previous
     * stamp (up to the maximum value of an integer, at which the
     * timestamp is reverted to 1)
     * @return New timestamp value
     */
	public static int getTime() {
		time++;
		if (time == Integer.MAX_VALUE)
			time = 1;
		return time;
	}

    /**
     * Handle loss of focus to the main canvas.
     * Clears all depressed keys (sending release messages
     * to the server.
     */
	public void lostFocus() {
        clearKeys();
		modifiersValid = false;
	}

    /**
     * Handle the main canvas gaining focus.
     * Check locking key states.
     */
    public void gainedFocus() {
		doLockKeys(); // ensure lock key states are correct
	}

    /**
     * Send a keyboard event to the server
     * @param time Time stamp to identify this event
     * @param flags Flags defining the nature of the event (eg: press/release/quiet/extended)
     * @param scancode Scancode value identifying the key in question
     */
	public void sendScancode(long time, int flags, int scancode) {

        if(scancode == KBD_ALT_KEY){ // be careful with alt
            if((flags & RDP_KEYRELEASE) != 0){
                //logger.info("Alt release, serverAltDown = " + serverAltDown);
                serverAltDown = false;
            }
            if((flags == RDP_KEYPRESS)){
                //logger.info("Alt press, serverAltDown = " + serverAltDown);
                serverAltDown = true;
            }
        }
        
		if ((scancode & KeyCode.SCANCODE_EXTENDED) != 0) {
			rdp.sendInput((int) time, RDP_INPUT_SCANCODE, flags | KBD_FLAG_EXT,
					scancode & ~KeyCode.SCANCODE_EXTENDED, 0);
		} else
			rdp.sendInput((int) time, RDP_INPUT_SCANCODE, flags, scancode, 0);
	}

    /**
     * Release any modifier keys that may be depressed.
     */
	public void clearKeys() {
        if(lastKeyEvent == null) return;
        
		if (lastKeyEvent.isShiftDown())
			sendScancode(getTime(), RDP_KEYRELEASE, 0x2a); // shift
		if (lastKeyEvent.isAltDown() || altDown) {
			// with Openoffice, if no other keys are pressed at the ALT release sequence, a menu is displayed  
			sendScancode(getTime(), RDP_KEYPRESS, 0x2a); // l.shift
			sendScancode(getTime(), RDP_KEYRELEASE, 0x2a); // l.shift
			sendScancode(getTime(), RDP_KEYRELEASE, KBD_ALT_KEY); // l.alt
        }
		if (lastKeyEvent.isControlDown() ||  ctrlDown) {
            sendScancode(getTime(), RDP_KEYRELEASE, 0x1d); // l.ctrl    
            //sendScancode(getTime(), RDP_KEYPRESS | KBD_FLAG_QUIET, 0x1d); // Ctrl
            //sendScancode(getTime(), RDP_KEYRELEASE | KBD_FLAG_QUIET, 0x1d); // ctrl
        }
		if (lastKeyEvent.isAltGraphDown() || altgrDown)
			sendScancode(getTime(), RDP_KEYRELEASE, KBD_ALTGR_KEY); // altgr
		
		altDown = false;
		ctrlDown = false;
		altgrDown = false;

	}

    /**
     * Send keypress events for any modifier keys that are currently down
     */
	public void setKeys() {
		if (!modifiersValid)
			return;


        if(lastKeyEvent == null) return;
        
		if (lastKeyEvent.isShiftDown())
			sendScancode(getTime(), RDP_KEYPRESS, 0x2a); // shift
		if (lastKeyEvent.isAltDown())
			sendScancode(getTime(), RDP_KEYPRESS, 0x38); // l.alt
		if (lastKeyEvent.isControlDown())
			sendScancode(getTime(), RDP_KEYPRESS, 0x1d); // l.ctrl
		if (lastKeyEvent.isAltGraphDown())
			sendScancode(getTime(), RDP_KEYPRESS, KBD_ALTGR_KEY); // altGr
	}

	class RdesktopKeyAdapter extends KeyAdapter {

        /**
         * Construct an RdesktopKeyAdapter based on the parent KeyAdapter class
         */
		public RdesktopKeyAdapter() {
			super();
		}

		boolean checkModifiers(KeyEvent e) {
			if (OSTools.isLinux()) {
				if (e.isAltGraphDown() && !altgrDown) {
					altgrDown = true;
					sendScancode(getTime(), RDP_KEYPRESS, KBD_ALTGR_KEY); // altGr
				}
				if (! e.isAltGraphDown() && altgrDown) {
					altgrDown = false;
					sendScancode(getTime(), RDP_KEYRELEASE, KBD_ALTGR_KEY); // altGr
				}
			}
			if (e.getID() == KeyEvent.KEY_PRESSED) {
				if (e.getKeyCode() == KeyEvent.VK_ALT){
					if (e.getKeyLocation() == KeyEvent.KEY_LOCATION_RIGHT) {
						altgrDown = true;
						if (ctrlDown) {
							sendScancode(getTime(), RDP_KEYRELEASE, 0x1d); // l.ctrl
							ctrlDown = false;
						}
						sendScancode(getTime(), RDP_KEYPRESS, KBD_ALTGR_KEY); // l.ctrl
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
			}
			if (e.getID() == KeyEvent.KEY_RELEASED) {
				if (e.getKeyCode() == KeyEvent.VK_ALT){
					if (e.getKeyLocation() == KeyEvent.KEY_LOCATION_RIGHT) {
						altgrDown = false;
						sendScancode(getTime(), RDP_KEYRELEASE, KBD_ALTGR_KEY); // l.altgr
					}
					else {
						altDown = true;
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
			}
			return false;
		}
		
        /**
         * Handle a keyPressed event, sending any relevant keypresses to the server
         */
		public void keyPressed(KeyEvent e) {
			KeyStroke keystroke = KeyStroke.getKeyStrokeForEvent(e);

			if (checkModifiers(e)) {
				return;
			}
			
			for (Entry<KeyStroke, Long> each : keystrokesList.entrySet()) {
				if (each.getKey().equals(keystroke)) {
					long time = System.currentTimeMillis();
					Long lastTime = each.getValue();
					if (time >= (lastTime + DEFAULT_KEYSTROKE_DELAY)) {
						each.setValue(time);
						fireKeyStrokePressed(keystroke, e);
					}
					return;
				}

			}

			lastKeyEvent = e;
			modifiersValid = true;
			long time = getTime();

			// Some java versions have keys that don't generate keyPresses -
			// here we add the key so we can later check if it happened
			pressedKeys.addElement(new Integer(e.getKeyCode()));

			logger.debug("PRESSED keychar='" + e.getKeyChar() + "' keycode=0x"
					+ Integer.toHexString(e.getKeyCode()) + " char='"
					+ ((char) e.getKeyCode()) + "'");

			if (rdp != null) {
				if (!handleSpecialKeys(time, e, true)) {
					sendKeyPresses(newKeyMapper.getKeyStrokes(e));
				}
				// sendScancode(time, RDP_KEYPRESS, keys.getScancode(e));
			}
		}

        /**
         * Handle a keyTyped event, sending any relevant keypresses to the server
         */
		public void keyTyped(KeyEvent e) {
			modifiersValid = true;
			long time = getTime();

			if (checkModifiers(e)) {
				return;
			}

			// Some java versions have keys that don't generate keyPresses -
			// here we add the key so we can later check if it happened
			pressedKeys.addElement(new Integer(e.getKeyCode()));

			logger.debug("TYPED keychar='" + e.getKeyChar() + "' keycode=0x"
					+ Integer.toHexString(e.getKeyCode()) + " char='"
					+ ((char) e.getKeyCode()) + "'");

			if (rdp != null) {
				if (!handleSpecialKeys(time, e, true)) {
					String keySeq = newKeyMapper.getKeyStrokes(e);
					if (keySeq.length() != 0) {
						sendKeyPresses(keySeq);
					}
					else if (lastKeyEvent.getKeyChar() != ((char)-1)) {
						char lastKeyChar = Character.toLowerCase((char)lastKeyEvent.getKeyCode());
						KeyEvent ev = new KeyEvent((Component)lastKeyEvent.getSource(), KeyEvent.KEY_TYPED, lastKeyEvent.getWhen(), 0, 0, lastKeyChar);
						sendKeyPresses(newKeyMapper.getKeyStrokes(ev));
					}
				}
				// sendScancode(time, RDP_KEYPRESS, keys.getScancode(e));
			}
			lastKeyEvent = e;
		}

        /**
         * Handle a keyReleased event, sending any relevent key events to the server
         */
		public void keyReleased(KeyEvent e) {
			if (checkModifiers(e)) {
				return;
			}
			
			// Some java versions have keys that don't generate keyPresses -
			// we added the key to the vector in keyPressed so here we check for
			// it
			Integer keycode = new Integer(e.getKeyCode());
			if (!pressedKeys.contains(keycode)) {
				if (rdp != null) {
					KeyEvent ev = new KeyEvent((Component)e.getSource(), KeyEvent.KEY_PRESSED, e.getWhen(), e.getModifiers(), e.getKeyCode(), e.getKeyChar());
					this.keyPressed(ev);
					if (e.getKeyChar() != ((char)-1)) {
						ev = new KeyEvent((Component)e.getSource(), KeyEvent.KEY_TYPED, e.getWhen(), e.getModifiers(), 0, e.getKeyChar());
						this.keyTyped(ev);
					}
					pressedKeys.removeElement(keycode);
				}
			}

			pressedKeys.removeElement(keycode);

			lastKeyEvent = e;
			modifiersValid = true;
			long time = getTime();

			logger.debug("RELEASED keychar='" + e.getKeyChar() + "' keycode=0x"
					+ Integer.toHexString(e.getKeyCode()) + " char='"
					+ ((char) e.getKeyCode()) + "'");
			if (rdp != null) {
				if (!handleSpecialKeys(time, e, false))
					sendKeyPresses(newKeyMapper.getKeyStrokes(e));
				// sendScancode(time, RDP_KEYRELEASE, keys.getScancode(e));
			}
		}

	}

    /**
     * Act on any keyboard shortcuts that a specified KeyEvent may describe
     * @param time Time stamp for event to send to server
     * @param e Keyboard event to be checked for shortcut keys
     * @param pressed True if key was pressed, false if released
     * @return True if a shortcut key combination was detected and acted upon, false otherwise
     */
	public boolean handleShortcutKeys(long time, KeyEvent e, boolean pressed) {
		if(!e.isAltDown()) return false;
        
        if (!altDown) return false; // all of the below have ALT on

		switch (e.getKeyCode()) {

		/* case KeyEvent.VK_M:
			if(pressed) ((RdesktopFrame_Localised) canvas.getParent()).toggleMenu();
			break; */
		
		case KeyEvent.VK_TAB: // Alt+Tab received, quiet combination

			sendScancode(time, (pressed ? RDP_KEYPRESS : RDP_KEYRELEASE)
					| KBD_FLAG_QUIET, 0x0f);
			if (!pressed) {
				sendScancode(time, RDP_KEYRELEASE | KBD_FLAG_QUIET, 0x38); // Release
																			// Alt
			}

			if (pressed)
				logger.debug("Alt + Tab pressed, ignoring, releasing tab");
			break;
		case KeyEvent.VK_PAGE_UP: // Alt + PgUp = Alt-Tab
			sendScancode(time, pressed ? RDP_KEYPRESS : RDP_KEYRELEASE, 0x0f); // TAB
			if (pressed)
				logger.debug("shortcut pressed: sent ALT+TAB");
			break;
		case KeyEvent.VK_PAGE_DOWN: // Alt + PgDown = Alt-Shift-Tab
			if (pressed) {
				sendScancode(time, RDP_KEYPRESS, 0x2a); // Shift
				sendScancode(time, RDP_KEYPRESS, 0x0f); // TAB
				logger.debug("shortcut pressed: sent ALT+SHIFT+TAB");
			} else {
				sendScancode(time, RDP_KEYRELEASE, 0x0f); // TAB
				sendScancode(time, RDP_KEYRELEASE, 0x2a); // Shift
			}

			break;
		case KeyEvent.VK_INSERT: // Alt + Insert = Alt + Esc
			sendScancode(time, pressed ? RDP_KEYPRESS : RDP_KEYRELEASE, 0x01); // ESC
			if (pressed)
				logger.debug("shortcut pressed: sent ALT+ESC");
			break;
		case KeyEvent.VK_HOME: // Alt + Home = Ctrl + Esc (Start)
			if (pressed) {
				sendScancode(time, RDP_KEYRELEASE, 0x38); // ALT
				sendScancode(time, RDP_KEYPRESS, 0x1d); // left Ctrl
				sendScancode(time, RDP_KEYPRESS, 0x01); // Esc
				logger.debug("shortcut pressed: sent CTRL+ESC (Start)");

			} else {
				sendScancode(time, RDP_KEYRELEASE, 0x01); // escape
				sendScancode(time, RDP_KEYRELEASE, 0x1d); // left ctrl
				// sendScancode(time,RDP_KEYPRESS,0x38); // ALT
			}

			break;
		case KeyEvent.VK_END: // Ctrl+Alt+End = Ctrl+Alt+Del
			if (ctrlDown) {
				sendScancode(time, pressed ? RDP_KEYPRESS : RDP_KEYRELEASE,
						0x53 | KeyCode.SCANCODE_EXTENDED); // DEL
				if (pressed)
					logger.debug("shortcut pressed: sent CTRL+ALT+DEL");
			}
			break;
		case KeyEvent.VK_DELETE: // Alt + Delete = Menu
			if (pressed) {
				sendScancode(time, RDP_KEYRELEASE, 0x38); // ALT
				// need to do another press and release to shift focus from
				// to/from menu bar
				sendScancode(time, RDP_KEYPRESS, 0x38); // ALT
				sendScancode(time, RDP_KEYRELEASE, 0x38); // ALT
				sendScancode(time, RDP_KEYPRESS,
						0x5d | KeyCode.SCANCODE_EXTENDED); // Menu
				logger.debug("shortcut pressed: sent MENU");
			} else {
				sendScancode(time, RDP_KEYRELEASE,
						0x5d | KeyCode.SCANCODE_EXTENDED); // Menu
				// sendScancode(time,RDP_KEYPRESS,0x38); // ALT
			}
			break;
		case KeyEvent.VK_SUBTRACT: // Ctrl + Alt + Minus (on NUM KEYPAD) =
									// Alt+PrtSc
			if (ctrlDown) {
				if (pressed) {
					sendScancode(time, RDP_KEYRELEASE, 0x1d); // Ctrl
					sendScancode(time, RDP_KEYPRESS,
							0x37 | KeyCode.SCANCODE_EXTENDED); // PrtSc
					logger.debug("shortcut pressed: sent ALT+PRTSC");
				} else {
					sendScancode(time, RDP_KEYRELEASE,
							0x37 | KeyCode.SCANCODE_EXTENDED); // PrtSc
					sendScancode(time, RDP_KEYPRESS, 0x1d); // Ctrl
				}
			}
			break;
		default:
			return false;
		}
		return true;
	}

    /**
     * Deal with modifier keys as control, alt or caps lock
     * @param time Time stamp for key event
     * @param e Key event to check for special keys
     * @param pressed True if key was pressed, false if released
     * @return
     */
	public boolean handleSpecialKeys(long time, KeyEvent e, boolean pressed) {
		if (handleShortcutKeys(time, e, pressed))
			return true;

		switch (e.getKeyCode()) {
		case KeyEvent.VK_CONTROL:
			ctrlDown = pressed;
			return false;
		case KeyEvent.VK_ALT:
			altDown = pressed;
			return false;
		case KeyEvent.VK_CAPS_LOCK:
			if (pressed && this.opt.caps_sends_up_and_down)
				capsLockOn = !capsLockOn;
			if (!this.opt.caps_sends_up_and_down) {
				if (pressed)
					capsLockOn = true;
				else
					capsLockOn = false;
			}
			return false;
		case KeyEvent.VK_NUM_LOCK:
			if (pressed)
				numLockOn = !numLockOn;
			return false;
		case KeyEvent.VK_SCROLL_LOCK:
			if (pressed)
				scrollLockOn = !scrollLockOn;
			return false;
		case KeyEvent.VK_PAUSE: // untested
			if (pressed) { // E1 1D 45 E1 9D C5
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYPRESS,
						0xe1, 0);
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYPRESS,
						0x1d, 0);
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYPRESS,
						0x45, 0);
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYPRESS,
						0xe1, 0);
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYPRESS,
						0x9d, 0);
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYPRESS,
						0xc5, 0);
			} else { // release left ctrl
				rdp.sendInput((int) time, RDP_INPUT_SCANCODE, RDP_KEYRELEASE,
						0x1d, 0);
			}
			break;
		case KeyEvent.VK_WINDOWS:
			int scancode = KBD_KEY_WINDOWS_LEFT;
			if (e.getKeyLocation() == KeyEvent.KEY_LOCATION_RIGHT)
				scancode = KBD_KEY_WINDOWS_RIGHT;

			this.sendScancode(time, pressed ? RDP_KEYPRESS : RDP_KEYRELEASE, scancode);
			break;

		// Removed, as java on MacOS send the option key as VK_META
		/*
		 * case KeyEvent.VK_META: // Windows key logger.debug("Windows key
		 * received"); if(pressed){ sendScancode(time, RDP_KEYPRESS, 0x1d); //
		 * left ctrl sendScancode(time, RDP_KEYPRESS, 0x01); // escape } else{
		 * sendScancode(time, RDP_KEYRELEASE, 0x01); // escape
		 * sendScancode(time, RDP_KEYRELEASE, 0x1d); // left ctrl } break;
		 */

		// haven't found a way to detect BREAK key in java - VK_BREAK doesn't
		// exist
		/*
		 * case KeyEvent.VK_BREAK: if(pressed){
		 * sendScancode(time,RDP_KEYPRESS,(KeyCode.SCANCODE_EXTENDED | 0x46));
		 * sendScancode(time,RDP_KEYPRESS,(KeyCode.SCANCODE_EXTENDED | 0xc6)); } //
		 * do nothing on release break;
		 */
		default:
			return false; // not handled - use sendScancode instead
		}
		return true; // handled - no need to use sendScancode
	}

    /**
     * Turn off any locking key, check states if available
     */
	public void triggerReadyToSend() {
		capsLockOn = false;
		numLockOn = false;
		scrollLockOn = false;
		doLockKeys(); // ensure lock key states are correct
	}

	protected void doLockKeys() {
	}

	/**
	 * Send CTRL-ALT-DEL combination.
	 */
	public void sendCtrlAltDel()
	{
		sendScancode(getTime(), RDP_KEYPRESS, 0x1d);	// CTRL
		sendScancode(getTime(), RDP_KEYPRESS, 0x38);	// ALT
		sendScancode(getTime(), RDP_KEYPRESS,
			     0x53 | KeyCode.SCANCODE_EXTENDED);	// DEL

		sendScancode(getTime(), RDP_KEYRELEASE,
			     0x53 | KeyCode.SCANCODE_EXTENDED);	// DEL
		sendScancode(getTime(), RDP_KEYRELEASE, 0x38);	// ALT
		sendScancode(getTime(), RDP_KEYRELEASE, 0x1d);	// CTRL
	}

    /**
     * Handle pressing of the middle mouse button, sending relevent event data to the server
     * @param e MouseEvent detailing circumstances under which middle button was pressed
     */
	protected void middleButtonPressed(MouseEvent e) {
		/*
		 * if (Options.paste_hack && ctrlDown){ try{ canvas.setBusyCursor();
		 * }catch (RdesktopException ex){ logger.warn(ex.getMessage()); } if
		 * (capsLockOn){ logger.debug("Turning caps lock off for paste"); //
		 * turn caps lock off sendScancode(getTime(), RDP_KEYPRESS, 0x3a); //
		 * caps lock sendScancode(getTime(), RDP_KEYRELEASE, 0x3a); // caps lock }
		 * paste(); if (capsLockOn){ // turn caps lock back on
		 * logger.debug("Turning caps lock back on after paste");
		 * sendScancode(getTime(), RDP_KEYPRESS, 0x3a); // caps lock
		 * sendScancode(getTime(), RDP_KEYRELEASE, 0x3a); // caps lock }
		 * canvas.unsetBusyCursor(); } else
		 */
		rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON3
				| MOUSE_FLAG_DOWN, e.getX(), e.getY());
	}

    /**
     * Handle release of the middle mouse button, sending relevent event data to the server
     * @param e MouseEvent detailing circumstances under which middle button was released
     */
	protected void middleButtonReleased(MouseEvent e) {
		/* if (!Options.paste_hack || !ctrlDown) */
		rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON3, e.getX(), e
				.getY());
	}

	class RdesktopMouseAdapter extends MouseAdapter {

		public RdesktopMouseAdapter() {
			super();
		}

		public void mousePressed(MouseEvent e) {
			if(e.getY() != 0 && canvas.getParent().getClass().getName() == "RdesktopFrame_Localised") ((RdesktopFrame_Localised) canvas.getParent()).hideMenu();
			
			int time = getTime();
			if (rdp != null) {
				if ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
					logger.debug("Mouse Button 1 Pressed.");
					rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON1
							| MOUSE_FLAG_DOWN, e.getX(), e.getY());
				} else if ((e.getModifiers() & InputEvent.BUTTON3_MASK) == InputEvent.BUTTON3_MASK) {
					logger.debug("Mouse Button 3 Pressed.");
					rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON2
							| MOUSE_FLAG_DOWN, e.getX(), e.getY());
				} else if ((e.getModifiers() & InputEvent.BUTTON2_MASK) == InputEvent.BUTTON2_MASK) {
					logger.debug("Middle Mouse Button Pressed.");
					middleButtonPressed(e);
				}
			}
		}

		public void mouseReleased(MouseEvent e) {
			int time = getTime();
			if (rdp != null) {
				if ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
					rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON1, e
							.getX(), e.getY());
				} else if ((e.getModifiers() & InputEvent.BUTTON3_MASK) == InputEvent.BUTTON3_MASK) {
					rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON2, e
							.getX(), e.getY());
				} else if ((e.getModifiers() & InputEvent.BUTTON2_MASK) == InputEvent.BUTTON2_MASK) {
					middleButtonReleased(e);
				}
			}
		}

		public void mouseWheelMoved(MouseWheelEvent e) {
			int flag;
			if (e.getWheelRotation() < 0) 
				flag = MOUSE_FLAG_BUTTON4;
			else
				flag = MOUSE_FLAG_BUTTON5;

			rdp.sendInput(time, RDP_INPUT_MOUSE, flag, 1, 1);
		}
	}

	class RdesktopMouseMotionAdapter extends MouseMotionAdapter {

		public RdesktopMouseMotionAdapter() {
			super();
		}

		public void mouseMoved(MouseEvent e) {
			int time = getTime();
           
            // Code to limit mouse events to 4 per second. Doesn't seem to affect performance
           // long mTime = System.currentTimeMillis();          
           // if((mTime - Input.last_mousemove) < 250) return;
           //Input.last_mousemove = mTime;
            
			// if(logger.isInfoEnabled()) logger.info("mouseMoved to
			// "+e.getX()+", "+e.getY()+" at "+time);
			
			// TODO: complete menu show/hide section
/*
			if(e.getY() == 0) ((RdesktopFrame_Localised) canvas.getParent()).showMenu();
			else ((RdesktopFrame_Localised) canvas.getParent()).hideMenu();
*/
			
			if (rdp != null) {
				rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_MOVE, e.getX(),
						e.getY());
			}
		}

		public void mouseDragged(MouseEvent e) {
			int time = getTime();
			// if(logger.isInfoEnabled()) logger.info("mouseMoved to
			// "+e.getX()+", "+e.getY()+" at "+time);
			if (rdp != null) {
				rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_MOVE, e.getX(),
						e.getY());
			}
		}
	}

}
