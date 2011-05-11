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
import org.ulteo.ovd.client.cache.AppsIconsCache;
import org.ulteo.ovd.client.cache.ContentManager;
import org.ulteo.ovd.client.cache.MimeTypeIconsCache;
import org.ulteo.ovd.integrated.mime.FileAssociate;
import org.ulteo.ovd.integrated.shorcut.Shortcut;

public abstract class SystemAbstract {
	public static final String KNOWN_ULTEO_TAG_FILE = ".ulteo.id";

	protected Shortcut shortcut = null;
	protected FileAssociate fileAssociate = null;

	private ContentManager iconContentManager = null;
	private AppsIconsCache cache_appsIcons = null;
	private MimeTypeIconsCache cache_mimeTypeIcons = null;

	public SystemAbstract(ContentManager iconContentManager_, String sm_) {
		this.iconContentManager = iconContentManager_;

		this.cache_appsIcons = new AppsIconsCache(this.iconContentManager, sm_);
		this.cache_mimeTypeIcons = new MimeTypeIconsCache(this.iconContentManager);
	}

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

	public int updateAppsIconsCache(HashMap<Integer, ImageIcon> appsIcons) {
		int updatedIcons = 0;

		if (appsIcons == null || appsIcons.isEmpty())
			return -1;

		for (Integer each : appsIcons.keySet()) {
			int appId = each.intValue();

			if (this.cache_appsIcons.contains(appId))
				continue;

			ImageIcon icon = appsIcons.get(appId);
			if (icon == null) {
				Logger.warn("Application '"+appId+"' has no icon");
				continue;
			}
			try {
				if (this.cache_appsIcons.put(appId, icon))
					updatedIcons++;
			} catch (IOException ex) {
				Logger.error("Failed to write application icon ("+appId+"): "+ex.getMessage());
				continue;
			}
		}

		return updatedIcons;
	}

	public ImageIcon getAppIcon(int appId) {
		ImageIcon icon = null;
		try {
			icon = this.cache_appsIcons.get(appId);
		} catch (IOException ex) {
			Logger.error("Failed to read application icon ("+appId+"): "+ex.getMessage());
			return null;
		}
		return icon;
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
			if (this.cache_mimeTypeIcons.contains(mimeType))
				continue;

			ImageIcon icon = mimeTypesIcons.get(mimeType);
			if (icon == null) {
				Logger.warn("Mime-type '"+mimeType+"' has no icon");
				continue;
			}
			try {
				if (this.cache_mimeTypeIcons.put(mimeType, icon))
					updatedIcons++;
			} catch (IOException ex) {
				Logger.error("Failed to write mime-type icon ("+mimeType+"): "+ex.getMessage());
				continue;
			}
		}

		return updatedIcons;
	}

	public ImageIcon getMimeTypeIcon(String mimeType) {
		if (mimeType == null)
			return null;

		ImageIcon icon = null;
		try {
			icon = this.cache_mimeTypeIcons.get(mimeType);
		} catch (IOException ex) {
			Logger.error("Failed to read mime-type icon ("+mimeType+"): "+ex.getMessage());
			return null;
		}
		return icon;
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
