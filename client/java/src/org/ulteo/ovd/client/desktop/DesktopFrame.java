/*
 * Copyright (C) 2010-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
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

package org.ulteo.ovd.client.desktop;

import java.awt.Dimension;
import java.awt.Image;
import java.awt.Insets;
import java.awt.Rectangle;
import java.awt.Toolkit;
import java.awt.event.InputEvent;
import java.awt.event.KeyEvent;

import javax.swing.JFrame;
import javax.swing.KeyStroke;
import net.propero.rdp.InputListener;
import net.propero.rdp.RdesktopCanvas;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;

import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.OvdClientDesktop;
import org.ulteo.ovd.client.OvdClientFrame;
import org.ulteo.utils.jni.WorkArea;

public class DesktopFrame extends OvdClientFrame implements InputListener {

	private Image logo = null;
	public static int screenWidth = Toolkit.getDefaultToolkit().getScreenSize().width;
	public static int screenHeight = Toolkit.getDefaultToolkit().getScreenSize().height;
	public static Rectangle workarea_rect = WorkArea.getWorkAreaSize();
	public static Dimension SMALL_RES = new Dimension(800,600);
	public static Dimension MEDUIM_RES = new Dimension(1024,768);
	public static Dimension HIGH_RES = new Dimension(1280,678);
	public static Dimension MAXIMISED = new Dimension(workarea_rect.width, workarea_rect.height);
	public static Dimension FULLSCREEN = new Dimension(screenWidth, screenHeight);
	public static Dimension DEFAULT_RES = DesktopFrame.FULLSCREEN;

	private boolean fullscreen = false;
	private RdesktopCanvas canvas = null;
	private ScrollableDesktopFrame scrollFrame = null;
	private KeyStroke fullscreen_keystroke = null;
	
	public DesktopFrame(Dimension resolution, OvdClientDesktop client) {
		super((NativeClientActions)client);
		this.fullscreen = (resolution.width == client.getScreenSize().width &&
				resolution.height == client.getScreenSize().height);
		this.logo = getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		setIconImage(logo);
		setSize(resolution);
		setPreferredSize(resolution);
		this.setTitle("Ulteo Remote Desktop");
		setResizable(false);
		setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		setLocation(0, 0);
		setVisible(false);

		if (this.fullscreen) {
			SwingTools.invokeLater(GUIActions.setAlwaysOnTop(this, this.fullscreen));
			this.setUndecorated(this.fullscreen);
			this.fullscreen_keystroke = KeyStroke.getKeyStroke(KeyEvent.VK_ENTER, InputEvent.CTRL_DOWN_MASK);
			this.scrollFrame = new ScrollableDesktopFrame(this);
		} else {
			this.setLocationRelativeTo(null);
		}
		
		pack();
	}

	/**
	 * say if this desktop is displayed in fullscreen size
	 * @return is fullscreened display
	 */
	public boolean isFullscreen() {
		return this.fullscreen;
	}
	
	public void destroy() {
		if (this.fullscreen) {
			this.scrollFrame.setVisible(false);
			this.scrollFrame.dispose();
			this.scrollFrame = null;
		}

		this.setVisible(false);
		this.dispose();
	}
	
	/**
	 * return size of the Frame without counting the external inset
	 * @return
	 * 		internal dimension of the frame
	 */
	public Dimension getInternalSize() {
		Insets inset = this.getInsets();
		return new Dimension(this.getWidth() - (inset.left + inset.right) + 2,
				this.getHeight() - (inset.bottom + inset.top) + 2);
	}
	
	public void setCanvas(RdesktopCanvas canvas_) {
		this.canvas = canvas_;

		if (! this.fullscreen)
			return;

		this.canvas.getInput().addKeyStroke(this.fullscreen_keystroke);
		this.canvas.getInput().addInputListener(this);
		this.scrollFrame.setCanvas(this.canvas);

		GUIActions.setFullscreen(this);
	}

	private void escapeFromFullsreen() {
		if (! this.fullscreen || this.scrollFrame == null || ! this.isVisible() || this.scrollFrame.isVisible())
			return;

		GUIActions.unsetFullscreen(this);

		this.setVisible(false);
		this.scrollFrame.setVisible(true);
	}

	private void switchToFullsreen() {
		if (! this.fullscreen || this.scrollFrame == null || this.isVisible() || ! this.scrollFrame.isVisible())
			return;

		this.scrollFrame.setVisible(false);
		this.setVisible(true);
	}

	@Override
	public void keyStrokePressed(KeyStroke keystroke, KeyEvent ke) {
		if (! keystroke.equals(this.fullscreen_keystroke))
			return;
		
		if (ke.getComponent() == this.canvas) {
			this.escapeFromFullsreen();
		}
		else if (ke.getComponent() == this.scrollFrame.getView()) {
			this.switchToFullsreen();
		}
	}
	
}
