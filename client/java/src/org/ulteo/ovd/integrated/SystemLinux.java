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
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import javax.imageio.ImageIO;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.shorcut.LinuxShortcut;

public class SystemLinux extends SystemAbstract {

	public SystemLinux() {
		this.shortcut = new LinuxShortcut();
	}

	@Override
	public String create(Application app, boolean associate) {
		Logger.debug("Creating the '"+app.getName()+"' shortcut");
		
		this.saveIcon(app);

		if (associate) {
			// ToDo: File association for Linux system
			Logger.debug("Should associate some mime types with application "+app.getName()+" but it is not implemented yet");
		}

		return this.shortcut.create(app);
	}

	@Override
	public void clean(Application app) {
		Logger.debug("Deleting the '"+app.getName()+"' shortcut");

		this.uninstall(app);
		this.shortcut.remove(app);
	}

	public static void cleanAll() {
		LinuxShortcut.removeAll();
	}

	@Override
	public void install(Application app, boolean showDesktopIcon) {
		Logger.debug("Installing the '"+app.getName()+"' shortcut");
		
		File f = new File(Constants.PATH_SHORTCUTS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
		if (! f.exists()) {
			Logger.error("Cannot copy the '"+app.getId()+Constants.SHORTCUTS_EXTENSION+"' shortcut: The file does not exist ("+f.getPath()+")");
			return;
		}

		try {
			BufferedInputStream shortcutReader = new BufferedInputStream(new FileInputStream(f), 4096);
			
			if (new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS).exists()) {
				File xdgShortcut = new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
				BufferedOutputStream xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);

				int currentChar;
				while ((currentChar = shortcutReader.read()) != -1) {
					xdgStream.write(currentChar);
				}

				xdgStream.close();
			}
			else {
				if (showDesktopIcon) {
					File desktopShortcut = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
					BufferedOutputStream desktopStream = null;
					desktopStream = new BufferedOutputStream(new FileOutputStream(desktopShortcut), 4096);
					byte[] buffer = new byte[1024];
					int nbytes;
					while ((nbytes = shortcutReader.read(buffer)) != -1) {
						desktopStream.write(buffer, 0, nbytes);
					}
					desktopStream.flush();
					desktopStream.close();
					if (! desktopShortcut.setExecutable(true)) {
						Logger.error("Failed to set executable "+desktopShortcut.getPath());
					}
				}

				File xdgShortcut = new File(Constants.PATH_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
				BufferedOutputStream xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);
				byte[] buffer = new byte[1024];
				int nbytes;
				while ((nbytes = shortcutReader.read(buffer)) != -1) {
					xdgStream.write(buffer, 0, nbytes);
				}
				xdgStream.flush();
				xdgStream.close();
				if (! xdgShortcut.setExecutable(true)) {
					Logger.error("Failed to set executable "+xdgShortcut.getPath());
				}
			}
			shortcutReader.close();
		} catch(FileNotFoundException e) {
			Logger.error("This file does not exists: "+e.getMessage());
			return;
		} catch(IOException e) {
			Logger.error("An error occured during the shortcut '"+app.getId()+Constants.SHORTCUTS_EXTENSION+"' copy: "+e.getMessage());
			return;
		} catch(SecurityException e) {
			Logger.error("An error occured while creating or setting the shortcut '"+app.getId()+Constants.SHORTCUTS_EXTENSION+"' executable: "+e.getMessage());
			return;
		}
	}

	@Override
	public void uninstall(Application app) {
		Logger.debug("Uninstalling the '"+app.getName()+"' shortcut");

		File desktop = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
		if (desktop.exists())
			desktop.delete();
		desktop = null;

		File xdgApps = new File(Constants.PATH_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
		if (xdgApps.exists())
			xdgApps.delete();
		xdgApps = null;
	}

	@Override
	protected void saveIcon(Application app) {
		BufferedImage buff = null;

		File output = new File(Constants.PATH_ICONS+Constants.FILE_SEPARATOR+app.getIconName()+".png");
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
			buff = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
		}
		catch (Exception ex) {
			Logger.error("Error while creating "+app.getName()+" icon: "+ex.getMessage());
			return;
		}
		
		Graphics2D g = buff.createGraphics();
		g.drawImage(img, null, null);

		try {
			ImageIO.write(buff, "png", output);
		} catch (IOException ex) {
			Logger.error("Error while converting "+app.getName()+" icon: "+ex.getMessage());
		}
	}
}
