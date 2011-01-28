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
import org.ulteo.utils.MD5;

public class MimeTypeIconsCache extends AbstractCache {

	public MimeTypeIconsCache(ContentManager contentManager) {
		super(Constants.PATH_CACHE_MIMETYPES_ICONS, contentManager);
	}

	public boolean contains(String mimeType) {
		if (mimeType == null)
			return false;

		String md5sum = MD5.getMD5Sum(mimeType);
		if (md5sum == null) {
			Logger.error("Failed to create md5sum for '"+mimeType+"'");
			return false;
		}

		return this.containsObject(md5sum);
	}

	public boolean put(String mimeType, ImageIcon icon) throws IOException {
		if (mimeType == null || icon == null)
			return false;

		String md5sum = MD5.getMD5Sum(mimeType);
		if (md5sum == null) {
			Logger.error("Failed to create md5sum for '"+mimeType+"'");
			return false;
		}

		Image img = icon.getImage();
		if (img == null) {
			Logger.warn("Mime-type '"+mimeType+"' has no image");
			return false;
		}

		return this.putObject(md5sum, img);
	}

	public ImageIcon get(String mimeType) throws IOException {
		if (mimeType == null)
			return null;

		String md5sum = MD5.getMD5Sum(mimeType);
		if (md5sum == null) {
			Logger.error("Failed to create md5sum for '"+mimeType+"'");
			return null;
		}

		return (ImageIcon) this.getObject(md5sum);
	}
}
