/*
 * Copyright (C) 2014 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2014
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

import net.propero.rdp.rdp5.ukbrdr.UkbrdrChannel;

import java.awt.event.InputMethodEvent;
import java.text.AttributedCharacterIterator;
import java.util.HashMap;
import java.util.Map;

import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;
import org.ulteo.utils.jni.WindowsTweaks;



public class IMEManager {
	private static IMEManager instance;
	protected String edit = "";
	protected String out = "";
	public boolean activated = true;
	private Map<Common, UkbrdrChannel> channels;
	private int caretX;
	private int caretY;
	private boolean useSeamless;

	
	public IMEManager() {
		this.channels = new HashMap<Common, UkbrdrChannel>();
		this.channels.clear();
		this.useSeamless = false;
	}
	
	
	public static IMEManager getInstance() {
		if (IMEManager.instance == null) {
			IMEManager.instance = new IMEManager();
		}
		
		return IMEManager.instance;
	}
	
	
	public void addChannel(Common common, UkbrdrChannel channel) {
		this.channels.put(common, channel);
	}
	
	public void setCaret(int x, int y) {
		this.caretX = x;
		this.caretY = y;
	}
	
	public void setSeamless(boolean value) {
		this.useSeamless = value;
		
	}
	
	public void inputMethodTextChanged(InputMethodEvent e, Common common) {
		UkbrdrChannel channel = this.channels.get(common);
		if (channel == null) {
			Logger.warn("Failed to process inputMethod Text change");
			return;
		}
		
		AttributedCharacterIterator ci = e.getText();

		if(e.getCommittedCharacterCount() == 0) {
			this.edit = "";

			if(ci != null) {
				for(int i=0 ; i < ci.getEndIndex() ; ++i) {
					this.edit = this.edit + ci.setIndex(i);
				}
			}
			
			if (OSTools.isWindows())
				WindowsTweaks.setIMEPosition(this.caretX, this.caretY, this.useSeamless);
			
			channel.sendPreedit(this.edit);
		}
		else {
			char c;
			// copy the committed text
			if (this.edit.length() > 0) {
				channel.stopComposition();
				return;
			}
			
			c = ci.first();
			channel.sendInput(c);
		}
	}
}
