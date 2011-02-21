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

package org.ulteo.ovd.client.bugreport;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import org.ulteo.Logger;

public class BugReport {
	private static final String NEW_LINE = System.getProperty("line.separator");

	private String date = null;
	private String version = null;
	private String system = null;
	private String jvm = null;
	private String description = null;

	public BugReport() {
		this.date = new String();
		this.version = new String();
		this.system = new String();
		this.jvm = new String();
		this.description = new String();
	}

	public void setDate(String date_) {
		if (this.date == null)
			return;

		this.date = date_;
	}

	public void setVersion(String version_) {
		if (this.version == null)
			return;

		this.version = version_;
	}

	public void setSystem(String system_) {
		if (this.system == null)
			return;

		this.system = system_;
	}

	public void setJVM(String jvm_) {
		if (this.jvm == null)
			return;

		this.jvm = jvm_;
	}

	public void setDescription(String description_) {
		if (this.description == null)
			return;

		this.description = description_;
	}

	public String getDate() {
		if (this.date == null)
			return new String();

		return this.date;
	}

	public String getVersion() {
		if (this.version == null)
			return new String();

		return this.version;
	}

	public String getSystem() {
		if (this.system == null)
			return new String();

		return this.system;
	}

	public String getJVM() {
		if (this.jvm == null)
			return new String();

		return this.jvm;
	}

	public String getDescription() {
		if (this.description == null)
			return new String();

		return this.description;
	}

	@Override
	public String toString() {
		String str = new String();
		str += "ULTEO OVD BUG REPORT" + NEW_LINE;
		str += NEW_LINE;
		str += "Date: " + this.date + NEW_LINE;
		str += "Version: " + this.version + NEW_LINE;
		str += "System: " + this.system + NEW_LINE;
		str += "JVM: " + this.jvm + NEW_LINE;
		str += NEW_LINE;
		str += "Description:" + NEW_LINE + this.description + NEW_LINE;
		str += NEW_LINE;
		str += "Log Content:" + NEW_LINE;
		str += Logger.getLogContent() + NEW_LINE;

		return str;
	}

	public void toTxtFile(File file) {
		if (file == null)
			return;
		
		FileWriter writer;
		try {
			writer = new FileWriter(file);
			writer.write(this.toString());
			writer.flush();
			writer.close();
		} catch (IOException ex) {
			Logger.error("[toTxtFile] An error occured while writing file '"+file.getPath()+"': "+ex.getMessage());
			return;
		}
	}
}
