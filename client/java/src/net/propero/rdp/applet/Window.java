package net.propero.rdp.applet;

import java.awt.*;
import java.awt.event.*;
import java.net.*;

import net.propero.rdp.*;
import net.propero.rdp.rdp5.*;

public class Window extends java.applet.Applet 
	implements Runnable, KeyListener, MouseListener, MouseMotionListener, MouseWheelListener {

	protected static boolean capsLockOn = false;
	protected static boolean numLockOn = false;
	protected static boolean scrollLockOn = false;

	protected static boolean serverAltDown = false;
    protected static boolean altDown = false;
	protected static boolean ctrlDown = false;

    protected static long last_mousemove = 0;
    
	// Using this flag value (0x0001) seems to do nothing, and after running
	// through other possible values, the RIGHT flag does not appear to be
	// implemented
	protected static final int KBD_FLAG_RIGHT = 0x0001;
	protected static final int KBD_FLAG_EXT = 0x0100;

	// QUIET flag is actually as below (not 0x1000 as in rdesktop)
	protected static final int KBD_FLAG_QUIET = 0x200;
	protected static final int KBD_FLAG_DOWN = 0x4000;
	protected static final int KBD_FLAG_UP = 0x8000;

	protected static final int RDP_KEYPRESS = 0;
	protected static final int RDP_KEYRELEASE = KBD_FLAG_DOWN | KBD_FLAG_UP;
	protected static final int MOUSE_FLAG_MOVE = 0x0800;

	protected static final int MOUSE_FLAG_BUTTON1 = 0x1000;
	protected static final int MOUSE_FLAG_BUTTON2 = 0x2000;
	protected static final int MOUSE_FLAG_BUTTON3 = 0x4000;

	protected static final int MOUSE_FLAG_BUTTON4 = 0x0280; // wheel up -
	                                                        // rdesktop 1.2.0
	protected static final int MOUSE_FLAG_BUTTON5 = 0x0380; // wheel down -
															// rdesktop 1.2.0
	protected static final int MOUSE_FLAG_DOWN = 0x8000;

    protected static final int RDP_INPUT_SYNCHRONIZE = 0;
	protected static final int RDP_INPUT_CODEPOINT = 1;
	protected static final int RDP_INPUT_VIRTKEY = 2;
	protected static final int RDP_INPUT_SCANCODE = 4;
	protected static final int RDP_INPUT_MOUSE = 0x8001;

	int id,x,y,width,height;
	RdpApplet rdpapplet;
	Rdp5 rdp;
	Input input;
	Thread t;

	public void openUrl(String url) {
		try {
			getAppletContext().showDocument (new URL(url));
		} catch(Exception e) {
			System.out.println(e.toString());
		}
	}

	public void init() {
		x = Integer.parseInt(getParameter("X"));
		y = Integer.parseInt(getParameter("Y"));
		width = Integer.parseInt(getParameter("W"));
		height = Integer.parseInt(getParameter("H"));
		id = Integer.parseInt(getParameter("ID"));
		
		rdpapplet = rdpapplet.refApplet;
		rdp = Rdesktop.common.rdp;
		input = Rdesktop.common.frame.getCanvas().getInput();

		addKeyListener(this);
		addMouseListener(this);
		addMouseMotionListener(this);
		addMouseWheelListener(this);

	}

	public void setParams(int id,int x,int y,int width,int height) {
		this.id = id;
		this.x = x;
		this.y = y;
		this.width = width;
		this.height = height;
	}
	
	public void update(Graphics g) {
		paint(g);
	}

	public void paint(Graphics g) {
		int x = Math.max(this.x,0);
		int y = Math.max(this.y,0);
		int w = Math.min(width,rdpapplet.backstore.getWidth()-x);
		int h = Math.min(height,rdpapplet.backstore.getHeight()-y);
		int dx = (this.x<0?-this.x:0);
		int dy = (this.y<0?-this.y:0);
		if (w>0 && h>0)
			g.drawImage(rdpapplet.backstore.getSubimage(x,y,w,h), dx , dy,null);
	}

	public void start() {
		openUrl("javascript:rdpClientLoaded('"+id+"')");
		t = new Thread(this);
		t.start();
	}

	public void run() {
		Thread thisThread = Thread.currentThread();
		while(thisThread == t) {
			repaint();
			try {
				thisThread.sleep(50);
			} catch(Exception e) {
				System.out.println("Window Applet error : " + e.toString() );
			}
		}
	}

	public void stop() {
		t=null;
	}
	public void destroy() {
	}
	
	public void keyPressed(KeyEvent e) {
		input.lastKeyEvent = e;
		input.modifiersValid = true;
		long time = net.propero.rdp.Input.getTime();

		input.pressedKeys.addElement(new Integer(e.getKeyCode()));

		if (rdp != null) {
			if (!input.handleSpecialKeys(time, e, true)) {
				input.sendKeyPresses(input.newKeyMapper.getKeyStrokes(e));
			}
			// sendScancode(time, RDP_KEYPRESS, keys.getScancode(e));
		}
	}

	public void keyReleased(KeyEvent e) {
		Integer keycode = new Integer(e.getKeyCode());
		if (!input.pressedKeys.contains(keycode)) {
			this.keyPressed(e);
		}

		input.pressedKeys.removeElement(keycode);

		input.lastKeyEvent = e;
		input.modifiersValid = true;
		long time = net.propero.rdp.Input.getTime();

		input.pressedKeys.addElement(new Integer(e.getKeyCode()));

		if (rdp != null) {
			if (!input.handleSpecialKeys(time, e, true))
				input.sendKeyPresses(input.newKeyMapper.getKeyStrokes(e));
			// sendScancode(time, RDP_KEYPRESS, keys.getScancode(e));
		}
	
	}

	public void keyTyped(KeyEvent e) {
		input.lastKeyEvent = e;
		input.modifiersValid = true;
		long time = net.propero.rdp.Input.getTime();

		if (rdp != null) {
			if (!input.handleSpecialKeys(time, e, false))
				input.sendKeyPresses(input.newKeyMapper.getKeyStrokes(e));
			// sendScancode(time, RDP_KEYRELEASE, keys.getScancode(e));
		}
	}

	public void mousePressed(MouseEvent e) {
		e.translatePoint(x,y);
		//if(e.getY() != 0) ((RdesktopFrame_Localised) canvas.getParent()).hideMenu();
			
		int time = net.propero.rdp.Input.getTime();
		if (rdp != null) {
			if ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
				rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON1
						| MOUSE_FLAG_DOWN, e.getX(), e.getY());
			} else if ((e.getModifiers() & InputEvent.BUTTON3_MASK) == InputEvent.BUTTON3_MASK) {
				rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON2
						| MOUSE_FLAG_DOWN, e.getX(), e.getY());
			} else if ((e.getModifiers() & InputEvent.BUTTON2_MASK) == InputEvent.BUTTON2_MASK) {
			//	middleButtonPressed(e);
			}
		}
	}

	public void mouseReleased(MouseEvent e) {
		e.translatePoint(x,y);
		int time = net.propero.rdp.Input.getTime();
		if (rdp != null) {
			if ((e.getModifiers() & InputEvent.BUTTON1_MASK) == InputEvent.BUTTON1_MASK) {
				rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON1, e
						.getX(), e.getY());
			} else if ((e.getModifiers() & InputEvent.BUTTON3_MASK) == InputEvent.BUTTON3_MASK) {
				rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_BUTTON2, e
						.getX(), e.getY());
			} else if ((e.getModifiers() & InputEvent.BUTTON2_MASK) == InputEvent.BUTTON2_MASK) {
			//	middleButtonReleased(e);
			}
		}
	}

	public void mouseMoved(MouseEvent e) {
		e.translatePoint(x,y);
		int time = net.propero.rdp.Input.getTime();
           
		if (rdp != null) {
			rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_MOVE, e.getX(),
					e.getY());
		}
	}

	public void mouseDragged(MouseEvent e) {
		e.translatePoint(x,y);
		int time = net.propero.rdp.Input.getTime();
		if (rdp != null) {
			rdp.sendInput(time, RDP_INPUT_MOUSE, MOUSE_FLAG_MOVE, e.getX(),
					e.getY());
		}
	}

	public void mouseWheelMoved(MouseWheelEvent evt) {
		//evt.translatePoint(x,y);
		//vnc.vc.processLocalMouseWheelEvent(evt);
	}


	//
	// Ignored events.
	//

	public void mouseClicked(MouseEvent evt) {}
	public void mouseEntered(MouseEvent evt) {}
	public void mouseExited(MouseEvent evt) {}	
}

