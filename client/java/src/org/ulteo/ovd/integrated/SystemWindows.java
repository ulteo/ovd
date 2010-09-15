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
import java.awt.image.BufferedImage;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStream;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.mime.WindowsRegistry;
import org.ulteo.ovd.integrated.shorcut.WindowsShortcut;

public class SystemWindows extends SystemAbstract {

	public SystemWindows() {
		this.shortcut = new WindowsShortcut();
		this.fileAssociate = new WindowsRegistry();
	}

	@Override
	public String install(Application app) {
		this.saveIcon(app);
		this.fileAssociate.createAppAction(app);
		return this.shortcut.create(app);
	}

	@Override
	public void uninstall(Application app) {
		this.shortcut.remove(app);
		this.fileAssociate.removeAppAction(app);
	}

	@Override
	protected void saveIcon(Application app) {
		File output = new File(Constants.PATH_ICONS+Constants.FILE_SEPARATOR+app.getIconName()+".ico");
		if (! output.exists()) {
			try {
				output.createNewFile();
			} catch (IOException ex) {
				Logger.getLogger(Application.class.getName()).log(Level.SEVERE, null, ex);
			}
		}
		BufferedImage buf = new BufferedImage(app.getIcon().getIconWidth(), app.getIcon().getIconHeight(), BufferedImage.TYPE_INT_RGB);
		Graphics2D graph = buf.createGraphics();
		graph.drawImage(app.getIcon().getImage(), 0, 0, null);
		graph.dispose();
		
		
		try {
			BufferedOutputStream os = new BufferedOutputStream(new FileOutputStream(output));
			net.sf.image4j.codec.ico.ICOEncoder.write(buf, os);
			os.close();
		} catch (IOException ex) {
			Logger.getLogger(Application.class.getName()).log(Level.SEVERE, null, ex);
		}
	}
}
