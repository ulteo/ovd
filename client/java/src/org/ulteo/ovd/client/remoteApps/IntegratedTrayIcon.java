package org.ulteo.ovd.client.remoteApps;

import java.awt.AWTException;
import java.awt.Image;
import java.awt.MenuItem;
import java.awt.PopupMenu;
import java.awt.SystemTray;
import java.awt.Toolkit;
import java.awt.TrayIcon;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;

import org.ulteo.rdp.RdpActions;

public class IntegratedTrayIcon implements ActionListener {
	private static final String EXIT = "Exit";
	private static final String DISCONNECT = "Disconnect";

	private RdpActions disc = null;
	private Image logo = null;
	private PopupMenu popup = null;
	private MenuItem exit = null;
	private MenuItem disconnect = null;

	public IntegratedTrayIcon(RdpActions disc_) {
		this.disc = disc_;
		this.popup = new PopupMenu();
		this.exit = new MenuItem(EXIT);
		this.exit.addActionListener(this);
		this.disconnect = new MenuItem(DISCONNECT);
		this.disconnect.addActionListener(this);
		popup.add(exit);
		popup.add(disconnect);
		logo = Toolkit.getDefaultToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		TrayIcon trayIcon = new TrayIcon(logo, "Open Virtual Desktop", popup);
		trayIcon.setImageAutoSize(true);
		trayIcon.addActionListener(this);
		SystemTray systemTray = SystemTray.getSystemTray();

		try {
			systemTray.add(trayIcon);
		} catch (AWTException e) {
			e.printStackTrace();
		}
	}

	public void actionPerformed(ActionEvent ae) {
		String action = ae.getActionCommand();
		if (action.equals(EXIT)) {
			this.disc.exit(0);
		}
		else if (action.equals(DISCONNECT)) {
			this.disc.disconnectAll();
		}
	}

}
