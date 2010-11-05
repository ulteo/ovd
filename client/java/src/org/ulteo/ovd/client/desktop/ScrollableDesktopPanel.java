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
import java.awt.event.MouseAdapter;
import javax.swing.InputMap;
import javax.swing.JComponent;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.KeyStroke;
import net.propero.rdp.Input;
import net.propero.rdp.RdesktopCanvas;

public class ScrollableDesktopPanel extends JScrollPane {

	private static final int SCROLLBAR_INCREMENT_UNIT = 20;
	private RdesktopCanvas canvas = null;
	private JPanel view = null;

	public ScrollableDesktopPanel(RdesktopCanvas canvas_) {
		this.canvas = canvas_;

		this.init();
	}

	private void init() {
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

		this.getViewport().setView(this.view);
		this.setPreferredSize(this.canvas.getPreferredSize());
		InputMap im = this.getInputMap(JComponent.WHEN_ANCESTOR_OF_FOCUSED_COMPONENT);
		im.put(KeyStroke.getKeyStroke("UP"), "none");
		im.put(KeyStroke.getKeyStroke("DOWN"), "none");
		im.put(KeyStroke.getKeyStroke("LEFT"), "none");
		im.put(KeyStroke.getKeyStroke("RIGHT"), "none");
		this.getHorizontalScrollBar().setUnitIncrement(SCROLLBAR_INCREMENT_UNIT);
		this.getVerticalScrollBar().setUnitIncrement(SCROLLBAR_INCREMENT_UNIT);
	}

	public JPanel getView() {
		return this.view;
	}
}
