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

package org.ulteo.ovd.integrated.mime;

import java.io.File;
import java.io.FileOutputStream;
import java.io.OutputStream;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import org.ulteo.Logger;
import org.w3c.dom.Document;
import org.w3c.dom.Element;

public final class XDGMimeTypesFile {
	private static Document getNewDocument() {
		DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
		DocumentBuilder builder;
		try {
			builder = factory.newDocumentBuilder();
		} catch (ParserConfigurationException e) {
			e.printStackTrace();
			return null;
		}

		return builder.newDocument();
	}

	private Document xml = null;
	private Element mime_type = null;
	private Element comment = null;

	public XDGMimeTypesFile() {
		this.xml = getNewDocument();
		this.xml.setXmlVersion("1.0");

		Element mime_info = this.xml.createElement("mime-info");
		mime_info.setAttribute("xmlns", "http://www.freedesktop.org/standards/shared-mime-info");
		this.xml.appendChild(mime_info);

		this.mime_type = this.xml.createElement("mime-type");
		mime_info.appendChild(this.mime_type);

		this.comment = this.xml.createElement("comment");
		this.mime_type.appendChild(this.comment);

		this.setMimeType("");
		this.setMimeTypeComment("");
	}

	public void setMimeType(String mimeType) {
		this.mime_type.setAttribute("type", mimeType);
	}
	
	public void setMimeTypeComment(String comment) {
		this.comment.setTextContent(comment);
	}

	public void addPattern(String pattern) {
		Element glob = this.xml.createElement("glob");
		glob.setAttribute("pattern", pattern);
		this.mime_type.appendChild(glob);
	}

	public void write(File out_file) {
		OutputStream out;
		try {
			out = new FileOutputStream(out_file);
			if (out == null)
				throw new NullPointerException("no output stream is available from "+out_file.getPath());

			Transformer transformer = TransformerFactory.newInstance().newTransformer();
			transformer.transform(new DOMSource(this.xml), new StreamResult(out));

			out.flush();
		} catch (Exception ex) {
			Logger.error("Failed to write mimetypes-file '"+out_file.getPath()+"': "+ex.getMessage());
		}
	}
}
