/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

import java.awt.Color;
import java.awt.Dimension;
import java.awt.GraphicsConfiguration;
import java.awt.GraphicsEnvironment;
import java.awt.GridLayout;
import java.awt.Insets;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.KeyEvent;

import javax.swing.JComboBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JSlider;
import javax.swing.KeyStroke;
import javax.swing.event.ChangeEvent;
import javax.swing.event.ChangeListener;

import org.jdesktop.swingx.JXTaskPane;
import org.ulteo.ovd.client.I18n;

public class OptionPanel extends JPanel {
	
	private JXTaskPane taskPane = new JXTaskPane();
	private JComboBox comboMode = new JComboBox();
	private JComboBox comboLanguage = new JComboBox();
	private JComboBox comboKeyboard = new JComboBox();
	private JSlider screenSizeSelecter = null;
	private JLabel resolution = new JLabel(I18n._("Resolution"));
	private JPanel showResolution = new JPanel();
	private JLabel resolutionValue = new JLabel(I18n._("Fullscreen"));
	
	public static int screenWidth = Toolkit.getDefaultToolkit().getScreenSize().width;
	public static int screenHeight = Toolkit.getDefaultToolkit().getScreenSize().height;
	private static GraphicsConfiguration gconf = GraphicsEnvironment.getLocalGraphicsEnvironment().getDefaultScreenDevice().getDefaultConfiguration();
	private static Insets insets = Toolkit.getDefaultToolkit().getScreenInsets(gconf);
	public static Dimension SMALL_RES = new Dimension(800,600);
	public static Dimension MEDUIM_RES = new Dimension(1024,768);
	public static Dimension HIGH_RES = new Dimension(1280,678);
	public static Dimension MAXIMISED = new Dimension(screenWidth-insets.left-insets.right, screenHeight-insets.top-insets.bottom);
	public static Dimension FULLSCREEN = new Dimension(screenWidth, screenHeight);

	
	public OptionPanel() {
		taskPane.setTitle(I18n._("Advanced options"));
		taskPane.setCollapsed(true);
		taskPane.setBackground(Color.GRAY);
		GridLayout layout = new GridLayout(5,2);
		
		screenSizeSelecter = new JSlider(0, 4, 4);
		screenSizeSelecter.setMajorTickSpacing(1);
		screenSizeSelecter.setPaintTicks(true);
		screenSizeSelecter.setSnapToTicks(true);
		screenSizeSelecter.setPreferredSize(new Dimension(100,20));
		
		layout.setHgap(30);
		layout.setVgap(10);
		taskPane.setLayout(layout);
		
		comboMode.addItem(I18n._("Desktop"));
		comboMode.addItem(I18n._("Portal"));
		comboMode.addItem(I18n._("Integrated"));
		
		taskPane.add(new JLabel(I18n._("Mode")));
		taskPane.add(comboMode);
		comboMode.addActionListener(new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent e) {				
				if(comboMode.getSelectedIndex() == 1 || comboMode.getSelectedIndex() == 2) {
					taskPane.removeAll();
					taskPane.add(new JLabel(I18n._("Mode")));
					taskPane.add(comboMode);
					taskPane.add(new JLabel(I18n._("Language")));
					taskPane.add(comboLanguage);
					taskPane.add(new JLabel(I18n._("Keyboard layout")));
					taskPane.add(comboKeyboard);
					taskPane.revalidate();
				}
				else {
					taskPane.removeAll();
					taskPane.add(new JLabel(I18n._("Mode")));
					taskPane.add(comboMode);
					taskPane.add(resolution);
					taskPane.add(screenSizeSelecter);
					taskPane.add(showResolution);
					taskPane.add(resolutionValue);
					taskPane.add(new JLabel(I18n._("Language")));
					taskPane.add(comboLanguage);
					taskPane.add(new JLabel(I18n._("Keyboard layout")));
					taskPane.add(comboKeyboard);
					taskPane.revalidate();
				}
			}
		});
		
		taskPane.add(resolution);
		screenSizeSelecter.addChangeListener(new ChangeListener() {
			
			@Override
			public void stateChanged(ChangeEvent ce) {
				int value = screenSizeSelecter.getValue();
				
				switch(value) {
				case 0 :
					resolutionValue.setText("800x600");
					break;
				case 1 :
					resolutionValue.setText("1024x768");
					break;
				case 2 :
					resolutionValue.setText("1280x678");
					break;
				case 3 :
					resolutionValue.setText(I18n._("Maximized"));
					break;
				case 4 :
					resolutionValue.setText(I18n._("Fullscreen"));
					break;
				}
				taskPane.revalidate();
			}
		});
		
		enterPressesWhenFocused(comboMode);
		enterPressesWhenFocused(comboLanguage);
		enterPressesWhenFocused(comboKeyboard);
		
		taskPane.add(screenSizeSelecter);
		
		taskPane.add(showResolution);
		taskPane.add(resolutionValue);
		
		initLanguage();
		comboLanguage.setPreferredSize(new Dimension(100,20));
		comboKeyboard.setPreferredSize(new Dimension(100,20));
		
		taskPane.add(new JLabel(I18n._("Language")));
		taskPane.add(comboLanguage);
		taskPane.add(new JLabel(I18n._("Keyboard layout")));
		taskPane.add(comboKeyboard);
		add(taskPane);
	}

	public void initLanguage() {
		comboLanguage.addItem("Français");
		comboLanguage.addItem("English");
		comboLanguage.addItem("English US");
	}
	
	public void enterPressesWhenFocused(JComboBox combo) {
	    combo.registerKeyboardAction(
	        combo.getActionForKeyStroke(
	            KeyStroke.getKeyStroke(KeyEvent.VK_SPACE, 0, false)), 
	            KeyStroke.getKeyStroke(KeyEvent.VK_ENTER, 0, false), 
	            JComponent.WHEN_FOCUSED);

	    combo.registerKeyboardAction(
	        combo.getActionForKeyStroke(
	            KeyStroke.getKeyStroke(KeyEvent.VK_SPACE, 0, true)), 
	            KeyStroke.getKeyStroke(KeyEvent.VK_ENTER, 0, true), 
	            JComponent.WHEN_FOCUSED);
	}
	public JXTaskPane getTaskPane() {
		return taskPane;
	}

	public void setTaskPane(JXTaskPane taskPane) {
		this.taskPane = taskPane;
	}

	public JComboBox getComboMode() {
		return comboMode;
	}

	public void setComboMode(JComboBox comboMode) {
		this.comboMode = comboMode;
	}

	public JComboBox getComboLanguage() {
		return comboLanguage;
	}

	public void setComboLanguage(JComboBox comboLanguage) {
		this.comboLanguage = comboLanguage;
	}

	public JComboBox getComboKeyboard() {
		return comboKeyboard;
	}

	public void setComboKeyboard(JComboBox comboKeyboard) {
		this.comboKeyboard = comboKeyboard;
	}

	public JSlider getScreenSizeSelecter() {
		return screenSizeSelecter;
	}

	public void setScreenSizeSelecter(JSlider screenSizeSelecter) {
		this.screenSizeSelecter = screenSizeSelecter;
	}
}
