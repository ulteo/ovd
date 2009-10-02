/*
 * Copyright (C) 2009 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009
 * Author David LECHEVALIER <david@ulteo.com> 2009 
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

package org.ulteo;

import java.io.BufferedWriter;
import java.io.FileWriter;
import java.util.Calendar;

public class Logger {
	private static String filename = null;
	private static Logger instance = null;

	public Logger(String filename) {
		this.filename = filename;
		System.out.println("org.ulteo.Logger init '"+this.filename+"'");
		write("org.ulteo.Logger init '"+this.filename+"'", "info");
	}
	
	public void write(String msg, String type) {
		String buffer = getTime()+" ["+type.toUpperCase()+"] "+msg+"\n";
		try{
			FileWriter fstream = new FileWriter(this.filename, true);
			BufferedWriter out = new BufferedWriter(fstream);
			out.write(buffer);
			out.close();
		}catch (Exception e){
			System.err.println("org.ulteo.Logger error: " + e.getMessage());
		}
	}

	private static String getDate() {
		Calendar rightNow = Calendar.getInstance();
		int d = rightNow.get(Calendar.DAY_OF_MONTH);
		int M = rightNow.get(Calendar.MONTH);
		int y = rightNow.get(Calendar.YEAR);

		return ""+y+"-"+((M<10)?"0":"")+M+"-"+((d<10)?"0":"")+d;
	}

	private static String getTime() {
		Calendar rightNow = Calendar.getInstance();

		int h = rightNow.get(Calendar.HOUR_OF_DAY);
		int m = rightNow.get(Calendar.MINUTE);
		int s = rightNow.get(Calendar.SECOND);

		return ""+((h<10)?"0":"")+h+":"+((m<10)?"0":"")+m+":"+((s<10)?"0":"")+s;
	}

	public static Logger getInstance() {
		if (instance == null) {
			String tempdir = System.getProperty("java.io.tmpdir");
			if ( !(tempdir.endsWith("/") || tempdir.endsWith("\\")) )
				tempdir = tempdir + System.getProperty("file.separator");
			
			instance = new Logger(tempdir+"ulteo-ovd-"+getDate()+".log");
		}

		return instance;
	}

	public static void info(String msg) {
		getInstance().write(msg, "info");
	}

	public static void warn(String msg) {
		getInstance().write(msg, "warn");
	}

	public static void error(String msg) {
		getInstance().write(msg, "error");
	}

	public static void debug(String msg) {
		getInstance().write(msg, "debug");
	}

}
