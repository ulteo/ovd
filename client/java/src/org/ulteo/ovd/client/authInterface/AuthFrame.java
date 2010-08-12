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
import java.awt.Component;
import java.awt.Dimension;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Image;
import java.awt.Insets;
import java.awt.KeyboardFocusManager;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.KeyEvent;
import java.awt.event.KeyListener;

import javax.swing.ButtonGroup;
import javax.swing.ButtonModel;
import javax.swing.ImageIcon;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JPasswordField;
import javax.swing.JRadioButton;
import javax.swing.JSlider;
import javax.swing.JTextField;
import javax.swing.event.ChangeEvent;
import javax.swing.event.ChangeListener;

import org.ulteo.ovd.client.I18n;
import org.ulteo.ovd.sm.SessionManagerCommunication;

public class AuthFrame {
	
	private JFrame mainFrame = new JFrame();
	private boolean desktopLaunched = false;
	
	private JLabel login = new JLabel(I18n._("Login"));
	private JLabel password = new JLabel(I18n._("Password"));
	private JLabel host = new JLabel(I18n._("Host"));
	private JTextField loginTextField = new JTextField();
	private JPasswordField passwordTextField = new JPasswordField();
	private String loginStr = null;
	
	private JTextField hostTextField = new JTextField();
	private JButton startButton = new JButton(I18n._("Start !"));
	private boolean startButtonClicked = false;
	private JButton moreOption = new JButton();
	private Image frameLogo = null;
	private ImageIcon ulteoLogo = null;
	private ImageIcon optionLogo = null;
	private ImageIcon userLogo = null;
	private ImageIcon passwordLogo = null;
	private ImageIcon hostLogo = null;
	private ImageIcon showOption = null;
	private ImageIcon hideOption = null;
	private JLabel logoLabel = new JLabel();
	private JLabel userLogoLabel = new JLabel();
	private JLabel passwordLogoLabel = new JLabel();
	private JLabel hostLogoLabel = new JLabel();
	private boolean desktopMode = true;
	private boolean optionClicked;
	private JLabel optionLogoLabel = new JLabel();
	private JLabel mode = new JLabel(I18n._("Mode"));
	private JLabel resolution = new JLabel(I18n._("Resolution"));
	private JLabel language = new JLabel(I18n._("Language"));
	private JLabel keyboard = new JLabel(I18n._("Keyboard"));
	private JRadioButton desktopButton = new JRadioButton(I18n._("Desktop"));
	private JRadioButton portalButton = new JRadioButton(I18n._("Applications"));
	private ButtonGroup radioGroup = new ButtonGroup();
	private JSlider resBar = new JSlider(0, 4, 4);
	private JLabel resolutionValue = new JLabel(I18n._("Fullscreen"));
	private JComboBox languageBox = new JComboBox();
	private JComboBox keyboardBox = new JComboBox();
	private JCheckBox rememberMe = new JCheckBox(I18n._("Remember me"));
	private JCheckBox autoPublish = new JCheckBox(I18n._("Auto-publish shortcuts"));
	private JCheckBox useLocalCredentials = new JCheckBox(I18n._("Use local creadentials"));
	private boolean displayUserLocalCredentials = (System.getProperty("os.name").startsWith("Windows"));
	private ActionListener optionListener = null;
	
	private ActionListener obj = null;
	
	private GridBagConstraints gbc = null;
	
	public AuthFrame(ActionListener obj_) {
		this.obj = obj_;
		
		this.init();
	}
	
	public void init() {
		this.optionClicked = false;

		this.mainFrame.setVisible(false);
		mainFrame.setTitle("OVD Native Client");
		mainFrame.setSize(500,450);
		mainFrame.setResizable(false);
		mainFrame.setBackground(Color.white);
		frameLogo = mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		ulteoLogo = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/logo_small.png")));
		optionLogo = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/options.png")));
		userLogo = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/users.png")));
		passwordLogo = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/password.png")));
		hostLogo = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/server.png")));
		showOption = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/show.png")));
		hideOption = new ImageIcon(mainFrame.getToolkit().getImage(getClass().getClassLoader().getResource("pics/hide.png")));
		
		mainFrame.setIconImage(frameLogo);
		logoLabel.setIcon(ulteoLogo);
		userLogoLabel.setIcon(userLogo);
		passwordLogoLabel.setIcon(passwordLogo);
		hostLogoLabel.setIcon(hostLogo);
		optionLogoLabel.setIcon(optionLogo);
		
		moreOption.setIcon(showOption);
		moreOption.setText(I18n._("More options ..."));
		
		desktopButton.addActionListener(new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				if (! desktopMode) {
					desktopMode = true;
					mainFrame.remove(autoPublish);
					gbc.gridx = 2;
					gbc.gridy = 10;
					gbc.gridwidth = 2;
					mainFrame.add(resBar, gbc);
					
					gbc.gridy = 11;
					gbc.anchor = GridBagConstraints.CENTER;
					mainFrame.add(resolutionValue, gbc);
					mainFrame.pack();
				}
				
			}
		});
		
		portalButton.addActionListener(new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				if (desktopMode) {
					desktopMode = false;
					mainFrame.remove(resolutionValue);
					mainFrame.remove(resolution);
					mainFrame.remove(resBar);
					gbc.gridx = 2;
					gbc.gridy = 10;
					gbc.anchor = GridBagConstraints.CENTER;
					gbc.fill = GridBagConstraints.NONE;
					mainFrame.add(autoPublish, gbc);
					mainFrame.pack();
				}
			}
		});
		
		this.useLocalCredentials.addActionListener(new ActionListener() {
			@Override
			public void actionPerformed(ActionEvent e) {
				toggleLocalCredentials();
			}
		});
		
		resBar.setMajorTickSpacing(1);
		resBar.setPaintTicks(true);
		resBar.setSnapToTicks(true);
		resBar.addChangeListener(new ChangeListener() {
			
			@Override
			public void stateChanged(ChangeEvent ce) {
				int value = resBar.getValue();
				
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
			}
		});
		
		radioGroup.add(desktopButton);
		radioGroup.add(portalButton);
		radioGroup.setSelected(desktopButton.getModel(), true);
		
		optionListener = new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				if (! optionClicked) {
					gbc.anchor = GridBagConstraints.LINE_START;
					gbc.insets.left = 25;
					gbc.gridx = 0;
					gbc.gridy = 8;
					gbc.insets.top = 30;
					mainFrame.add(optionLogoLabel, gbc);
					
					gbc.insets.top = 0;
					gbc.insets.left = 0;
					gbc.gridx = 1;
					gbc.gridy = 9;
					mainFrame.add(mode, gbc);
					
					if (desktopMode) {
						gbc.gridx = 1;
						gbc.gridy = 10;
						mainFrame.add(resolution, gbc);
					}
					
					/*gbc.gridy = 12;
					mainFrame.add(language, gbc);
					
					gbc.gridy = 13;
					mainFrame.add(keyboard, gbc);*/
					
					gbc.gridwidth = 1;
					gbc.gridx = 2;
					gbc.gridy = 9;
					mainFrame.add(desktopButton,gbc);
					
					gbc.gridx = 3;
					mainFrame.add(portalButton,gbc);
					
					
					if(desktopMode) {
						gbc.gridx = 2;
						gbc.gridwidth = 2;
						gbc.gridy = 10;
						mainFrame.add(resBar, gbc);

						gbc.gridy = 11;
						gbc.anchor = GridBagConstraints.CENTER;
						mainFrame.add(resolutionValue, gbc);
					}
					else {
						gbc.gridx = 2;
						gbc.gridy = 10;
						gbc.gridwidth = 2;
						gbc.fill = GridBagConstraints.NONE;
						gbc.anchor = GridBagConstraints.CENTER;
						mainFrame.add(autoPublish, gbc);
					}
					
					/*gbc.gridx = 2;
					gbc.gridwidth = 2;
					gbc.gridy = 12;
					gbc.fill = GridBagConstraints.HORIZONTAL;
					mainFrame.add(languageBox, gbc);
					
					gbc.gridy = 13;
					mainFrame.add(keyboardBox, gbc);*/
					
					gbc.fill = GridBagConstraints.NONE;
					moreOption.setIcon(hideOption);
					moreOption.setText(I18n._("Fewer options"));
					mainFrame.pack();
					optionClicked = true;
					
				} else {
					mainFrame.remove(optionLogoLabel);
					mainFrame.remove(mode);
					mainFrame.remove(resolution);
					mainFrame.remove(language);
					mainFrame.remove(keyboard);
					mainFrame.remove(desktopButton);
					mainFrame.remove(portalButton);
					mainFrame.remove(resBar);
					mainFrame.remove(resolutionValue);
					mainFrame.remove(languageBox);
					mainFrame.remove(keyboardBox);
					mainFrame.remove(autoPublish);
					
					moreOption.setIcon(showOption);
					moreOption.setText(I18n._("More options ..."));
					mainFrame.pack();
					optionClicked = false;
				}
				
			}
		};
		
		moreOption.addActionListener(optionListener);
		
		mainFrame.setLayout(new GridBagLayout());
		gbc = new GridBagConstraints();
		startButton.setPreferredSize(new Dimension(150, 25));
		startButton.addActionListener(this.obj);
		
		gbc.gridx = gbc.gridy = 0;
		gbc.insets = new Insets(0, 0, 25, 0);
		gbc.gridwidth = 2;
		gbc.anchor = GridBagConstraints.LINE_START;
		gbc.weightx = 1;
		gbc.weighty = 1;
		mainFrame.add(logoLabel, gbc);
		
		gbc.gridwidth = 1;
		gbc.anchor = GridBagConstraints.LINE_END;
		gbc.gridx = 0;
		gbc.gridy = 3;
		gbc.insets.bottom = 5;
		mainFrame.add(userLogoLabel, gbc);
		
		gbc.gridy = 4;
		mainFrame.add(passwordLogoLabel, gbc);
		
		int pos = 5;
		if (this.displayUserLocalCredentials)
			pos++;
		
		gbc.gridy = pos;
		mainFrame.add(hostLogoLabel, gbc);
		
		pos = 1;
		gbc.anchor = GridBagConstraints.LINE_START;
		gbc.insets.left = 5;
		gbc.gridx = 1;
		gbc.gridy = 3;
		mainFrame.add(login, gbc);
		
		gbc.gridy = 4;
		mainFrame.add(password, gbc);      
		
		pos = 5;
		if (this.displayUserLocalCredentials)
			pos++;
		
		gbc.gridy = pos;
		mainFrame.add(host, gbc);
		
		gbc.gridwidth = GridBagConstraints.REMAINDER;;
		gbc.gridheight = GridBagConstraints.REMAINDER;
		gbc.insets.top = 25;
		gbc.gridx = 0;
		gbc.gridy = 14;
		mainFrame.add(moreOption, gbc);
		
		gbc.gridwidth = 0;
		gbc.gridheight = 1;
		gbc.insets.top = 0;
		gbc.gridx = 2;
		gbc.gridy = 3;
		gbc.insets.left = 0;
		gbc.insets.right = 15;
		gbc.weightx = 0;
		gbc.weighty = 0;
		gbc.fill = GridBagConstraints.HORIZONTAL;
		mainFrame.add(loginTextField, gbc);
		
		gbc.gridy = 4;
		mainFrame.add(passwordTextField, gbc);
		
		pos = 5;
		if (this.displayUserLocalCredentials) {
			gbc.gridy = pos++;
			mainFrame.add(this.useLocalCredentials, gbc);
		}
		
		gbc.gridy = pos++;
		mainFrame.add(hostTextField, gbc);
		
		gbc.gridy = pos++;
		gbc.anchor = GridBagConstraints.CENTER;
		mainFrame.add(rememberMe, gbc);
		
		gbc.gridx = 3;
		gbc.gridy = pos++;
		gbc.anchor = GridBagConstraints.LINE_START;
		gbc.gridwidth = 1;
		gbc.fill = GridBagConstraints.NONE;
		mainFrame.add(startButton, gbc);
		
		KeyListener keyListener = new KeyListener() {

			public synchronized void keyTyped(KeyEvent ke) {
				if ((ke.getKeyChar() == KeyEvent.VK_ENTER) && (! startButtonClicked)) {
					startButtonClicked = true;
					startButton.doClick();
				}
			}

			public void keyPressed(KeyEvent ke) {}
			public void keyReleased(KeyEvent ke) {}

		};
		for (Component c : this.mainFrame.getContentPane().getComponents()) {
			if (c.getClass() != JLabel.class) {
				c.addKeyListener(keyListener);
			}
		}
		
		mainFrame.pack();
		mainFrame.setLocationRelativeTo(null);
		this.showWindow();
		mainFrame.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
	}
	
	protected void toggleLocalCredentials() {
		if (this.useLocalCredentials.isSelected()) {
			this.loginStr = this.loginTextField.getText();
			this.loginTextField.setText(System.getProperty("user.name"));
			this.loginTextField.setEnabled(false);
			this.login.setEnabled(false);
			this.passwordTextField.setEnabled(false);
			this.password.setEnabled(false);
			this.userLogoLabel.setEnabled(false);
			this.passwordLogoLabel.setEnabled(false);
		}
		else {
			if (this.loginStr != null)
				this.loginTextField.setText(this.loginStr);
			this.loginTextField.setEnabled(true);
			this.login.setEnabled(true);
			this.passwordTextField.setEnabled(true);
			this.password.setEnabled(true);
			this.userLogoLabel.setEnabled(true);
			this.passwordLogoLabel.setEnabled(true);
		}
	}
	
	public void showWindow() {
		KeyboardFocusManager.setCurrentKeyboardFocusManager(null);
		this.startButtonClicked = false;
		this.toggleLocalCredentials();
		mainFrame.setVisible(true);
	}
	
	public void hideWindow() {
		mainFrame.setVisible(false);
	}
	
	public JTextField getLogin() {
		return loginTextField;
	}

	public void setLogin(String login_) {
		if (login_ == null)
			return;

		this.loginTextField.setText(login_);
	}

	public JPasswordField getPassword() {
		return passwordTextField;
	}

	public JTextField getHost() {
		return hostTextField;
	}

	public void setHost(String host_) {
		if (host_ == null)
			return;
		
		this.hostTextField.setText(host_);
	}

	public JRadioButton getDesktopButton() {
		return desktopButton;
	}
	
	public JSlider getResBar() {
		return resBar;
	}

	public void setResolution(int resolution_) {
		this.resBar.setValue(resolution_);
	}

	public String getSessionMode() {
		return (this.desktopButton.isSelected()) ? SessionManagerCommunication.SESSION_MODE_DESKTOP : SessionManagerCommunication.SESSION_MODE_REMOTEAPPS;
	}

	public void setSessionMode(String sessionMode_) {
		if (sessionMode_ == null)
			return;

		ButtonModel model = null;
		if (sessionMode_.equals(SessionManagerCommunication.SESSION_MODE_DESKTOP)) {
			model = this.desktopButton.getModel();
			this.desktopMode = true;
		}
		else if (sessionMode_.equals(SessionManagerCommunication.SESSION_MODE_REMOTEAPPS)) {
			model = this.portalButton.getModel();
			this.desktopMode = false;
		}
		else {
			return;
		}
		this.radioGroup.setSelected(model, true);
	}
	
	public JFrame getMainFrame() {
		return mainFrame;
	}
	
	public boolean isDesktopLaunched() {
		return desktopLaunched;
	}
	
	public void setDesktopLaunched(boolean desktopLaunched) {
		this.desktopLaunched = desktopLaunched;
	}
	
	public boolean isRememberMeChecked() {
		return this.rememberMe.isSelected();
	}

	public void setRememberMeChecked(boolean checked_) {
		this.rememberMe.setSelected(checked_);
	}
	
	public boolean isAutoPublishChecked() {
		return this.autoPublish.isSelected();
	}

	public void setAutoPublishChecked(boolean autoPublish_) {
		this.autoPublish.setSelected(autoPublish_);
	}
	
	public void setUseLocalCredentials(boolean useLocalCredentials_) {
		this.useLocalCredentials.setSelected(useLocalCredentials_);
	}
	
	public boolean isUseLocalCredentials() {
		return this.useLocalCredentials.isSelected();
	}
	
	public JButton getOptionButton() {
		return moreOption;
	}
	
	public JButton GetStartButton() {
		return this.startButton;
	}
}