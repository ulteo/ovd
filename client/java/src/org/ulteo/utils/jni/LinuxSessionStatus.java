/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Vincent ROULLIER <v.roullier@ulteo.com> 2013
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

package org.ulteo.utils.jni;

import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;
import java.io.StringReader;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.parsers.ParserConfigurationException;
import org.xml.sax.SAXException;
import java.io.IOException;
import org.xml.sax.InputSource;

public class LinuxSessionStatus {
	public static String getSessionStatus() {
		String xml = LinuxSessionStatus.nGetSessionStatus();
		DocumentBuilder dom = null;
		
		try {
			dom = DocumentBuilderFactory.newInstance().newDocumentBuilder();
		} 
		catch (ParserConfigurationException e) 
		{
			e.printStackTrace();
		}
		
		InputSource is = new InputSource(new StringReader(xml));
		Document doc = null;
		
		try {
			doc = dom.parse(is);
		}
		catch (SAXException e)
		{
			e.printStackTrace();
		}
		catch (IOException e)
		{
			e.printStackTrace();
		}
		
		Element rootNode = doc.getDocumentElement();
		if (rootNode.getNodeName().equalsIgnoreCase("error")) {
			Logger.warn("Unable to get user additionnal config : "+rootNode.getAttribute("message"));
			return null;
		}
		
		NodeList catLst = rootNode.getElementsByTagName("session");
		Node cat = catLst.item(0);
        NamedNodeMap catAttrMap = cat.getAttributes();
		Node catAttr = catAttrMap.getNamedItem("status");
		
		return catAttr.getNodeValue();
	}
	
	private static native String nGetSessionStatus();
}