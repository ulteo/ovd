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

package org.ulteo.ovd.client;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import org.ulteo.ovd.integrated.OSTools;

public final class ClientInfos {
	public JVMInfos jvm_infos = null;
	public OSTools.OSInfos os_infos = null;

	private ClientInfos() {
		this.jvm_infos = ClientInfos.getJVMInfos();
		this.os_infos = OSTools.getOSInfos();
	}

	public static String getOVDVersion() {
		String versionPath = "/VERSION";

		InputStream versionStream = ClientInfos.class.getResourceAsStream(versionPath);
		if (versionStream == null) {
			System.out.println("No version file ("+versionPath+")");
			return null;
		}
		try {
			InputStreamReader reader = new InputStreamReader(versionStream);
			BufferedReader in = new BufferedReader(reader);
			String version = in.readLine();

			return version;
		} catch (IOException ex) {
			System.out.println("Error while reading manifest file ("+versionPath+"): "+ex.getMessage());
			return null;
		}
	}

	public static ClientInfos getClientInfos() {
		return new ClientInfos();
	}

	public static void showClientInfos() {
		ClientInfos infos = new ClientInfos();
		System.out.println("OS name: "+infos.os_infos.name+" version: "+infos.os_infos.version+" arch: "+infos.os_infos.arch);
		System.out.println("JRE vendor: "+infos.jvm_infos.jre_vendor+"("+infos.jvm_infos.jre_vendor_url+") version: "+infos.jvm_infos.jre_version);
		System.out.println("JRE Specification vendor: "+infos.jvm_infos.jre_spec_vendor+" name: "+infos.jvm_infos.jre_spec_name+" version: "+infos.jvm_infos.jre_spec_version);
		System.out.println("JVM vendor: "+infos.jvm_infos.jvm_vendor+" name: "+infos.jvm_infos.jvm_name+" version: "+infos.jvm_infos.jvm_version);
		System.out.println("JVM Specification vendor: "+infos.jvm_infos.jvm_spec_vendor+" name: "+infos.jvm_infos.jvm_spec_name+" version: "+infos.jvm_infos.jvm_spec_version);
		System.out.println("OVD version: "+ClientInfos.getOVDVersion());
	}

	public static final class JVMInfos {
		public String jre_version = null;
		public String jre_vendor = null;
		public String jre_vendor_url = null;

		public String jre_spec_version = null;
		public String jre_spec_vendor = null;
		public String jre_spec_name = null;

		public String jvm_version = null;
		public String jvm_vendor = null;
		public String jvm_name = null;

		public String jvm_spec_version = null;
		public String jvm_spec_vendor = null;
		public String jvm_spec_name = null;
	}

	public static JVMInfos getJVMInfos() {
		JVMInfos jvm_infos = new JVMInfos();

		jvm_infos.jre_version = System.getProperty("java.version");
		jvm_infos.jre_vendor = System.getProperty("java.vendor");
		jvm_infos.jre_vendor_url = System.getProperty("java.vendor.url");

		jvm_infos.jre_spec_version = System.getProperty("java.specification.version");
		jvm_infos.jre_spec_vendor = System.getProperty("java.specification.vendor");
		jvm_infos.jre_spec_name = System.getProperty("java.specification.name");

		jvm_infos.jvm_version = System.getProperty("java.vm.version");
		jvm_infos.jvm_vendor = System.getProperty("java.vm.vendor");
		jvm_infos.jvm_name = System.getProperty("java.vm.name");

		jvm_infos.jvm_spec_version = System.getProperty("java.specification.version");
		jvm_infos.jvm_spec_vendor = System.getProperty("java.specification.vendor");
		jvm_infos.jvm_spec_name = System.getProperty("java.specification.name");

		return jvm_infos;
	}
}
