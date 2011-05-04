/*
 * Copyright (C) 2009-2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011
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
import java.util.ArrayList;
import java.util.List;
import javax.imageio.ImageIO;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.cache.PngManager;
import org.ulteo.ovd.integrated.shorcut.LinuxShortcut;

public class SystemLinux extends SystemAbstract {
	private final String ulteo_graphic_refresh_application = "xfdesktop --reload";
	private final String desktop_refresh_application = "update-desktop-database";

	public SystemLinux(String sm) {
		super(new PngManager(), sm);

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

		BufferedInputStream shortcutReader;
		try {
			shortcutReader = new BufferedInputStream(new FileInputStream(f), 4096);
		} catch (FileNotFoundException ex) {
			Logger.error("Cannot read the '"+app.getId()+Constants.SHORTCUTS_EXTENSION+"' shortcut: The file does not exist ("+f.getPath()+")");
			return;
		}

		if (new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS).exists()) {
			File xdgShortcut = new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
			try {
				BufferedOutputStream xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);

				int currentChar;
				try {
					while ((currentChar = shortcutReader.read()) != -1) {
						xdgStream.write(currentChar);
					}
				} catch (IOException ex) {
					Logger.error("Error while copying '"+app.getId()+Constants.SHORTCUTS_EXTENSION+"' shortcut to "+xdgShortcut.getPath()+": "+ex.getMessage());
				}

				xdgStream.close();

			} catch (FileNotFoundException ex) {
				Logger.error("Cannot create file "+xdgShortcut.getPath()+": "+ex.getMessage());
			} catch (IOException ex) {
				Logger.error("Error while closing file "+xdgShortcut.getPath()+": "+ex.getMessage());
			}
		}
		else {
			BufferedOutputStream desktopStream = null;
			File desktopShortcut = null;
			if (showDesktopIcon) {
				desktopShortcut = new File(Constants.PATH_DESKTOP+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
				try {
					desktopStream = new BufferedOutputStream(new FileOutputStream(desktopShortcut), 4096);
				} catch (FileNotFoundException ex) {
					Logger.error("Cannot create file "+desktopShortcut.getPath()+": "+ex.getMessage());
					showDesktopIcon = false;
					desktopStream = null;
				}
			}

			File xdgShortcut = new File(Constants.PATH_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
			BufferedOutputStream xdgStream = null;
			try {
				xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);
			} catch (FileNotFoundException ex) {
				Logger.error("Cannot create file "+xdgShortcut.getPath()+": "+ex.getMessage());
				xdgStream = null;
			}

			if (xdgStream != null || desktopStream != null) {
				byte[] buffer = new byte[1024];
				int nbytes;
				try {
					while ((nbytes = shortcutReader.read(buffer)) != -1) {
						if (desktopStream != null)
							desktopStream.write(buffer, 0, nbytes);

						if (xdgStream != null)
							xdgStream.write(buffer, 0, nbytes);
					}
				} catch (IOException ex) {
					Logger.error("Error while copying '"+app.getId()+Constants.SHORTCUTS_EXTENSION+"' shortcut: "+ex.getMessage());
				}
			}

			if (desktopStream != null) {
				try {
					desktopStream.flush();
				} catch (IOException ex) {
					Logger.error("Error while flushing file "+desktopShortcut.getPath()+": "+ex.getMessage());
				}
				try {
					desktopStream.close();
				} catch (IOException ex) {
					Logger.error("Error while closing file "+desktopShortcut.getPath()+": "+ex.getMessage());
				}

				if (! desktopShortcut.setExecutable(true)) {
					Logger.error("Failed to set executable "+desktopShortcut.getPath());
				}
			}
			if (xdgStream != null) {
				try {
					xdgStream.flush();
				} catch (IOException ex) {
					Logger.error("Error while flushing file "+xdgShortcut.getPath()+": "+ex.getMessage());
				}
				try {
					xdgStream.close();
				} catch (IOException ex) {
					Logger.error("Error while closing file "+xdgShortcut.getPath()+": "+ex.getMessage());
				}
			}

			if (! xdgShortcut.setExecutable(true)) {
				Logger.error("Failed to set executable "+xdgShortcut.getPath());
			}
		}
		try {
			shortcutReader.close();
		} catch (IOException ex) {
			Logger.error("Error while closing file "+f.getPath()+": "+ex.getMessage());
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
	
	
	public static String getKnownDrivesUUIDPathFromPath(String path) {
		String[] composants = path.split(File.separator);
		String tempPath = "/";

		for (String composant: composants) {
			tempPath += composant+"/";
			String ulteoTag = tempPath + SystemLinux.KNOWN_ULTEO_TAG_FILE;
			File ulteoTagFile = new File(ulteoTag);
			
			if (ulteoTagFile.exists()) {
				return tempPath;
			}
		}
		return null;		
	}
	
	public static String getKnownDrivesUUIDFromPath(String UUIDpath) {
		String shareID = null;
		if (UUIDpath == null) {
			return null;
		}
		
		String ulteoTag = UUIDpath+"/"+SystemLinux.KNOWN_ULTEO_TAG_FILE;
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
				return shareID;
			} catch (FileNotFoundException e) {
				Logger.warn("Unable to find the file "+ulteoTag+" ["+e.getMessage()+"]");
			}
			catch (IOException e) {
				Logger.warn("Error while opening the file "+ulteoTag+" ["+e.getMessage()+"]");
			}
		}
		return null;
	}

	public void refresh() {
		File issueFile = new File("/etc/issue");
		String issue = "";
		boolean ulteoSystem = false;
		String xdg_dir = Constants.PATH_XDG_APPLICATIONS;
		
		try {
			BufferedReader br = new BufferedReader(new FileReader(issueFile));
			issue = br.readLine();
			issue = issue.toLowerCase();
			if (issue.startsWith(Constants.OVD_ISSUE.toLowerCase())) {
				xdg_dir = Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS;
				ulteoSystem = true;
			}
		}
		catch (FileNotFoundException e) {
			Logger.debug("Enable to find the issue file at "+issueFile);
		} catch (IOException e) {
			Logger.warn("Failed to read the issue file: "+issueFile);
		}
		
		try {
			Runtime runtime = Runtime.getRuntime();
			
			runtime.exec(this.desktop_refresh_application+" "+xdg_dir);
			if (ulteoSystem)
				runtime.exec(this.ulteo_graphic_refresh_application	);
		}
		catch (SecurityException e) {
			Logger.error("Unable to refresh desktop, this process is not allowed to start a process ["+e.getMessage()+"]");
		} 
		catch (IOException e) {
			Logger.error("Unable to refresh desktop ["+e.getMessage()+"]");	
		}
	}
}
