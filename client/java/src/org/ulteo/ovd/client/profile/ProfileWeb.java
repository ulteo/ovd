/*
 * Copyright (C) 2011 Ulteo SAS
 * http://www.ulteo.com
 * Author David LECHEVALIER <david@ulteo.com> 2011
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

import org.ulteo.Logger;
import org.ulteo.ovd.client.WebClientCommunication;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;


public class ProfileWeb extends Profile {
	private static final String INI_SECTION_RDP = "rdp";
	private static final String INI_SECTION_LIMITATION = "limitation";
	private static final String INI_SECTION_PERSISTENT_CACHE = "persistentCache";
	
	
	
	@Override
	protected String loadPassword() throws IOException { return null; }

	@Override
	protected void storePassword(String password) throws IOException { }
	
	

	public ProfileProperties loadProfile(WebClientCommunication wcc) {
		ProfileProperties properties = new ProfileProperties();
		
		Document doc = wcc.askForConfig();
		if (doc == null)
			return null;
		
		NodeList sections = doc.getElementsByTagName("entry");
		if (sections == null)
			return null;
		
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
			
			if (sectionName.equalsIgnoreCase(INI_SECTION_RDP)) {
				if (keyName.equalsIgnoreCase(FIELD_RDP_PACKET_COMPRESSION)) {
					if (valueName.equalsIgnoreCase(VALUE_TRUE) || valueName.equalsIgnoreCase("1"))
						properties.setUsePacketCompression(true);
					else
						properties.setUsePacketCompression(false);
				}
				if (keyName.equalsIgnoreCase(FIELD_RDP_PERSISTENT_CACHE)) {
					if (valueName.equalsIgnoreCase(VALUE_TRUE) || valueName.equalsIgnoreCase("1"))
						properties.setUsePersistantCache(true);
					else
						properties.setUsePersistantCache(false);
				}
				if (keyName.equalsIgnoreCase(FIELD_RDP_USE_OFFSCREEN_CACHE)) {
					if (valueName.equalsIgnoreCase(VALUE_TRUE) || valueName.equalsIgnoreCase("1"))
						properties.setUseOffscreenCache(true);
					else
						properties.setUseOffscreenCache(false);
				}
				if (keyName.equalsIgnoreCase(FIELD_RDP_USE_BANDWIDTH_LIMITATION)) {
					if (valueName.equalsIgnoreCase(VALUE_TRUE) || valueName.equalsIgnoreCase("1"))
						properties.setUseBandwithLimitation(true);
					else
						properties.setUseBandwithLimitation(false);
				}
				if (keyName.equalsIgnoreCase(FIELD_RDP_SOCKET_TIMEOUT)) {
					int timeout = properties.getSocketTimeout();
					try {
						timeout = Integer.parseInt(valueName);	
					}
					catch (NumberFormatException e) {
						Logger.warn("Unable to convert value " + valueName + ": "+e.getMessage());
					}
					properties.setSocketTimeout(timeout);
				}
			}
			
			if (sectionName.equalsIgnoreCase(INI_SECTION_LIMITATION)) {
				if (keyName.equalsIgnoreCase(FIELD_LIMITATION_USE_DISK_LIMIT)) {
					if (valueName.equalsIgnoreCase(VALUE_TRUE) || valueName.equalsIgnoreCase("1"))
						properties.setUseDiskBandwithLimitation(true);
					else
						properties.setUseDiskBandwithLimitation(false);
				}
				if (keyName.equalsIgnoreCase(FIELD_LIMITATION_DISK_LIMIT)) {
					int limit = properties.getDiskBandwidthLimit();
					try {
						limit = Integer.parseInt(valueName);	
					}
					catch (NumberFormatException e) {
						Logger.warn("Unable to convert value " + valueName + ": "+e.getMessage());
					}
					properties.setDiskBandwidthLimit(limit);
				}
			}

			if (sectionName.equalsIgnoreCase(INI_SECTION_PERSISTENT_CACHE)) {
				if (keyName.equalsIgnoreCase(FIELD_PERSISTENT_CACHE_MAX_CELLS)) {
					int max = properties.getPersistentCacheMaxCells();
					try {
						max = Integer.parseInt(valueName);	
					}
					catch (NumberFormatException e) {
						Logger.warn("Unable to convert value " + valueName + ": "+e.getMessage());
					}
					properties.setPersistentCacheMaxCells(max);
				}
				if (keyName.equalsIgnoreCase(FIELD_PERSISTENT_CACHE_PATH)) {
					properties.setPersistentCachePath(valueName);
				}
			}
		}		
		return properties;
	}
}
