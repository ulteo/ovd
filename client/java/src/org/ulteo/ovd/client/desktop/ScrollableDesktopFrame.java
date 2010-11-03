/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
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

import java.awt.Graphics;
import java.awt.Rectangle;
import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.awt.event.MouseAdapter;
import javax.swing.InputMap;
import javax.swing.JComponent;
import javax.swing.JFrame;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.KeyStroke;
import net.propero.rdp.Input;
import net.propero.rdp.RdesktopCanvas;

public class ScrollableDesktopFrame extends JFrame implements ComponentListener {

	private static final int SCROLLBAR_INCREMENT_UNIT = 20;

	private DesktopFrame desktopFrame = null;
	private RdesktopCanvas canvas = null;
	private JPanel view = null;
	private JScrollPane scrollPane = null;

	public ScrollableDesktopFrame(DesktopFrame desktopFrame_) {
		super("Ulteo Remote Desktop");

		this.desktopFrame = desktopFrame_;

		this.setVisible(false);
		this.setUndecorated(false);
		this.setSize(DesktopFrame.MAXIMISED);
		this.setResizable(true);
		this.setDefaultCloseOperation(JFrame.DO_NOTHING_ON_CLOSE);
		this.setIconImage(this.desktopFrame.getIconImage());

		this.addWindowListener(this.desktopFrame);
		this.addComponentListener(this);
	}

	public void setCanvas(RdesktopCanvas canvas_) {
		this.canvas = canvas_;

		Input input = this.canvas.getInput();
		
		this.view = new JPanel() {
			@Override
			protected void paintComponent(Graphics g) {
				super.paintComponent(g);

				Rectangle r = g.getClipBounds();
				g.drawImage(canvas.backstore.getSubimage(r.x,r.y,r.width,r.height),r.x,r.y,null);
			}
		};
		this.canvas.addComponentListener(this.view);

		MouseAdapter mouseAdapter = input.getMouseAdapter();
		this.view.addMouseListener(mouseAdapter);
		this.view.addMouseWheelListener(mouseAdapter);
		this.view.addMouseMotionListener(input.getMouseMotionAdapter());

		this.view.setFocusable(true);
		this.view.addKeyListener(input.getKeyAdapter());
		this.view.setPreferredSize(this.canvas.getPreferredSize());

		this.scrollPane = new JScrollPane();
		this.scrollPane.getViewport().setView(this.view);
		this.setPreferredSize(this.canvas.getPreferredSize());
		InputMap im = this.scrollPane.getInputMap(JComponent.WHEN_ANCESTOR_OF_FOCUSED_COMPONENT);
		im.put(KeyStroke.getKeyStroke("UP"), "none");
		im.put(KeyStroke.getKeyStroke("DOWN"), "none");
		im.put(KeyStroke.getKeyStroke("LEFT"), "none");
		im.put(KeyStroke.getKeyStroke("RIGHT"), "none");
		this.scrollPane.getHorizontalScrollBar().setUnitIncrement(SCROLLBAR_INCREMENT_UNIT);
		this.scrollPane.getVerticalScrollBar().setUnitIncrement(SCROLLBAR_INCREMENT_UNIT);
	}

	public JPanel getView() {
		return this.view;
	}

	public void componentResized(ComponentEvent ce) {}
	public void componentMoved(ComponentEvent ce) {}
	public void componentShown(ComponentEvent ce) {
		this.getContentPane().removeAll();
		this.getContentPane().add(this.scrollPane);
		this.getContentPane().validate();
		
		this.view.requestFocus();
	}
	public void componentHidden(ComponentEvent ce) {}
}
