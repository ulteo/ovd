/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
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

import java.util.HashMap;

public class CookieManager {
	private HashMap<String, String> parameters;
	
	public CookieManager() {
		this.parameters = new HashMap<String, String>();
	}

	public void addCookieElement(String fieldName, String FieldValue) {
		this.parameters.put(fieldName,FieldValue);
	}

	public void delCookieElement(String fieldName) {
		this.parameters.remove(fieldName);
	}

	public void clearCookieElement() {
		this.parameters.clear();
	}

	public boolean isEmptyCookie() {
		return this.parameters.isEmpty();
	}

	public String getFormatedCookie() {

		if (this.parameters.isEmpty()) {
			return "";
		}

		String cookie = "";

		for (String s : this.parameters.keySet()) {
			cookie +=";" + s + "=" + this.parameters.get(s);
		}
		
		return cookie + ";";
	}
}
