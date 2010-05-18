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

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.LineNumberReader;
import gnu.getopt.Getopt;

import org.ulteo.ovd.client.authInterface.AuthFrame;


public class StartConnection {

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		I18n.init();		
		String profile = null;
		String password = null;
		Getopt opt = new Getopt(Client.productName, args, "c:p:");

		int c;
		if ((c = opt.getopt()) != -1) {
			do {
				if(c == 'c') {
					profile = new String(opt.getOptarg());
				}
				else if(c == 'p') {
					password = new String(opt.getOptarg());
				}
			}while ((c = opt.getopt()) != -1); 
			InputStreamReader reader = null;
			LineNumberReader lineReader = null;
			File profileInfo = new File("./"+profile);
			try{
				FileInputStream fis = new FileInputStream(profileInfo);
				LineNumberReader l = new LineNumberReader(       
						new BufferedReader(new InputStreamReader(fis)));
				int count=0;
				String str;
				while ((str=l.readLine())!=null)
				{
					count = l.getLineNumber();
				}
				reader = new InputStreamReader(new  FileInputStream(profileInfo));
				lineReader = new LineNumberReader(reader);
				
				String username = null;
				String server = null;
				String current = null;
				int mode = 0;
				int resolution = 0;
				
				for(int i=0;i<count;i++) {
					current = lineReader.readLine();
					if(current.startsWith("login=")) {
						username = current.substring("login=".length());
					} else if (current.startsWith("host=")) {
						server = current.substring("host=".length());
					} else if (current.startsWith("mode=")) {
						if (current.substring("mode=".length()).equals("desktop"))
							mode=0;
						else if (current.substring("mode=".length()).equals("portal"))
							mode=1;
					} else if (current.startsWith("resolution=")) {
						if(current.substring("resolution=".length()).equals("800x600"))
							resolution=0;
							else if(current.substring("resolution=".length()).equals("1024x768"))
								resolution=1;
							else if(current.substring("resolution=".length()).equals("1280x678"))
								resolution=2;
							else if(current.substring("resolution=".length()).equals("maximized"))
								resolution=3;
							else
								resolution=4;
					}
				}
				Client cli = new Client(server, username, password, mode, resolution);
				cli.start();
			}catch(IOException ioe){
				ioe.printStackTrace();
			}
		}
		else
			new AuthFrame();
	}

}
