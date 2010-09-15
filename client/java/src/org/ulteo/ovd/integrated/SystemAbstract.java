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

import org.ulteo.ovd.Application;
import org.ulteo.ovd.integrated.mime.FileAssociate;
import org.ulteo.ovd.integrated.shorcut.Shortcut;

public abstract class SystemAbstract {
	protected Shortcut shortcut = null;
	protected FileAssociate fileAssociate = null;

	public abstract String install(Application app);

	public abstract void uninstall(Application app);

	protected abstract void saveIcon(Application app);
	
	public final void setShortcutArgumentInstance(String token) {
		this.shortcut.setToken(token);
	}

	/*private void saveIcon() {
		File output = new File(Constants.iconsPath+Constants.separator+this.id+"."+((OSTools.isWindows()) ? "ico" : "png"));
		if (! output.exists()) {
			try {
				output.createNewFile();
			} catch (IOException ex) {
				Logger.getLogger(Application.class.getName()).log(Level.SEVERE, null, ex);
			}
		}
		BufferedImage buf = new BufferedImage(this.icon.getIconWidth(), this.icon.getIconHeight(), BufferedImage.TYPE_INT_RGB);
		Graphics2D graph = buf.createGraphics();
		graph.drawImage(this.icon.getImage(), 0, 0, null);
		graph.dispose();

		if (OSTools.isWindows()) {
			try {
				net.sf.image4j.codec.ico.ICOEncoder.write(buf, output);
			} catch (IOException ex) {
				Logger.getLogger(Application.class.getName()).log(Level.SEVERE, null, ex);
			}
		}
		else {
			try {
				ImageIO.write(buf, "png", output);
			}catch (Exception e) {}
		}

	}*/
}
