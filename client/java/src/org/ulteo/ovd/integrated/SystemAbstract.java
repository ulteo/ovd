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

import java.awt.Image;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.util.HashMap;
import java.util.List;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.mime.FileAssociate;
import org.ulteo.ovd.integrated.shorcut.Shortcut;
import org.ulteo.utils.MD5;

public abstract class SystemAbstract {
	protected Shortcut shortcut = null;
	protected FileAssociate fileAssociate = null;

	public abstract String create(Application app);

	public abstract void clean(Application app);

	public static void cleanAll() {
		if (OSTools.isWindows())
			SystemWindows.cleanAll();
		else if (OSTools.isLinux())
			SystemLinux.cleanAll();
	}

	protected abstract void installShortcuts(Application app, boolean showDesktopIcon);
	protected abstract void associateMimeTypes(Application app);

	public final void install(Application app, boolean showDesktopIcon, boolean makeAssoc) {
		this.installShortcuts(app, showDesktopIcon);
		if (makeAssoc)
			this.associateMimeTypes(app);
	}

	protected abstract void uninstallShortcuts(Application app);
	protected abstract void disassociateMimeTypes(Application app);

	public final void uninstall(Application app) {
		this.disassociateMimeTypes(app);
		this.uninstallShortcuts(app);
	}

	public int updateMimeTypesIconsCache(HashMap<String, ImageIcon> mimeTypesIcons) {
		int updatedIcons = 0;

		if (mimeTypesIcons == null || mimeTypesIcons.isEmpty())
			return -1;

		File mimeTypesIconsCache = new File(Constants.PATH_CACHE_MIMETYPES_ICONS);
		if (! mimeTypesIconsCache.exists())
			mimeTypesIconsCache.mkdirs();
		mimeTypesIconsCache = null;

		for (String mimeType : mimeTypesIcons.keySet()) {
			String md5sum = MD5.getMD5Sum(mimeType);
			if (md5sum == null) {
				Logger.error("Failed to create md5sum for '"+mimeType+"'");
				continue;
			}

			File iconFile = new File(Constants.PATH_CACHE_MIMETYPES_ICONS+Constants.FILE_SEPARATOR+md5sum+Constants.ICONS_EXTENSION);
			if (iconFile.exists()) {
				if (iconFile.isFile()) {
					Logger.debug("icon '"+iconFile.getPath()+"' already exists");
					continue;
				}

				Logger.warn("Weird. '"+iconFile.getPath()+"' already exists but it is not a file.");
				continue;
			}

			ImageIcon icon = mimeTypesIcons.get(mimeType);
			if (icon == null) {
				Logger.warn("Mime-type '"+mimeType+"' has no icon");
				continue;
			}
			Image img = icon.getImage();
			if (img == null) {
				Logger.warn("Mime-type '"+mimeType+"' has no image");
				continue;
			}
			try {
				if (this.writeIcon(img, iconFile))
					updatedIcons++;
			} catch (Exception ex) {
				Logger.error("Failed to write the '"+mimeType+"' icon to '"+iconFile.getPath()+"': "+ex.getMessage());
				continue;
			}
		}

		return updatedIcons;
	}

	public ImageIcon getMimeTypeIcon(String mimeType) {
		String md5sum = MD5.getMD5Sum(mimeType);
		if (md5sum == null)
			return null;

		File iconFile = new File(Constants.PATH_CACHE_MIMETYPES_ICONS+Constants.FILE_SEPARATOR+md5sum+Constants.ICONS_EXTENSION);
		if (! iconFile.exists())
			return null;

		List<BufferedImage> icons = null;
		try {
			icons = this.readIcon(iconFile);
		} catch (IOException ex) {
			Logger.error("Failed to read ico file '"+iconFile.getPath()+"': "+ex.getMessage());
			return null;
		}
		if (icons == null || icons.isEmpty())
			return null;

		ImageIcon mimeTypeIcon = null;
		for (Image each : icons) {
			if (each.getWidth(null) != 32 || each.getHeight(null) != 32)
				continue;

			mimeTypeIcon = new ImageIcon(each);
			break;
		}
		if (mimeTypeIcon == null)
			mimeTypeIcon = new ImageIcon(icons.get(0));

		return mimeTypeIcon;
	}

	protected abstract boolean writeIcon(Image img, File out) throws FileNotFoundException, IOException;

	protected abstract List<BufferedImage> readIcon(File in) throws IOException;

	protected abstract void saveIcon(Application app);
	
	public final void setShortcutArgumentInstance(String token) {
		this.shortcut.setToken(token);

		if (this.fileAssociate != null)
			this.fileAssociate.setToken(token);
	}

	public void refresh() {}
}
