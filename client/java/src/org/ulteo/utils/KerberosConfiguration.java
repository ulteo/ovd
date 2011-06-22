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

package org.ulteo.utils;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;

import org.ulteo.Logger;
import org.ulteo.ovd.integrated.OSTools;

public class KerberosConfiguration {
	private static final String KRB5_FILE = "krb5.ini";
	private static final String LOGIN_FILE = "login.conf";

	private final String tmpDir = System.getProperty("java.io.tmpdir");
	
	private String realm;
	private String kdc = "";
	
	
	public KerberosConfiguration() {
//		System.setProperty("sun.security.krb5.debug", "true");   // very useful property
		if (! OSTools.isWindows())
			return;

		this.realm = System.getenv("USERDNSDOMAIN");
		String dcName = System.getenv("LOGONSERVER");
		if (dcName != null) {
			dcName = dcName.replace("\\", "");
			this.kdc = dcName+"."+this.realm;
		}
	}
	
	private boolean loadRessource(String name) {
		InputStream resourceStream = KerberosConfiguration.class.getResourceAsStream("/resources/"+name);
		
		if (resourceStream == null) {
			Logger.warn("Unable to find resource "+ name);
			return false;
		}
			
		String destFile = this.tmpDir + File.separator + name;
		try {
			int c = 0;
			File outputFile = new File(destFile);
			outputFile.deleteOnExit();
			FileOutputStream fos = new FileOutputStream(outputFile);	

			while ((c = resourceStream.read()) != -1) {
				fos.write(c);
			}
			fos.close();
		} catch (FileNotFoundException e) {
			Logger.error("Unable to find "+ destFile + " : "+ e.getMessage());
			return false;
		} catch (IOException e) {
			Logger.error("Error while creating "+destFile + " : "+ e.getMessage());
			return false;
		}

		return true;
	}
	
	
	public boolean initialize() {
		if (this.realm == null) {
			Logger.debug("No Realm available");
			return true;
		}
		
		Logger.debug("Loading "+KRB5_FILE+" in "+this.tmpDir);
		if (! loadRessource(KRB5_FILE)) {
			Logger.warn("Enabe to load "+KRB5_FILE);
			return false; 
		}
			
		Logger.debug("Loading "+LOGIN_FILE+" in "+this.tmpDir);
		if (! loadRessource(LOGIN_FILE)) {
			Logger.warn("Enabe to load "+LOGIN_FILE);
			return false; 
		}
		
		// config in order to use kerberos
		System.setProperty("java.security.krb5.conf", this.tmpDir+File.separator+KRB5_FILE);
		System.setProperty("java.security.auth.login.config", this.tmpDir+File.separator+LOGIN_FILE);
		
		System.setProperty("javax.security.auth.useSubjectCredsOnly", "false");
		System.setProperty("java.security.krb5.realm", this.realm);
		System.setProperty("java.security.krb5.kdc", this.kdc);
		
		Logger.debug("Realm "+this.realm);
		Logger.debug("KDC "+this.kdc);

		
		Logger.debug("Kerberos configuration done");
		
		return true;
	}
}
