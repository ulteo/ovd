/*
 * Copyright (C) 2009-2014 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2010
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2014
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
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.List;
import javax.imageio.ImageIO;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.gui.GUIActions;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.client.cache.PngManager;
import org.ulteo.ovd.integrated.mime.XDGMime;
import org.ulteo.ovd.integrated.shorcut.LinuxShortcut;
import org.ulteo.utils.FilesOp;

public class SystemLinux extends SystemAbstract {
	private final String wm_detect_command = "xprop -root";
	private final String ulteo_graphic_refresh_application = "xfdesktop --reload";
	private final String desktop_refresh_application = "update-desktop-database";
	private static enum windows_manager {xfce, gnome, kde, unknow};

	private static String HOME_PATH;
	static {
		HOME_PATH = System.getProperty("user.home");
		if (! HOME_PATH.endsWith(File.separator))
			HOME_PATH += File.separator;
	}

	// Main menu constants - Begin
	private static final String MENU_ICON_NAME = "ulteo-ovd.png";
	private static final String APPLICATIONSMENU_FILE_NAME = "applications.menu";
	private static final String MENU_FILE_NAME = "UlteoOVD.menu";
	private static final String DIRECTORY_FILE_NAME = "UlteoOVD.directory";

	private static final String INTEGRATION_PATH = "integration/linux/";

	private static final String APPLICATIONSMENU_FILE_OUTPATH = HOME_PATH + ".config/menus/";
	private static final String MENU_FILE_OUTPATH = APPLICATIONSMENU_FILE_OUTPATH + "applications-merged/";
	private static final String DIRECTORY_FILE_OUTPATH = HOME_PATH + ".local/share/desktop-directories/";
	// Main menu constants - End
	
	private boolean isApplicationsMenuExisting = true;

	public SystemLinux(String sm) {
		super(new PngManager(), sm);

		this.shortcut = new LinuxShortcut();

		new File(Constants.PATH_CACHE_MIMETYPES_FILES).mkdirs();
		this.fileAssociate = new XDGMime();
	}

	public void installSystemMenu() {
		// Create folders
		new File(MENU_FILE_OUTPATH).mkdirs();
		new File(DIRECTORY_FILE_OUTPATH).mkdirs();
		
		// applications.menu
		if (! new File(APPLICATIONSMENU_FILE_OUTPATH + APPLICATIONSMENU_FILE_NAME).exists()) {
			this.isApplicationsMenuExisting = false;
			try {
				FilesOp.exportJarResource(INTEGRATION_PATH + APPLICATIONSMENU_FILE_NAME, APPLICATIONSMENU_FILE_OUTPATH);
			} catch (FileNotFoundException ex) {
				Logger.error("Failed to create main menu section: "+ex.getMessage());
				return;
			}
		}

		// Ulteo menu
		try {
			FilesOp.exportJarResource(INTEGRATION_PATH + MENU_FILE_NAME, MENU_FILE_OUTPATH);
			FilesOp.exportJarResource(INTEGRATION_PATH + DIRECTORY_FILE_NAME, DIRECTORY_FILE_OUTPATH);
		} catch (FileNotFoundException ex) {
			Logger.error("Failed to create menu section: "+ex.getMessage());
			return;
		}

		// Ulteo menu icon
		try {
			Image img = GUIActions.getUlteoIcon();
			int width = img.getWidth(null);
			int height = img.getWidth(null);
			
			if (width != height) {
				Logger.error("Failed to create menu icon: width != height");
				return;
			}
			
			BufferedImage buf = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
			buf.getGraphics().drawImage(img, 0, 0, null);
			
			File icon = new File(Constants.PATH_ULTEO_TMP + File.separator + MENU_ICON_NAME);
			icon.getParentFile().mkdirs();
			ImageIO.write(buf, "png", icon);
			try {
				Process proc = Runtime.getRuntime().exec("xdg-icon-resource install --context apps --size "+width+" "+icon.getPath());
				proc.waitFor();
			} catch (Exception ex) {
				Logger.error("Failed to add menu icon: "+ex.getMessage());
			}
			icon.delete();
		} catch (IOException ex) {
			Logger.error("Failed to set the menu section icon: "+ex.getMessage());
		}
	}

	public void clearSystemMenu() {
		// applications.menu	
		if (! this.isApplicationsMenuExisting) {
			File applicationsMenu = new File(APPLICATIONSMENU_FILE_OUTPATH + APPLICATIONSMENU_FILE_NAME);
			if (applicationsMenu.exists())
				applicationsMenu.delete();
		}
		
		// Ulteo menu
		File menu = new File(MENU_FILE_OUTPATH + MENU_FILE_NAME);
		if (menu.exists())
			menu.delete();

		File directory = new File(DIRECTORY_FILE_OUTPATH + DIRECTORY_FILE_NAME);
		if (directory.exists())
			directory.delete();

		// Ulteo menu icon
		try {
			Runtime.getRuntime().exec("xdg-icon-resource uninstall --context apps --size 64 "+MENU_ICON_NAME.substring(0, MENU_ICON_NAME.length() - 4));
		} catch (IOException ex) {
			Logger.error("Failed to remove menu icon: "+ex.getMessage());
		}
	}

	public static void cleanAll() {
		LinuxShortcut.removeAll();

		XDGMime.unregisterAllMimeTypes();
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
		
		BufferedOutputStream desktopStream = null;
		BufferedOutputStream xdgStream = null;
		File xdgShortcut;
		
		if (new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS).exists()) {
			xdgShortcut = new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
			try {
				xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);
			} catch (FileNotFoundException ex) {
				Logger.error("Cannot create file "+xdgShortcut.getPath()+": "+ex.getMessage());
			}
		}
		else {
			xdgShortcut = new File(Constants.PATH_XDG_APPLICATIONS+Constants.FILE_SEPARATOR+app.getId()+Constants.SHORTCUTS_EXTENSION);
			xdgShortcut.getParentFile().mkdirs();
			
			try {
				xdgStream = new BufferedOutputStream(new FileOutputStream(xdgShortcut), 4096);
			} catch (FileNotFoundException ex) {
				Logger.error("Cannot create file "+xdgShortcut.getPath()+": "+ex.getMessage());
				xdgStream = null;
			}			
		}
		
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

	private windows_manager getWM() {
		String line = null;
		Runtime runtime = Runtime.getRuntime();
		Process process = null;
		
		try {
			process = runtime.exec(this.wm_detect_command);
			if (process == null) {
				Logger.warn("Unable to detect windows manager. Internal error");
				return windows_manager.unknow;
			}
			
			process.waitFor();
			if (process.exitValue() != 0) {
				Logger.warn("Unable to detect windows manager, processus return error");
				return windows_manager.unknow;
			}
			
			BufferedReader input = new BufferedReader(new InputStreamReader(process.getInputStream()));
			while ( (line = input.readLine()) != null) {
				line = line.toLowerCase();
				if (line.contains("xfce")) {
					return windows_manager.xfce;
				}
				
				if (line.contains("kde")) {
					return windows_manager.kde;
				}
				
				if (line.contains("gnome")) {
					return windows_manager.gnome;
				}
			}
		}
		catch (SecurityException e) {
			Logger.error("Unable to detect windows manager. This process is not allowed to start a process ["+e.getMessage()+"]");
		}
		catch (InterruptedException e) {
			Logger.warn("Unable to detect windows manager. Operation stopped: "+e.getMessage());
		}
		catch (IOException e) {
			Logger.error("Unable to detect windows manager. " +e.getMessage());
		}
		
		return windows_manager.unknow;
	}
	
	
	public void refresh() {
		XDGMime.updateDatabase();
		String xdg_dir = Constants.PATH_XDG_APPLICATIONS;
		
		if (new File(Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS).exists()) {
			xdg_dir = Constants.PATH_OVD_SPOOL_XDG_APPLICATIONS;
		}
		
		try {
			Runtime runtime = Runtime.getRuntime();
			
			runtime.exec(this.desktop_refresh_application+" "+xdg_dir);
			windows_manager wm = this.getWM();
			if (wm == windows_manager.xfce) {
				runtime.exec(this.ulteo_graphic_refresh_application	);
			}
		}
		catch (SecurityException e) {
			Logger.error("Unable to refresh desktop, this process is not allowed to start a process ["+e.getMessage()+"]");
		} 
		catch (IOException e) {
			Logger.error("Unable to refresh desktop ["+e.getMessage()+"]");	
		}
	}
}
