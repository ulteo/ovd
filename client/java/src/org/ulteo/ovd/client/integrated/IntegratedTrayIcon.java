package org.ulteo.ovd.client.integrated;

import java.awt.AWTException;
import java.awt.Image;
import java.awt.MenuItem;
import java.awt.PopupMenu;
import java.awt.SystemTray;
import java.awt.Toolkit;
import java.awt.TrayIcon;

public class IntegratedTrayIcon {

	private Image logo = null;
	private PopupMenu popup = null;

	public IntegratedTrayIcon() {
		popup = new PopupMenu();
		MenuItem exit = new MenuItem("Exit");
		MenuItem disconnect = new MenuItem("Disconnect");
		popup.add(exit);
		popup.add(disconnect);
		logo = Toolkit.getDefaultToolkit().getImage(getClass().getClassLoader().getResource("pics/ulteo.png"));
		TrayIcon trayIcon = new TrayIcon(logo, "Open Virtual Desktop", popup);
		trayIcon.setImageAutoSize(true);

		SystemTray systemTray = SystemTray.getSystemTray();

		try {
			systemTray.add(trayIcon);
		} catch (AWTException e) {
			e.printStackTrace();
		}
	}

}
