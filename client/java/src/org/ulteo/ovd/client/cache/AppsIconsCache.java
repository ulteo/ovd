/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author Thomas MOUTON <thomas@ulteo.com> 2011
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

package org.ulteo.ovd.client.cache;

import java.awt.Image;
import java.io.IOException;
import javax.swing.ImageIcon;
import org.ulteo.Logger;
import org.ulteo.ovd.integrated.Constants;

public class AppsIconsCache extends AbstractCache {

	public AppsIconsCache(ContentManager contentManager, String sm) {
		super(Constants.PATH_CACHE_APPS_ICONS+Constants.FILE_SEPARATOR+sm, contentManager);
	}

	public boolean contains(int appId) {
		String idStr = Integer.toString(appId);

		return this.containsObject(idStr);
	}

	public boolean put(int appId, ImageIcon icon) throws IOException {
		if (icon == null)
			return false;

		String idStr = Integer.toString(appId);

		Image img = icon.getImage();
		if (img == null) {
			Logger.warn("Application '"+appId+"' has no image");
			return false;
		}

		return this.putObject(idStr, img);
	}

	public ImageIcon get(int appId) throws IOException {
		String idStr = Integer.toString(appId);

		return (ImageIcon) this.getObject(idStr);
	}
}
