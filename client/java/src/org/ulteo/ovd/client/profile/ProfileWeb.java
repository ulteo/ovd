/*
 * Copyright (C) 2011-2012 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011, 2012
 * Author Thomas MOUTON <thomas@ulteo.com> 2012
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

package org.ulteo.ovd.client.profile;

import java.io.IOException;
import java.util.HashMap;
import java.util.List;
import java.util.Map.Entry;

import org.ulteo.Logger;
import org.ulteo.ovd.client.WebClientCommunication;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;


public class ProfileWeb extends Profile {
	private WebClientCommunication wcc = null;

	public ProfileWeb(WebClientCommunication wcc) {
		this.wcc = wcc;
	}
	
	@Override
	protected boolean loadProfileEntries() {
		Document doc = this.wcc.askForConfig();
		if (doc == null)
			return false;
		
		NodeList sections = doc.getElementsByTagName("entry");
		if (sections == null)
			return false;
		
		for (int i=0 ; i<sections.getLength() ; i++) {
			Node key = sections.item(i);
			Node parent = key.getParentNode();
			String sectionName = "";
			String keyName = "";
			String valueName = "";
			
			if (parent.hasAttributes() && key.hasAttributes()) {
				NamedNodeMap parentNode = parent.getAttributes();
				NamedNodeMap childNode = key.getAttributes();
				
				Node sectionNode = null;
				Node keyNode = null;
				Node valueNode = null;
				
				if (parentNode != null)
					sectionNode = parentNode.getNamedItem("id");
				
				if (childNode != null) {
					keyNode = childNode.getNamedItem("key");
					valueNode = childNode.getNamedItem("value");
				}

				if(sectionNode != null)
					sectionName = sectionNode.getNodeValue();
				if(keyNode != null)
					keyName = keyNode.getNodeValue();
				if(valueNode != null)
					valueName = valueNode.getNodeValue();
				
			}
			
			this.fillProfileMap(sectionName, keyName, valueName);
		}
		
		return true;
	}

	@Override
	protected void saveProfileEntries(HashMap<String, List<Entry<String, String>>> profileEntriesMap) {}
}
