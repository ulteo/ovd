/*
 * Copyright (C) 2009 Ulteo SAS
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

package org.ulteo.ovd.integrated;

import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.image.BufferedImage;
import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.IOException;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.mime.WindowsRegistry;
import org.ulteo.ovd.integrated.shorcut.WindowsShortcut;

public class SystemWindows extends SystemAbstract {

	public SystemWindows() {
		this.shortcut = new WindowsShortcut();
		this.fileAssociate = new WindowsRegistry();
	}

	@Override
	public String create(Application app) {
		Logger.debug("Creating the '"+app.getName()+"' shortcut");
		
		this.saveIcon(app);
		this.fileAssociate.createAppAction(app);

		return this.shortcut.create(app);
	}

	@Override
	public void clean(Application app) {
		Logger.debug("Deleting the '"+app.getName()+"' shortcut");

		this.uninstall(app);
		this.shortcut.remove(app);
		this.fileAssociate.removeAppAction(app);
	}

	public static void cleanAll() {
		WindowsShortcut.removeAll();
		WindowsRegistry.removeAll();
	}

	@Override
	public void install(Application app, boolean showDesktopIcon) {
		String shortcutName = WindowsShortcut.replaceForbiddenChars(app.getName())+Constants.SHORTCUTS_EXTENSION;

		File f = new File(Constants.PATH_SHORTCUTS+Constants.FILE_SEPARATOR+shortcutName);
		if (! f.exists()) {
			Logger.error("Cannot copy the '"+shortcutName+"' shortcut: The file does not exist ("+f.getPath()+")");
			return;
		}

		try {
			BufferedInputStream shortcutReader = new BufferedInputStream(new FileInputStream(f), 4096);

			BufferedOutputStream desktopStream = null;
			if (showDesktopIcon) {
				File desktopShortcut = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+shortcutName);
				desktopStream = new BufferedOutputStream(new FileOutputStream(desktopShortcut), 4096);
				Logger.debug("Installing the '"+app.getName()+"' shortcut on the desktop ("+desktopShortcut.getPath()+")");
			}

			File startMenuShortcut = new File(Constants.PATH_STARTMENU+Constants.FILE_SEPARATOR+shortcutName);
			BufferedOutputStream startMenuStream = new BufferedOutputStream(new FileOutputStream(startMenuShortcut), 4096);
			Logger.debug("Installing the '"+app.getName()+"' shortcut in the start menu ("+startMenuShortcut.getPath()+")");

			int currentChar;
			while ((currentChar = shortcutReader.read()) != -1) {
				if (desktopStream != null)
					desktopStream.write(currentChar);
				startMenuStream.write(currentChar);
			}

			if (desktopStream != null)
				desktopStream.close();
			startMenuStream.close();
			shortcutReader.close();
		} catch(FileNotFoundException e) {
			Logger.error("This file does not exists: "+e.getMessage());
			return;
		} catch(IOException e) {
			Logger.error("An error occured during the shortcut '"+shortcutName+"' copy: "+e.getMessage());
			return;
		}
	}

	@Override
	public void uninstall(Application app) {
		Logger.debug("Uninstalling the '"+app.getName()+"' shortcut");

		String shortcutName = WindowsShortcut.replaceForbiddenChars(app.getName())+Constants.SHORTCUTS_EXTENSION;

		File desktopItem = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+shortcutName);
		if (desktopItem.exists())
			desktopItem.delete();
		desktopItem = null;

		File menuItem = new File(Constants.PATH_STARTMENU+Constants.FILE_SEPARATOR+shortcutName);
		if (menuItem.exists())
			menuItem.delete();
		menuItem = null;
	}

	@Override
	protected void saveIcon(Application app) {
		File output = new File(Constants.PATH_ICONS+Constants.FILE_SEPARATOR+app.getIconName()+".ico");
		BufferedImage buf = null;
		if (! output.exists()) {
			try {
				output.createNewFile();
			} catch (IOException ex) {
				Logger.error("Error while creating "+app.getName()+" icon file: "+ex.getMessage());
				return;
			}
		}

		ImageIcon icon = app.getIcon();
		if (icon == null) {
			Logger.error("No icon for "+app.getName());
			return;
		}
		Image img = icon.getImage();
		if (img == null) {
			Logger.error("No image for "+app.getName()+" icon");
			return;
		}
		int width = img.getWidth(null);
		int height = img.getHeight(null);
		if (width <= 0 || height <= 0) {
			Logger.error(app.getName()+" icon size is too small: "+width+"x"+height);
			return;
		}

		try {
			buf = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
		}
		catch (Exception ex) {
			Logger.error("Error while creating "+app.getName()+" icon: "+ex.getMessage());
			return;
		}
		
		Graphics2D graph = buf.createGraphics();
		graph.drawImage(img, 0, 0, null);
		graph.dispose();
		
		
		try {
			BufferedOutputStream os = new BufferedOutputStream(new FileOutputStream(output));
			net.sf.image4j.codec.ico.ICOEncoder.write(buf, os);
			os.close();
		} catch (IOException ex) {
			Logger.error("Error while converting "+app.getName()+" icon: "+ex.getMessage());
		}
	}
	
	public static String KNOWN_ULTEO_TAG_FILE = ".ulteo.id";
	
	public static String getKnownDrivesUUIDFromPath(String path) {
		String shareID = null;
		String driveLetter = path.substring(0, 3);
		String ulteoTag = driveLetter+SystemWindows.KNOWN_ULTEO_TAG_FILE;
		File ulteoTagFile = new File(ulteoTag);
		
		if (ulteoTagFile.exists()) {
			try {
				BufferedReader br = new BufferedReader(new FileReader(ulteoTagFile));
				char[] buff = new char[16];
				if (br.read(buff, 0, 16) != 16) {
					Logger.warn("Error while reading ulteo id file");
					return null;
				}
				shareID  = new String(buff);
			} catch (FileNotFoundException e) {
				Logger.warn("Unable to find the file "+ulteoTag+" ["+e.getMessage()+"]");
			}
			catch (IOException e) {
				Logger.warn("Error while opening the file "+ulteoTag+" ["+e.getMessage()+"]");
			}
		}
		return shareID;
	}
}
