/*
 * Copyright (C) 2009-2010 Ulteo SAS
 * http://www.ulteo.com
 * Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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
import java.io.ByteArrayOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintStream;
import java.util.Calendar;


class LoggingOutputStream extends ByteArrayOutputStream { 
    private String lineSeparator = null; 
    private Logger logger = null;

    public LoggingOutputStream(Logger logger) { 
        super(); 
        this.logger = logger; 
        this.lineSeparator = System.getProperty("line.separator"); 
    } 
    
    public void flush() throws IOException { 
        String record; 
        synchronized(this) { 
            super.flush(); 
            record = this.toString(); 
            super.reset(); 
 
            if (record.length() == 0 || record.equals(lineSeparator)) { 
                // avoid empty records 
                return; 
            } 
 
            logger.write(record);
        } 
    } 
}


public class Logger {
	private static Logger instance = null;
	
	private boolean dump_stdout = false;
	private String filename = null;
	private boolean redirectOut = false;
	private BufferedWriter fileStream = null;
	private String lineSeparator = null;
	
	private PrintStream stdout = null;                                        
	
	public Logger(boolean dump_stdout, String filename, boolean redirectOut) throws Exception{
		this.lineSeparator = System.getProperty("line.separator"); 
		this.dump_stdout = dump_stdout;
		this.filename = filename;
		this.redirectOut = redirectOut;
		
		this.stdout = System.out;                                        
		
		if (this.filename != null) {
			FileWriter fstream = new FileWriter(this.filename, true);
			this.fileStream = new BufferedWriter(fstream);
		}
		
		if (this.redirectOut) {
			LoggingOutputStream los = new LoggingOutputStream(this);
			System.setOut(new PrintStream(los, true));
		    System.setErr(new PrintStream(los, true));
		}
	}
	
	public synchronized void write(String message) {
		if (this.dump_stdout) 
			this.stdout.println(message);
		
		if (this.fileStream != null) {
			try{
				this.fileStream.write(message+this.lineSeparator);
				this.fileStream.flush();
			}
			catch (Exception e){
				//e.printStackTrace();
			}
		}
	}
	
	public void write(String msg, String type) {
		String buffer = getTime()+" ["+type.toUpperCase()+"] "+msg;
		this.write(buffer);
	}

	public static String getDate() {
		Calendar rightNow = Calendar.getInstance();
		int d = rightNow.get(Calendar.DAY_OF_MONTH);
		int M = rightNow.get(Calendar.MONTH)+1;
		int y = rightNow.get(Calendar.YEAR);

		return ""+y+"-"+((M<10)?"0":"")+M+"-"+((d<10)?"0":"")+d;
	}

	public static String getTime() {
		Calendar rightNow = Calendar.getInstance();

		int h = rightNow.get(Calendar.HOUR_OF_DAY);
		int m = rightNow.get(Calendar.MINUTE);
		int s = rightNow.get(Calendar.SECOND);

		return ""+((h<10)?"0":"")+h+":"+((m<10)?"0":"")+m+":"+((s<10)?"0":"")+s;
	}

	public static boolean initInstance(boolean stdout, String filename, boolean redirectOut) {
		try {
			instance = new Logger(stdout, filename, redirectOut);
		}
		catch(Exception e) {
			e.printStackTrace();
			return false;
		}
		
		return true;
	}

	public static void info(String msg) {
		if (instance == null)
			return;
		instance.write(msg, "info");
	}

	public static void warn(String msg) {
		if (instance == null)
			return;
		instance.write(msg, "warn");
	}

	public static void error(String msg) {
		if (instance == null)
			return;
		instance.write(msg, "error");
	}

	public static void debug(String msg) {
		if (instance == null)
			return;
		instance.write(msg, "debug");
	}
}
