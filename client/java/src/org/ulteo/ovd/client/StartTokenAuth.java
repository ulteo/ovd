/*
 * Copyright (C) 2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Guillaume DUPAS <guillaume@ulteo.com> 2010
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

import java.io.File;
import java.io.IOException;

import org.ini4j.Wini;
import org.ulteo.ovd.client.integrated.OvdClientIntegrated;

import gnu.getopt.Getopt;

public class StartTokenAuth {

	public static void main(String[] args) {
		Getopt opt = new Getopt("ExternalApps",args,"c:");
		OvdClient cli = null;
		
		String profile = null;
		int c;
		while ((c = opt.getopt()) != -1) {
			if(c == 'c') {
				profile = new String(opt.getOptarg());
			}
		}
		
		if (profile != null) {
			try {
				String ovdServer = null;
				String token = null;
				Wini ini = new Wini(new File(profile));
				ovdServer = ini.get("server", "host");
				token = ini.get("token", "token");
				cli = new OvdClientIntegrated(ovdServer, true, token);
			} catch (IOException ioe) {
				ioe.printStackTrace();
			}
			cli.start();
		}
	}

}
