/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David Lechavalier <david@ulteo.com> 2011
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

package net.propero.rdp;

import java.awt.event.KeyEvent;
import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.StringTokenizer;
import org.ulteo.Logger;


public class ScancodeConverter {
	private final List<Integer> specialKeysException = Arrays.asList(KeyEvent.VK_BACK_SPACE, KeyEvent.VK_SPACE, KeyEvent.VK_ENTER, KeyEvent.VK_DELETE, KeyEvent.VK_TAB);

	protected String scancodeFile = "/resources/keymaps/unicode";
	public Map<Integer, Integer> scancodeList = null;
	
	public ScancodeConverter() {
		scancodeList = new HashMap<Integer, Integer>();
	}
	
	public boolean load() {
		InputStream istr = null;
		String line = null;

		istr = ScancodeConverter.class.getResourceAsStream(this.scancodeFile);
		
		if (istr == null) {
			Logger.warn("The resource "+this.scancodeFile+" do not exist");
			return false;
		}

		try {
			BufferedReader in = new BufferedReader(new InputStreamReader(istr));
			
			while ((line = in.readLine()) != null) {
				line = line.trim();
				if ((line.length() == 0))
					continue;

				char fc = line.charAt(0);
				if (fc == '#') 
					continue;

				StringTokenizer st = new StringTokenizer(line);
				Integer javaCode = Integer.parseInt(st.nextToken());
				Integer rdpCode = Integer.decode(st.nextToken());

				this.scancodeList.put(javaCode, rdpCode);
			}

			istr.close();
		} catch (IOException ex) {
			Logger.warn("Unable to load keymap "+ this.scancodeFile +" : "+ ex);
			return false;
		} catch(NumberFormatException ex) {
			Logger.warn("Error parsing data from unicode keymap "+ this.scancodeFile +" : "+ ex);
			return false;
		} catch (IllegalArgumentException ex) {
			Logger.warn("Unable to open keymap "+ this.scancodeFile +" : "+ ex);
			return false;
		}

		return true;
	}
	
	public int get(KeyEvent e) {
		Integer code = this.scancodeList.get(e.getKeyCode());
		if (code == null)
			return 0;
		return code;
	}

	public boolean isSpecialKey(KeyEvent e) {
		if (e.getID() == KeyEvent.KEY_TYPED) {
			Character c = e.getKeyChar();
			return specialKeysException.contains(new Integer(c));
		}
		else
			return this.scancodeList.containsKey(e.getKeyCode());
	}
}
