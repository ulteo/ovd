/* RdesktopFrame.java
 * Component: ProperJavaRDP
 * 
 * Revision: $Revision: 1.3 $
 * Author: $Author: suvarov $
 * Date: $Date: 2007/03/15 23:18:35 $
 *
 * Copyright (c) 2005 Propero Limited
 *
 * Purpose: Window for RDP session
 */
package net.propero.rdp;

import java.awt.*;
import java.awt.event.*;

import org.apache.log4j.Logger;

import net.propero.rdp.keymapping.KeyCode_FileBased;
import net.propero.rdp.menu.RdpMenu;
import net.propero.rdp.rdp5.cliprdr.ClipChannel;

//import javax.swing.Box;

public abstract class RdesktopFrame extends Frame {  
    
	static Logger logger = Logger.getLogger(RdesktopFrame.class);

	public RdesktopCanvas canvas = null;

	public Rdp rdp = null;

	public RdpMenu menu = null;
	
	protected Options opt = null;
	protected Common common = null;

    /**
     * Register the clipboard channel
     * @param c ClipChannel object for controlling clipboard mapping
     */
	public void setClip(ClipChannel c) {
		canvas.addFocusListener(c);
	}

//	/**
//	 * @deprecated ActionListener should be used instead.
//	 */
//
//	public boolean action(Event event, Object arg) {
//		if (menu != null)
//			return menu.action(event, arg);
//		return false;
//	}


	protected boolean inFullscreen = false;

    /**
     * Switch to fullscreen mode
     */
	public void goFullScreen() {
		inFullscreen = true;
	}

    /**
     * Exit fullscreen mode
     */
	public void leaveFullScreen() {
		inFullscreen = false;
	}

    /**
     * Switch in/out of fullscreen mode
     */
	public void toggleFullScreen() {
		if (inFullscreen)
			leaveFullScreen();
		else
			goFullScreen();
	}

	private boolean menuVisible = false;

    /**
     * Display the menu bar
     */
	public void showMenu(){
		if (menu == null)
			menu = new RdpMenu(this, this.common);

		if (!menuVisible && this.opt.enable_menu)
			this.setMenuBar(menu);
		canvas.repaint();
		menuVisible = true;
	}
	
    /**
     * Hide the menu bar
     */
	public void hideMenu(){
		if(menuVisible && this.opt.enable_menu) this.setMenuBar(null);
		//canvas.setSize(this.WIDTH, this.HEIGHT);
		canvas.repaint();
		menuVisible = false;
	}
	
	/**
     * Toggle the menu on/off (show if hidden, hide if visible)
     *
	 */
	public void toggleMenu() {
		if(!menuVisible) showMenu();
		else hideMenu();
	}

    /**
     * Create a new RdesktopFrame.
     * Size defined by this.opt.width and this.opt.height
     * Creates RdesktopCanvas occupying entire frame
     */
	public RdesktopFrame(Options opt_, Common common_) {
		super();
		this.opt = opt_;
		this.common = common_;
		
		setLayout(new FlowLayout(FlowLayout.CENTER, 0, 0));

		menu = new RdpMenu(this, this.common);
		setMenuBar(menu);

		this.common.frame = this;
		this.canvas = new RdesktopCanvas_Localised(this.opt, this.common);
		add(this.canvas);
		setTitle(this.opt.windowTitle);

		if (Constants.OS == Constants.WINDOWS)
			setResizable(false);
		// Windows has to setResizable(false) before pack,
		// else draws on the frame

		if (this.opt.fullscreen) {
			goFullScreen();
			pack();
			setLocation(0, 0);
		} else {// centre
			pack();
			centreWindow();
		}

		logger.info("canvas:" + canvas.getSize());
		logger.info("frame: " + getSize());
		logger.info("insets:" + getInsets());

		if (Constants.OS != Constants.WINDOWS)
			setResizable(false);
		// Linux Java 1.3 needs pack() before setResizeable

		addWindowListener(new RdesktopWindowAdapter(this.opt));
        canvas.addFocusListener(new RdesktopFocusListener(canvas, this.opt));
        if (Constants.OS == Constants.WINDOWS) {
			// redraws screen on window move
			addComponentListener(new RdesktopComponentAdapter(this.opt));
		}

		canvas.requestFocus();
	}


    /**
     * Retrieve the canvas contained within this frame
     * @return RdesktopCanvas object associated with this frame
     */
	public RdesktopCanvas getCanvas() {
		return this.canvas;
	}

    /**
     * Register the RDP communications layer with this frame
     * @param rdp Rdp object encapsulating the RDP comms layer
     */
	public void registerCommLayer(Rdp rdp) {
		this.rdp = rdp;
		canvas.registerCommLayer(rdp);
	}

    /**
     * Register keymap
     * @param keys Keymapping object for use in handling keyboard events
     */
	public void registerKeyboard(KeyCode_FileBased keys) {
		canvas.registerKeyboard(keys);
	}
    
	class RdesktopWindowAdapter extends WindowAdapter {
		private Options opt = null;
		
		public RdesktopWindowAdapter(Options opt_) {
			this.opt = opt_;
		}

		public void windowClosing(WindowEvent e) {
			hide();
			Rdesktop.exit(0, rdp, (RdesktopFrame) e.getWindow(), true);
		}

		public void windowLostFocus(WindowEvent e) {
            logger.info("windowLostFocus");
			// lost focus - need clear keys that are down
			canvas.lostFocus();
		}

		public void windowDeiconified(WindowEvent e) {
			if (Constants.OS == Constants.WINDOWS) {
				// canvas.repaint();
				canvas.repaint(0, 0, this.opt.width, this.opt.height);
			}
			canvas.gainedFocus();
		}

		public void windowActivated(WindowEvent e) {
			if (Constants.OS == Constants.WINDOWS) {
				// canvas.repaint();
				canvas.repaint(0, 0, this.opt.width, this.opt.height);
			}
			// gained focus..need to check state of locking keys
			canvas.gainedFocus();
		}

		public void windowGainedFocus(WindowEvent e) {
			if (Constants.OS == Constants.WINDOWS) {
				// canvas.repaint();
				canvas.repaint(0, 0, this.opt.width, this.opt.height);
			}
			// gained focus..need to check state of locking keys
			canvas.gainedFocus();
		}
	}

	class RdesktopComponentAdapter extends ComponentAdapter {
		private Options opt = null;
		
		public RdesktopComponentAdapter(Options opt_) {
			this.opt = opt_;
		}

		public void componentMoved(ComponentEvent e) {
			canvas.repaint(0, 0, this.opt.width, this.opt.height);
		}
	}

	class YesNoDialog extends Dialog implements ActionListener {

		Button yes, no;

		boolean retry = false;

		public YesNoDialog(Frame parent, String title, String[] message) {
			super(parent, title, true);
			// Box msg = Box.createVerticalBox();
			// for(int i=0; i<message.length; i++) msg.add(new
			// Label(message[i],Label.CENTER));
			// this.add("Center",msg);
			Panel msg = new Panel();
			msg.setLayout(new GridLayout(message.length, 1));
			for (int i = 0; i < message.length; i++)
				msg.add(new Label(message[i], Label.CENTER));
			this.add("Center", msg);

			Panel p = new Panel();
			p.setLayout(new FlowLayout());
			yes = new Button("Yes");
			yes.addActionListener(this);
			p.add(yes);
			no = new Button("No");
			no.addActionListener(this);
			p.add(no);
			this.add("South", p);
			this.pack();
			if (getSize().width < 240)
				setSize(new Dimension(240, getSize().height));

			centreWindow(this);
		}

		public void actionPerformed(ActionEvent e) {
			if (e.getSource() == yes)
				retry = true;
			else
				retry = false;
			this.hide();
			this.dispose();
		}
	}

	class OKDialog extends Dialog implements ActionListener {
		public OKDialog(Frame parent, String title, String[] message) {

			super(parent, title, true);
			// Box msg = Box.createVerticalBox();
			// for(int i=0; i<message.length; i++) msg.add(new
			// Label(message[i],Label.CENTER));
			// this.add("Center",msg);

			Panel msg = new Panel();
			msg.setLayout(new GridLayout(message.length, 1));
			for (int i = 0; i < message.length; i++)
				msg.add(new Label(message[i], Label.CENTER));
			this.add("Center", msg);

			Panel p = new Panel();
			p.setLayout(new FlowLayout());
			Button ok = new Button("OK");
			ok.addActionListener(this);
			p.add(ok);
			this.add("South", p);
			this.pack();

			if (getSize().width < 240)
				setSize(new Dimension(240, getSize().height));

			centreWindow(this);
		}

		public void actionPerformed(ActionEvent e) {
			this.hide();
			this.dispose();
		}
	}

    /**
     * Display an error dialog with "Yes" and "No" buttons and the title "properJavaRDP error"
     * @param msg Array of message lines to display in dialog box
     * @return True if "Yes" was clicked to dismiss box
     */
	public boolean showYesNoErrorDialog(String[] msg) {

		YesNoDialog d = new YesNoDialog(this, "UlteoRDP error", msg);
		d.show();
		return d.retry;
	}

    /**
     * Display an error dialog with the title "properJavaRDP error"
     * @param msg Array of message lines to display in dialog box
     */
	public void showErrorDialog(String[] msg) {
		Dialog d = new OKDialog(this, "UlteoRDP error", msg);
		d.show();
	}

    /**
     * Notify the canvas that the connection is ready for sending messages
     */
	public void triggerReadyToSend() {
		//this.show();
		canvas.triggerReadyToSend();
	}

    /**
     * Centre a window to the screen
     * @param f Window to be centred
     */
	public void centreWindow(Window f) {
		Dimension screen_size = Toolkit.getDefaultToolkit().getScreenSize();
		Dimension window_size = f.getSize();
		int x = (screen_size.width - window_size.width) / 2;
		if (x < 0)
			x = 0; // window can be bigger than screen
		int y = (screen_size.height - window_size.height) / 2;
		if (y < 0)
			y = 0; // window can be bigger than screen
		f.setLocation(x, y);
	}

    /**
     * Centre this window
     */
	public void centreWindow() {
		centreWindow(this);
	}

}
