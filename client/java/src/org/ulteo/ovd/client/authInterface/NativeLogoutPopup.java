/*
 * Copyright (C) 2010-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
 * Author Thomas MOUTON <thomas@ulteo.com> 2011-2012
 * Author Samuel BOVEE <samuel@ulteo.com> 2011
 * Author Omar AKHAM <oakham@ulteo.com> 2011
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

package org.ulteo.ovd.client.authInterface;

import java.awt.AWTKeyStroke;
import java.awt.Component;
import java.awt.Container;
import java.awt.Frame;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.GridLayout;
import java.awt.Image;
import java.awt.Insets;
import java.awt.KeyboardFocusManager;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.InputEvent;
import java.awt.event.KeyAdapter;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;
import java.util.ArrayList;
import java.util.Collections;
import java.util.HashSet;
import javax.swing.Box;
import javax.swing.ButtonGroup;
import javax.swing.Icon;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JRadioButton;
import org.ulteo.gui.GUIActions;
import org.ulteo.gui.SwingTools;
import org.ulteo.ovd.client.NativeClientActions;
import org.ulteo.ovd.client.OvdClient;
import org.ulteo.ovd.client.OvdClientFrame;
import org.ulteo.utils.I18n;

public class NativeLogoutPopup extends JDialog implements ActionListener {
	private OvdClientFrame frame = null;
	private NativeClientActions actions = null;
	
	private JRadioButton logoffRadio = null;
	private JRadioButton disconnectRadio = null;
	private JButton cancelButton = null;
	private JButton okButton = null;
	private JCheckBox exitCheckBox = null;
	
	public NativeLogoutPopup(OvdClientFrame frame, NativeClientActions actions) {
		super(frame);
		
		this.frame = frame;
		this.actions = actions;
		
		this.initPopup();
	}

	private void initPopup() {
		// Initialize UI components
		this.logoffRadio = new JRadioButton(I18n._("Logoff"));
		this.disconnectRadio = new JRadioButton(I18n._("Disconnect"));
		
		ButtonGroup groupRadio = new ButtonGroup();
		groupRadio.add(this.disconnectRadio);
		groupRadio.add(this.logoffRadio);
		
		JPanel panelRadio = new JPanel(new GridLayout(0, 1));
		panelRadio.add(this.disconnectRadio);
		panelRadio.add(this.logoffRadio);
		
		this.exitCheckBox = new JCheckBox(I18n._("Close the application"));
		
		this.cancelButton = new JButton(I18n._("Cancel"));
		this.cancelButton.addActionListener(this);
		
		this.okButton = new JButton(I18n._("Ok"));
		this.okButton.addActionListener(this);
		
		Box buttonsHBox = Box.createHorizontalBox();
		buttonsHBox.add(this.okButton);
		buttonsHBox.add(this.cancelButton);
		
		// Fill the dialog container
		Container content = this.getContentPane();
		content.setLayout(new GridBagLayout());
		GridBagConstraints gbc = new GridBagConstraints();
		
		Image ulteoImage = GUIActions.getUlteoIcon();
		if (ulteoImage != null) {
			Icon ulteoIcon = new ImageIcon(ulteoImage);
			
			gbc.gridx = 0;
			gbc.gridy = 0;
			gbc.gridwidth = 1;
			gbc.gridheight = 2;
			gbc.anchor = GridBagConstraints.FIRST_LINE_START;
			gbc.insets = new Insets(10, 10, 0, 0);
			content.add(new JLabel(ulteoIcon), gbc);
		}
		
		gbc.gridx = 1;
		gbc.gridy = 0;
		gbc.gridwidth = 2;
		gbc.gridheight = 1;
		gbc.anchor = GridBagConstraints.FIRST_LINE_START;
		gbc.insets = new Insets(20, 10, 0, 0);
		content.add(panelRadio, gbc);
		
		gbc.gridx = 0;
		gbc.gridy = 1;
		gbc.gridwidth = 2;
		gbc.gridheight = 1;
		gbc.anchor = GridBagConstraints.FIRST_LINE_START;
		gbc.insets = new Insets(10, 15, 0, 0);
		content.add(this.exitCheckBox,gbc);
		
		gbc.gridx = 2;
		gbc.gridy = 2;
		gbc.gridwidth = 1;
		gbc.gridheight = 1;
		gbc.anchor = GridBagConstraints.LAST_LINE_END;
		gbc.insets = new Insets(5, 0, 0, 0);
		content.add(buttonsHBox,gbc);
		
		// Pre-select the disconnection mode
		if (this.actions.isPersistentSessionEnabled()) {
			this.disconnectRadio.setSelected(true);
		}
		else {
			this.disconnectRadio.setEnabled(false);
			this.logoffRadio.setSelected(true);
		}
		
		this.initKeyActions();
		
		// Configure the window
		this.setTitle("End the session");
		GUIActions.setIconImage(this, null).run();
		this.setModalityType(ModalityType.APPLICATION_MODAL);
		
		this.pack();
		this.setResizable(false);
		
		// Set the window placement
		Frame relativeFrame = null;
		if (this.frame.isShowing() && (this.frame.getExtendedState() & Frame.ICONIFIED) == 0)
			relativeFrame = this.frame;
		
		this.setLocationRelativeTo(relativeFrame);
	}
	
	private void setFocusTraversalKeys(Component c) {
		HashSet<AWTKeyStroke> backwardSet = new HashSet<AWTKeyStroke>();
		HashSet<AWTKeyStroke> forwardSet = new HashSet<AWTKeyStroke>();

		AWTKeyStroke forward = AWTKeyStroke.getAWTKeyStroke(KeyEvent.VK_TAB, 0);
		AWTKeyStroke backward = AWTKeyStroke.getAWTKeyStroke(KeyEvent.VK_TAB, InputEvent.SHIFT_DOWN_MASK);
		
		backwardSet.add(backward);
		forwardSet.add(forward);
		
		c.setFocusTraversalKeysEnabled(true);
		
		c.setFocusTraversalKeys(KeyboardFocusManager.BACKWARD_TRAVERSAL_KEYS, backwardSet);
		c.setFocusTraversalKeys(KeyboardFocusManager.FORWARD_TRAVERSAL_KEYS, forwardSet);

		c.setFocusTraversalKeys(KeyboardFocusManager.DOWN_CYCLE_TRAVERSAL_KEYS, Collections.EMPTY_SET);
		c.setFocusTraversalKeys(KeyboardFocusManager.UP_CYCLE_TRAVERSAL_KEYS, Collections.EMPTY_SET);
	}

	private void initKeyActions() {
		KeyListener enterKeyListener = new KeyAdapter() {
			@Override
			public synchronized void keyTyped(KeyEvent ke) {
				if ((ke.getKeyChar() == KeyEvent.VK_ENTER)) {
					okButton.doClick();
				}
			}
		};
		
		KeyListener cancelKeyListener = new KeyAdapter() {
			@Override
			public synchronized void keyTyped(KeyEvent ke) {
				if ((ke.getKeyChar() == KeyEvent.VK_ESCAPE)) {
					cancelButton.doClick();
				}
			}
		};
		
		ArrayList<Component> componentsList = new ArrayList<Component>();
		componentsList.add(this.disconnectRadio);
		componentsList.add(this.logoffRadio);
		componentsList.add(this.exitCheckBox);
		componentsList.add(this.okButton);
		componentsList.add(this.cancelButton);
		
		for (Component c : componentsList) {
			this.setFocusTraversalKeys(c);
			
			for (KeyListener each : c.getKeyListeners()) {
				c.removeKeyListener(each);
			}
			
			c.addKeyListener(cancelKeyListener);
			
			if (c == this.okButton || c == this.cancelButton)
				continue;	
			
			c.addKeyListener(enterKeyListener);
		}
		
		this.disconnectRadio.addKeyListener(new KeyAdapter() {
			@Override
			public void keyPressed(KeyEvent ke) {
				if ((ke.getKeyCode() == KeyEvent.VK_DOWN)) {
					logoffRadio.setSelected(true);
					SwingTools.invokeLater(GUIActions.requestFocus(logoffRadio));
				}
			}
		});
		this.logoffRadio.addKeyListener(new KeyAdapter() {
			@Override
			public synchronized void keyPressed(KeyEvent ke) {
				if ((ke.getKeyCode() == KeyEvent.VK_UP)) {
					disconnectRadio.setSelected(true);
					SwingTools.invokeLater(GUIActions.requestFocus(disconnectRadio));
				}
			}
		});
	}

	@Override
	public void actionPerformed(ActionEvent ae) {
		Object src = ae.getSource();
		
		boolean cancelled = false;
		boolean disconnected = true;
		if (src == this.cancelButton) {
			cancelled = true;
		}
		else if (src == this.okButton) {
			if (this.actions.isPersistentSessionEnabled() && this.disconnectRadio.isSelected()) {
				this.frame.setDisconnectionMode(OvdClient.DisconnectionMode.SUSPEND);
				disconnected = true;
			} else if (this.logoffRadio.isSelected()) {
				this.frame.setDisconnectionMode(OvdClient.DisconnectionMode.LOGOFF);
				disconnected = false;
			}
		}
		else {
			return;
		}
		
		this.setVisible(false);
		this.dispose();
		
		if (cancelled)
			return;
		
		this.frame.haveToQuit(this.exitCheckBox.isSelected());
		
		this.actions.disconnect(disconnected);
	}
}
