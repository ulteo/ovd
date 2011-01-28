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
import java.util.ArrayList;
import java.util.List;
import javax.imageio.ImageIO;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.cache.PngManager;
import org.ulteo.ovd.integrated.shorcut.LinuxShortcut;

public class SystemLinux extends SystemAbstract {

	public SystemLinux() {
		super(new PngManager());

		this.shortcut = new LinuxShortcut();
	}

	@Override
	public String create(Application app) {
		Logger.debug("Creating the '"+app.getName()+"' shortcut");
		
		this.saveIcon(app);

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

	protected void installShortcuts(Application app, boolean showDesktopIcon) {
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
				BufferedOutputStream desktopStream = null;
				File desktopShortcut = null;
				if (showDesktopIcon) {
					desktopShortcut = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
					desktopStream = new BufferedOutputStream(new FileOutputStream(desktopShortcut), 4096);
				}

				File xdgShortcut = new File(Constants.PATH_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
				BufferedOutputStream xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);

				byte[] buffer = new byte[1024];
				int nbytes;
				while ((nbytes = shortcutReader.read(buffer)) != -1) {
					if (desktopStream != null)
						desktopStream.write(buffer, 0, nbytes);
					xdgStream.write(buffer, 0, nbytes);
				}

				if (desktopStream != null) {
					desktopStream.flush();
					desktopStream.close();

					if (! desktopShortcut.setExecutable(true)) {
						Logger.error("Failed to set executable "+desktopShortcut.getPath());
					}
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

	public void uninstallShortcuts(Application app) {
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

	protected void associateMimeTypes(Application app) {
		// ToDo: File association for Linux system
		Logger.debug("Should associate some mime types with application "+app.getName()+" but it is not implemented yet");
	}

	protected void disassociateMimeTypes(Application app) {
		// ToDo: File disassociation for Linux system
		Logger.debug("Should disassociate some mime types with application "+app.getName()+" but it is not implemented yet");
	}

	@Override
	protected void saveIcon(Application app) {
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

		try {
			this.writeIcon(img, output);
		} catch (Exception ex) {
			Logger.error("Failed to write the "+app.getName()+" icon to '"+output.getPath()+"': "+ex.getMessage());
			return;
		}
	}

	@Override
	protected boolean writeIcon(Image img, File out) throws FileNotFoundException, IOException {
		if (img == null || out == null)
			return false;

		int width = img.getWidth(null);
		int height = img.getHeight(null);
		if (width <= 0 || height <= 0) {
			Logger.error("Icon size is too small: "+width+"x"+height);
			return false;
		}

		BufferedImage buff = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
		Graphics2D g = buff.createGraphics();
		g.drawImage(img, null, null);

		ImageIO.write(buff, "png", out);

		return true;
	}

	@Override
	protected List<BufferedImage> readIcon(File in) throws IOException {
		List<BufferedImage> iconsList = new ArrayList<BufferedImage>();

		BufferedImage icon = ImageIO.read(in);
		if (icon != null)
			iconsList.add(icon);

		return iconsList;
	}
}
