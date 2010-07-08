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
