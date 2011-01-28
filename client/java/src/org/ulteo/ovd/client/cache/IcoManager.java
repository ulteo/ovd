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

import java.awt.Graphics2D;
import java.awt.Image;
import java.awt.image.BufferedImage;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.List;
import javax.swing.ImageIcon;
import net.sf.image4j.codec.ico.ICODecoder;
import net.sf.image4j.codec.ico.ICOEncoder;
import org.ulteo.Logger;

public class IcoManager extends ContentManager {
	public IcoManager() {
		super("ico");
	}

	@Override
	protected Object load(File f) throws IOException {
		if (f == null)
			return null;

		if (! f.exists() || f.isDirectory())
			return null;

		List<BufferedImage> iconsList = ICODecoder.read(f);
		if (iconsList == null || iconsList.isEmpty())
			return null;

		ImageIcon mimeTypeIcon = null;
		for (Image each : iconsList) {
			if (each.getWidth(null) != 32 || each.getHeight(null) != 32)
				continue;

			mimeTypeIcon = new ImageIcon(each);
			break;
		}
		if (mimeTypeIcon == null)
			mimeTypeIcon = new ImageIcon(iconsList.get(0));

		return mimeTypeIcon;
	}

	@Override
	protected void save(File f, Object content) throws IOException {
		if (f == null || content == null)
			return;

		if (! (content instanceof Image))
			return;

		Image img = (Image) content;

		int width = img.getWidth(null);
		int height = img.getHeight(null);
		if (width <= 0 || height <= 0) {
			Logger.error("Icon size is too small: "+width+"x"+height);
			return;
		}

		BufferedImage buf = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);
		Graphics2D graph = buf.createGraphics();
		graph.drawImage(img, 0, 0, null);
		graph.dispose();

		BufferedOutputStream os = new BufferedOutputStream(new FileOutputStream(f));
		ICOEncoder.write(buf, os);
		os.close();
	}
	
}
